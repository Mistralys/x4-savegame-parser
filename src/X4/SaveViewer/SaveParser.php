<?php
/**
 * @package X4SaveViewer
 * @subpackage Parser
 * @see \Mistralys\X4\SaveViewer\SaveParser
 */

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer;

use AppUtils\FileHelper;
use AppUtils\FileHelper\FileInfo;
use AppUtils\FileHelper_Exception;
use Mistralys\X4\SaveViewer\CLI\QueryCache;
use Mistralys\X4\SaveViewer\Data\ArchivedSave;
use Mistralys\X4\SaveViewer\Data\SaveManager;
use Mistralys\X4\SaveViewer\Data\SaveReader;
use Mistralys\X4\SaveViewer\Parser\Collections;
use Mistralys\X4\SaveViewer\Parser\FileAnalysis;
use Mistralys\X4\SaveViewer\Parser\Fragment\ClusterConnectionFragment;
use Mistralys\X4\SaveViewer\Parser\Fragment\EventLogFragment;
use Mistralys\X4\SaveViewer\Parser\Fragment\FactionsFragment;
use Mistralys\X4\SaveViewer\Parser\Fragment\PlayerStatsFragment;
use Mistralys\X4\SaveViewer\Parser\Fragment\SaveInfoFragment;
use Mistralys\X4\SaveViewer\Parser\SaveSelector\SaveGameFile;
use Mistralys\X4\SaveViewer\SaveManager\SaveTypes\MainSave;
use Mistralys\X4\SaveViewer\Config\Config;
use function AppLocalize\t;

/**
 * Main parser class that dispatches the extraction of the
 * game data from an XML savegame to subclasses specialized
 * for the different XML structures.
 *
 * See the <code>Fragments</code> subfolder for the XML
 * node reading classes.
 *
 * @package X4SaveViewer
 * @subpackage Parser
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */
class SaveParser extends BaseXMLParser
{
    public const int ERROR_SAVEGAME_MUST_BE_UNZIPPED = 137401;
    public const int ERROR_CANNOT_BACKUP_WITHOUT_SAVE = 137402;

    protected ?SaveGameFile $saveFile = null;
    protected bool $optionAutoBackup = false;
    protected bool $optionKeepXML = false;

    /**
     * @param SaveGameFile|MainSave $saveFile Path to the XML save file to parse. Must have been unzipped first via {@see SaveGameFile::unzip()}.
     * @throws SaveViewerException {@see self::ERROR_SAVEGAME_MUST_BE_UNZIPPED}
     */
    public static function create($saveFile) : SaveParser
    {
        if($saveFile instanceof MainSave) {
            $saveFile = $saveFile->getSaveFile();
        }

        if(!$saveFile->isUnzipped())
        {
            throw new SaveViewerException(
                'A savegame must be unzipped before parsing.',
                sprintf(
                    'Use the savegame\'s %s method to unzip it.',
                    array(SaveGameFile::class, 'unzip')[1]
                ),
                self::ERROR_SAVEGAME_MUST_BE_UNZIPPED
            );
        }

        return new SaveParser(
            $saveFile->getAnalysis(),
            $saveFile->requireXMLFile()->getPath(),
            $saveFile
        );
    }

    public static function createFromAnalysis(FileAnalysis $analysis) : SaveParser
    {
        return new self(
            $analysis,
            '',
            null
        );
    }

    public function __construct(FileAnalysis $analysis, string $xmlFilePath, ?SaveGameFile $saveFile)
    {
        $this->saveFile = $saveFile;

        parent::__construct(
            new Collections($analysis->getJSONFolder()),
            $analysis,
            $xmlFilePath
        );
    }

    public static function createFromMonitorConfig(MainSave $save) : SaveParser
    {
        return self::create($save)
            ->optionKeepXML(Config::isKeepXMLFiles())
            ->optionAutoBackup(Config::isAutoBackupEnabled());
    }

    public function getCollections() : Collections
    {
        return $this->collections;
    }

    /**
     * Override to add log analysis cache generation after data processing.
     *
     * @param bool $force
     * @return $this
     * @throws FileHelper_Exception
     * @throws SaveViewerException
     */
    public function postProcessFragments(bool $force=false) : BaseXMLParser
    {
        // Call parent implementation
        parent::postProcessFragments($force);

        // Generate log analysis cache automatically (WP1: Logbook Performance Optimization)
        $this->generateLogAnalysisCache();

        return $this;
    }

    /**
     * Generate log analysis cache automatically during extraction.
     * This pre-categorizes log entries for fast CLI API queries.
     *
     * @return void
     */
    private function generateLogAnalysisCache() : void
    {
        // Only generate for main savegame extractions with a save file
        if ($this->saveFile === null) {
            return;
        }

        try {
            // Create a temporary SaveManager and ArchivedSave to access SaveReader
            $manager = SaveManager::createFromConfig();
            $archivedSave = new ArchivedSave($manager, $this->analysis);
            $reader = new SaveReader($archivedSave);
            $log = $reader->getLog();

            // Only generate if not already present
            if (!$log->isCacheValid()) {
                $this->log('Generating log analysis cache...');
                $log->generateAnalysisCache();
                $this->log('Log analysis cache generated successfully.');
            }

            // Warm query cache for fast pagination (WP4: Logbook Performance Optimization)
            $this->warmLogQueryCache($archivedSave, $reader);

        } catch (\Exception $e) {
            // Log error but don't fail extraction
            $this->log('Warning: Failed to generate log analysis cache: ' . $e->getMessage());
        }
    }

    /**
     * Warm the log query cache for fast pagination.
     * Pre-loads all log entries into the query cache so first unfiltered
     * pagination request is fast.
     *
     * @param ArchivedSave $save The save file instance
     * @param SaveReader $reader The save reader instance
     * @return void
     */
    private function warmLogQueryCache(ArchivedSave $save, SaveReader $reader) : void
    {
        try {
            $this->log('Warming log query cache...');

            // Get the auto-cache key (same format as WP3)
            $cacheKey = '_log_unfiltered_' . $save->getSaveID();
            $fullCacheKey = '_log_full_' . $save->getSaveID();

            // Get all log entries (already optimized by WP2)
            $data = $reader->getLog()->toArrayForAPI();

            // Store in query cache
            $cache = new QueryCache($save->getManager());
            $cache->store($save, $fullCacheKey, $data);
            $cache->store($save, $cacheKey, $data);

            $this->log('Log query cache warmed successfully (' . count($data) . ' entries).');

        } catch (\Exception $e) {
            // Log error but don't fail extraction
            $this->log('Warning: Failed to warm log query cache: ' . $e->getMessage());
        }
    }

    /**
     * Runs all processing tasks in one:
     *
     * - {@see self::processFile()}
     * - {@see self::postProcessFragments()}
     * - {@see self::cleanUp()}
     *
     * @return $this
     * @throws FileHelper_Exception
     * @throws SaveViewerException
     */
    public function unpack() : self
    {
        $startTime = microtime(true);

        if($this->optionAutoBackup) {
            $this->createBackup();
        }

        $this->processFile();
        $this->postProcessFragments();
        $this->cleanUp();

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $this->analysis->setExtractionDuration($duration);

        return $this;
    }

    public function cleanUp() : void
    {
        $this->log('Cleanup | Running cleanup tasks.');

        $xmlFolder = $this->analysis->getXMLFolder();

        if(!$this->optionKeepXML && $xmlFolder->exists())
        {
            $this->log('Cleanup | Deleting the XML folder.');
            FileHelper::deleteTree($xmlFolder);
        }
    }

    public function optionAutoBackup(bool $enabled=true) : self
    {
        $this->optionAutoBackup = $enabled;
        return $this;
    }

    public function getCannotBackupMessage() : ?string
    {
        if(!isset($this->saveFile)) {
            return t('No savegame specified.');
        }

        if($this->saveFile->getFileMode() !== SaveGameFile::FILE_MODE_ZIP) {
            return t('No archive file available.');
        }

        return null;
    }

    /**
     * Creates a copy of the savegame ZIP file into the parser's
     * output folder as a backup.
     *
     * NOTE: This is done automatically if the option is enabled
     * using {@see self::optionAutoBackup()}.
     *
     * @return $this
     * @throws SaveViewerException
     * @throws FileHelper_Exception
     */
    public function createBackup() : self
    {
        if(!isset($this->saveFile)) {
            throw new SaveViewerException(
                'Backup not possible, no savegame specified.',
                'To back up a savegame, a main savegame instance must be passed to the parser.',
                self::ERROR_CANNOT_BACKUP_WITHOUT_SAVE
            );
        }

        if($this->saveFile->getFileMode() !== SaveGameFile::FILE_MODE_ZIP) {
            return $this;
        }

        $zipFile = $this->saveFile->requireZipFile();
        $targetFile = $this->getBackupFile();

        if(!$targetFile->exists())
        {
            $zipFile->copyTo($targetFile);
        }

        return $this;
    }

    public function hasBackup() : bool
    {
        return $this->getBackupFile()->exists();
    }

    public function getBackupFile() : FileInfo
    {
        return $this->analysis->getBackupFile();
    }

    public function optionKeepXML(bool $keepXML) : self
    {
        $this->optionKeepXML = $keepXML;
        return $this;
    }

    protected function registerActions() : void
    {
        $this->registerExtractXML(SaveInfoFragment::TAG_PATH, SaveInfoFragment::class);
        $this->registerExtractXML(FactionsFragment::TAG_PATH, FactionsFragment::class);
        $this->registerExtractXML(ClusterConnectionFragment::TAG_PATH, ClusterConnectionFragment::class);
        $this->registerExtractXML(PlayerStatsFragment::TAG_PATH, PlayerStatsFragment::class);
        $this->registerExtractXML(EventLogFragment::TAG_PATH, EventLogFragment::class);

        // These tag paths are ignored to speed up the process.
        // This works well, because the XML Reader advances down
        // into the XML tree until the deepest level. This way,
        // it does not go into the ignored elements.
        $this->registerIgnore('savegame.messages');
        $this->registerIgnore('savegame.universe.blacklists');
        $this->registerIgnore('savegame.universe.traderules');
        $this->registerIgnore('savegame.universe.jobs');
        $this->registerIgnore('savegame.universe.god');
        $this->registerIgnore('savegame.universe.controltextures');
        $this->registerIgnore('savegame.universe.physics');
        $this->registerIgnore('savegame.economylog');
        $this->registerIgnore('savegame.script');
        $this->registerIgnore('savegame.md');
        $this->registerIgnore('savegame.missions');
        $this->registerIgnore('savegame.aidirector');
        $this->registerIgnore('savegame.operations');
        $this->registerIgnore('savegame.ventures');
        $this->registerIgnore('savegame.notifications');
        $this->registerIgnore('savegame.ui');
        $this->registerIgnore('savegame.universe.uianchorhelper');
        $this->registerIgnore('savegame.universe.cameraanchor');
    }
}

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
use AppUtils\FileHelper\FolderInfo;
use AppUtils\FileHelper_Exception;
use Mistralys\X4\SaveViewer\Data\BaseSaveFile;
use Mistralys\X4\SaveViewer\Parser\Collections;
use Mistralys\X4\SaveViewer\Parser\Fragment\ClusterConnectionFragment;
use Mistralys\X4\SaveViewer\Parser\Fragment\EventLogFragment;
use Mistralys\X4\SaveViewer\Parser\Fragment\FactionsFragment;
use Mistralys\X4\SaveViewer\Parser\Fragment\PlayerStatsFragment;
use Mistralys\X4\SaveViewer\Parser\Fragment\SaveInfoFragment;
use Mistralys\X4\SaveViewer\Parser\SaveSelector\SaveGameFile;
use Mistralys\X4\SaveViewer\SaveManager\SaveTypes\MainSave;

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
    public const ERROR_SAVEGAME_MUST_BE_UNZIPPED = 5454654;

    protected SaveGameFile $saveFile;
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

        return new SaveParser($saveFile);
    }

    /**
     * @throws SaveViewerException
     */
    public function __construct(SaveGameFile $saveFile)
    {
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

        $this->saveFile = $saveFile;

        $folder = $this->saveFile->getStorageFolder()->getPath();

        parent::__construct(
            new Collections($folder.'/JSON'),
            $saveFile->getAnalysis(),
            $saveFile->requireXMLFile()->getPath(),
            $folder
        );
    }

    public function getCollections() : Collections
    {
        return $this->collections;
    }

    public function unpack() : self
    {
        if($this->optionAutoBackup) {
            $this->createBackup();
        }

        $this->processFile();
        $this->postProcessFragments();
        $this->cleanUp();

        return $this;
    }

    protected function cleanUp() : void
    {
        $this->log('Cleanup | Running cleanup tasks.');

        $xmlFolder = FolderInfo::factory($this->saveFile->getStorageFolder()->getPath().'/XML');

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
        $this->registerExtractXML('savegame.info', SaveInfoFragment::class);
        $this->registerExtractXML('savegame.universe.factions', FactionsFragment::class);
        $this->registerExtractXML('savegame.universe.component[galaxy].connections.connection[ID]', ClusterConnectionFragment::class);
        $this->registerExtractXML('savegame.stats', PlayerStatsFragment::class);
        $this->registerExtractXML('savegame.log', EventLogFragment::class);

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

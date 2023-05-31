<?php
/**
 * @package X4SaveViewer
 * @subpackage CLI
 * @see \Mistralys\X4\SaveViewer\CLI\CLIHandler
 */

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\CLI;

use AppUtils\ConvertHelper;
use AppUtils\StringBuilder;
use League\CLImate\CLImate;
use Mistralys\X4\SaveViewer\Data\ArchivedSave;
use Mistralys\X4\SaveViewer\Data\SaveManager;
use Mistralys\X4\SaveViewer\Parser\Collections;
use Mistralys\X4\SaveViewer\Parser\XMLFragmentParser;
use Mistralys\X4\SaveViewer\SaveParser;
use Throwable;
use function AppLocalize\t;
use function AppUtils\sb;

/**
 * Command line handling class: Manages the command line
 * extraction of savegames.
 *
 * @package X4SaveViewer
 * @subpackage CLI
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */
class CLIHandler
{
    public const COMMAND_EXTRACT = 'extract';
    public const COMMAND_HELP = 'help';
    public const COMMAND_LIST = 'list';
    public const COMMAND_EXTRACT_ALL = 'extract-all';
    public const COMMAND_KEEP_XML = 'keep-xml';
    public const COMMAND_NO_BACKUP = 'no-backup';
    public const COMMAND_REBUILD_JSON = 'rebuild-json';
    public const COMMAND_LIST_ARCHIVED = 'list-archived';

    private SaveManager $manager;
    private CLImate $cli;

    public function __construct(SaveManager $manager)
    {
        $this->manager = $manager;
        $this->cli = new CLImate();

        $this->registerCommands();
    }

    public static function create(SaveManager $manager) : CLIHandler
    {
        return new CLIHandler($manager);
    }

    public static function createFromConfig() : CLIHandler
    {
        return self::create(SaveManager::createFromConfig());
    }

    private bool $optionKeepXML = false;
    private bool $optionAutoBackup = true;

    public function handle() : void
    {
        try
        {
            $this->cli->arguments->parse();

            if($this->cli->arguments->defined(self::COMMAND_HELP)) {
                $this->cli->usage();
                return;
            }

            if($this->cli->arguments->defined(self::COMMAND_LIST_ARCHIVED)) {
                $this->execute_listArchivedNames();
                return;
            }

            if($this->cli->arguments->defined(self::COMMAND_LIST)) {
                $this->execute_listNames();
                return;
            }

            $rebuild = $this->cli->arguments->get(self::COMMAND_REBUILD_JSON);
            if(!empty($rebuild)) {
                $this->execute_rebuildJSON((string)$rebuild);
            }

            $this->optionKeepXML = $this->cli->arguments->defined(self::COMMAND_KEEP_XML);
            $this->optionAutoBackup = !$this->cli->arguments->defined(self::COMMAND_NO_BACKUP);

            if($this->cli->arguments->defined(self::COMMAND_EXTRACT_ALL)) {
                $this->execute_extractAll();
                return;
            }

            $nameString = $this->cli->arguments->get(self::COMMAND_EXTRACT);
            if(!empty($nameString)) {
                $this->execute_extract(ConvertHelper::explodeTrim(' ', $nameString));
                return;
            }
        }
        catch (Throwable $e)
        {
            $this->cli->bold()->error(t('A %1$s exception occurred.', get_class($e)));
            $this->cli->error((string)sb()->t('Code:')->add($e->getCode()));
            $this->cli->error((string)sb()->t('Message:')->add($e->getMessage()));
        }
    }

    private function registerCommands() : void
    {
        $this->cli->description((string)sb()
            ->t('Allows extracting the information from X4 savegame files into folders as JSON files, as well as creating a backup of the savegame itself.')
        );

        $this->registerCommand(
            self::COMMAND_EXTRACT,
            'e',
            self::COMMAND_EXTRACT,
            sb()
                ->t('Extract savegames by their name (e.g. "quicksave", "autosave_01").')
                ->t('Separate multiple savegame names with spaces, surrounded by quotes.'),
            ''
        );

        $this->registerCommand(
            self::COMMAND_EXTRACT_ALL,
            'all',
            self::COMMAND_EXTRACT_ALL,
            sb()
                ->t('Extracts all available savegames that have not been extracted yet.'),
            null
        );

        $this->registerCommand(
            self::COMMAND_KEEP_XML,
            'xml',
            self::COMMAND_KEEP_XML,
            sb()
                ->t('Keep the XML files in the savegame storage folder when extracting.'),
            null
        );

        $this->registerCommand(
            self::COMMAND_NO_BACKUP,
            '',
            self::COMMAND_NO_BACKUP,
            sb()
                ->t('Do not create a savegame backup when extracting.'),
            null
        );

        $this->registerCommand(
            self::COMMAND_REBUILD_JSON,
            'rebuild',
            self::COMMAND_REBUILD_JSON,
            sb()
                ->t('Rebuild an archived savegame\'s JSON files from its XML fragments.')
                ->t('Requires the XML fragments to be present.')
                ->t(
                    'Target must be an unpacked folder name, e.g. %1$s.',
                    'unpack-20230528171642-quicksave'
                )
        );

        $this->registerCommand(
            self::COMMAND_LIST_ARCHIVED,
            'la',
            self::COMMAND_LIST_ARCHIVED,
            sb()->t('List all archived savegame names.'),
            null
        );

        $this->registerCommand(
            self::COMMAND_LIST,
            'l',
            self::COMMAND_LIST,
            sb()->t('Lists all available savegame names.'),
            null
        );

        $this->registerCommand(
            self::COMMAND_HELP,
            'h',
            'help',
            sb()
                ->t('Displays usage and command help.'),
            null
        );
    }

    private function registerCommand(string $name, string $prefix, string $longPrefix, StringBuilder $description, ?string $defaultValue='') : void
    {
        $config = array(
            'prefix' => $prefix,
            'longPrefix' => $longPrefix,
            'description' => (string)$description,
        );

        if($defaultValue === null) {
            $config['noValue'] = true;
        } else {
            $config['defaultValue'] = $defaultValue;
        }

        $this->cli->arguments->add(array(
            $name => $config
        ));
    }

    private function execute_extract(array $names) : void
    {
        $this->cli->out(t('Extracting the specified savegames.'));
        $this->cli->out(t('Output folder:').' '.$this->manager->getStorageFolder()->getPath());
        $this->cli->out('');

        foreach($names as $name)
        {
            if($this->manager->nameExists($name))
            {
                $this->cli->info(sprintf('Savegame %s found.', '['.$name.']'));
                $save = $this->manager->getSaveByName($name);

                if($save->isTempSave())
                {
                    $this->cli->yellow('- '.t('Cannot extract a temp save.'));
                }
                else if($save->isUnpacked())
                {
                    $this->cli->info('- '.t('Already extracted, skipping.'));
                }
                else
                {
                    $this->cli->out('- '.t('Processing the file.'));

                    if(!$save->isUnzipped()) {
                        $this->cli->out('- '.t('Unzipping...'));
                        $save->unzip();
                    }

                    $parser = SaveParser::createFromMonitorConfig($save);
                    $parser->optionKeepXML($this->optionKeepXML);

                    if($this->optionAutoBackup)
                    {
                        $this->cli->out('- ' . t('Creating backup...'));
                        $message = $parser->getCannotBackupMessage();

                        if($message === null)
                        {
                            $parser->createBackup();
                        }
                        else
                        {
                            $this->cli->yellow('- '.t('Backup failed:').' '.$message);
                        }
                    }
                    else
                    {
                        $this->cli->out('- '.t('Backup disabled, ignoring.'));
                    }

                    $this->cli->out('- '.t('Extracting XML fragments...'));
                    $parser->processFile();

                    $this->cli->out('- '.t('Analysing and writing JSON files...'));
                    $parser->postProcessFragments();

                    $this->cli->out('- '.t('Cleaning up...'));
                    $parser->cleanUp();

                    $this->cli->info(t('Done.'));
                }
            }
            else
            {
                $this->cli->error(sprintf('Savegame %s not found.', '['.$name.']'));
            }

            $this->cli->out('');
        }
    }

    private function execute_listNames() : void
    {
        $saves = $this->manager->getSaves();

        $this->cli->out(t('Listing available saves.'));
        $this->cli->out(t('%1$s saves found.', count($saves)));
        $this->cli->out('');

        $lines = array();

        foreach($saves as $save)
        {
            $lines[] = array(
                t('Name') => $save->getSaveName(),
                t('Modified') => strip_tags(ConvertHelper::date2listLabel($save->getDateModified(), true, true)),
                t('Unpacked?') => strtoupper(ConvertHelper::bool2string($save->isUnpacked(), true))
            );
        }

        $this->cli->table($lines);
    }

    private function execute_listArchivedNames() : void
    {
        $saves = $this->manager->getArchivedSaves();

        $this->cli->out(t('Listing available archived saves.'));
        $this->cli->out(t('%1$s saves found.', count($saves)));
        $this->cli->out('');

        $lines = array();

        foreach($saves as $save)
        {
            $lines[] = array(
                t('Name') => $save->getStorageFolder()->getName(),
                t('Modified') => strip_tags(ConvertHelper::date2listLabel($save->getDateModified(), true, true))
            );
        }

        $this->cli->table($lines);
    }

    private function execute_extractAll() : void
    {
        $this->execute_extract($this->manager->getSaveNames());
    }

    private function execute_rebuildJSON(string $folderName) : void
    {
        $save = $this->getArchivedSaveByFolder($folderName);

        if ($save === null)
        {
            $this->cli->error(t('Cannot find the archived savegame %1$s.', '[' . $folderName . ']'));
            return;
        }

        $this->cli->out(t('Rebuild JSON files from XML fragments.'));

        $parser = SaveParser::createFromAnalysis($save->getAnalysis());

        $processors = $parser->getPostProcessors();
        if(empty($processors)) {
            $this->cli->error('- '.t('No XML fragments found.'));
            return;
        }

        $this->cli->out('- '.t('Found %1$s XML fragments.', count($processors)));
        $this->cli->out('- '.t('Extracting JSON.'));

        $parser->postProcessFragments(true);

        $this->cli->out('- '.t('Done.'));
        $this->cli->out('');
    }

    private function getArchivedSaveByFolder(string $folderName) : ?ArchivedSave
    {
        $saves = $this->manager->getArchivedSaves();

        foreach($saves as $save)
        {
            if($save->getStorageFolder()->getName() === $folderName)
            {
                return $save;
            }
        }

        return null;
    }
}

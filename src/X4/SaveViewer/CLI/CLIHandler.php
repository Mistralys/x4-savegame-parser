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
use Mistralys\X4\SaveViewer\Data\SaveManager;
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

            if($this->cli->arguments->defined(self::COMMAND_LIST)) {
                $this->execute_listNames();
                return;
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
                        $parser->createBackup();
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

        $this->cli->out(t('Available saves:'));
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

    private function execute_extractAll() : void
    {
        $this->execute_extract($this->manager->getSaveNames());
    }
}

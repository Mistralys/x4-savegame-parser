<?php

declare(strict_types=1);

namespace X4\SaveGameParserTests\TestClasses;

use AppUtils\ClassHelper;
use AppUtils\FileHelper;
use AppUtils\FileHelper\FolderInfo;
use Mistralys\X4\SaveViewer\Data\BaseSaveFile;
use Mistralys\X4\SaveViewer\Data\SaveManager;
use Mistralys\X4\SaveViewer\Parser\SaveSelector;
use Mistralys\X4\SaveViewer\Traits\DebuggableInterface;
use Mistralys\X4\SaveViewer\Traits\DebuggableTrait;
use PHPUnit\Framework\TestCase;
use Mistralys\X4\SaveViewer\Config\Config;

require_once __DIR__.'/../bootstrap.php';

abstract class X4ParserTestCase extends TestCase implements DebuggableInterface
{
    use DebuggableTrait;

    protected string $filesFolder;
    protected string $saveGameFile;
    protected array $foldersCleanup = array();

    /**
     * Cached SaveManager instance for test saves.
     * Extracted savegames are immutable - tests should never modify save data on disk.
     */
    private ?SaveManager $saveManager = null;

    protected function setUp() : void
    {
        parent::setUp();

        Config::setTestSuiteEnabled(true);

        $this->filesFolder = __DIR__.'/../files';
        $this->saveGameFile = __DIR__.'/../files/quicksave.xml';
        $this->foldersCleanup = array();

        // Initialize cached SaveManager for test saves
        $testSavesFolder = __DIR__.'/../files/test-saves';
        $this->saveManager = SaveManager::create($testSavesFolder, $testSavesFolder);

        // Validate that required test saves exist
        $this->validateTestSavesExist();

        $this->disableLogging();
    }

    protected function tearDown() : void
    {
        parent::tearDown();

        foreach($this->foldersCleanup as $path)
        {
            FileHelper::deleteTree($path);
        }
    }

    /**
     * @param string|FolderInfo $path
     * @return void
     */
    public function addFolderToRemove($path) : void
    {
        $this->foldersCleanup[] = FolderInfo::factory($path)->getFolderPath();
    }

    public function getLogIdentifier() : string
    {
        return sprintf(
            'Test [%s] | ',
            ClassHelper::getClassTypeName($this)
        );
    }

    public function createSelector() : SaveSelector
    {
        return SaveSelector::create(Config::getSavesFolder(), Config::getStorageFolder())
            ->setLoggingEnabled($this->isLoggingEnabled());
    }

    /**
     * Validates that both required test saves are available.
     * Fails immediately with helpful error message if saves are missing.
     *
     * @return void
     */
    private function validateTestSavesExist(): void
    {
        $requiredSaves = [
            TestSaveNames::SAVE_ADVANCED_CREATIVE,
            TestSaveNames::SAVE_START_SCIENTIST
        ];

        $missing = [];
        foreach ($requiredSaves as $saveName) {
            if ($this->getSaveByName($saveName) === null) {
                $missing[] = $saveName;
            }
        }

        if (!empty($missing)) {
            $available = $this->getAvailableSaveNames();

            $message = "Required test save(s) not found:\n";
            foreach ($missing as $saveName) {
                $message .= "  - Expected folder pattern: unpack-*-{$saveName}\n";
            }
            $message .= "\nAvailable saves:\n";
            if (empty($available)) {
                $message .= "  (none)\n";
            } else {
                foreach ($available as $name) {
                    $message .= "  - {$name}\n";
                }
            }
            $message .= "\nPlease run: php ./tests/extract-test-saves.php\n";
            $message .= "See: /tests/README.md for more information\n";

            $this->fail($message);
        }
    }

    /**
     * Get the cached SaveManager instance for test saves.
     *
     * Note: Extracted savegames are immutable and should not be modified by tests.
     *
     * @return SaveManager
     */
    protected function getSaveManager(): SaveManager
    {
        return $this->saveManager;
    }

    /**
     * Find a save by name, matching folder pattern unpack-*-{saveName}.
     *
     * @param string $saveName The save name (e.g., 'advanced-creative-v8')
     * @return BaseSaveFile|null
     */
    protected function getSaveByName(string $saveName): ?BaseSaveFile
    {
        $saves = $this->saveManager->getArchivedSaves();

        foreach ($saves as $save) {
            $folderName = $save->getStorageFolder()->getName();
            // Check if folder name ends with the save name
            if (substr($folderName, -strlen($saveName)) === $saveName) {
                return $save;
            }
        }

        return null;
    }

    /**
     * Require a save by name, failing with helpful error if not found.
     *
     * @param string $saveName The save name (e.g., 'advanced-creative-v8')
     * @return BaseSaveFile
     */
    protected function requireSaveByName(string $saveName): BaseSaveFile
    {
        $save = $this->getSaveByName($saveName);

        if ($save === null) {
            $available = $this->getAvailableSaveNames();

            $message = "Required test save not found: {$saveName}\n";
            $message .= "Expected folder pattern: unpack-*-{$saveName}\n\n";
            $message .= "Available saves:\n";
            if (empty($available)) {
                $message .= "  (none)\n";
            } else {
                foreach ($available as $name) {
                    $message .= "  - {$name}\n";
                }
            }
            $message .= "\nPlease run: php ./tests/extract-test-saves.php\n";
            $message .= "See: /tests/README.md for more information\n";

            $this->fail($message);
        }

        return $save;
    }

    /**
     * Get list of available save folder names for error messages.
     *
     * @return string[]
     */
    private function getAvailableSaveNames(): array
    {
        $saves = $this->saveManager->getArchivedSaves();
        $names = [];

        foreach ($saves as $save) {
            $names[] = $save->getStorageFolder()->getName();
        }

        return $names;
    }
}

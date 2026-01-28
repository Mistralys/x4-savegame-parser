<?php

declare(strict_types=1);

namespace X4\SaveGameParserTests\TestClasses;

use AppUtils\ClassHelper;
use AppUtils\FileHelper;
use AppUtils\FileHelper\FolderInfo;
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

    protected function setUp() : void
    {
        parent::setUp();

        $this->filesFolder = __DIR__.'/../files';
        $this->saveGameFile = __DIR__.'/../files/quicksave.xml';
        $this->foldersCleanup = array();

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
        return SaveSelector::create(Config::getSavesFolder(), Config::getString('X4_STORAGE_FOLDER'))
            ->setLoggingEnabled($this->isLoggingEnabled());
    }
}

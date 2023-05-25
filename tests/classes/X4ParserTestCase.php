<?php

declare(strict_types=1);

namespace X4\SaveGameParserTests\TestClasses;

use AppUtils\ClassHelper;
use Mistralys\X4\SaveViewer\Parser\SaveSelector;
use Mistralys\X4\SaveViewer\Traits\DebuggableInterface;
use Mistralys\X4\SaveViewer\Traits\DebuggableTrait;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../bootstrap.php';

abstract class X4ParserTestCase extends TestCase implements DebuggableInterface
{
    use DebuggableTrait;

    protected string $filesFolder;
    protected string $saveGameFile;

    protected function setUp() : void
    {
        parent::setUp();

        $this->filesFolder = __DIR__.'/../files';
        $this->saveGameFile = __DIR__.'/../files/quicksave.xml';

        $this->disableLogging();
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
        return SaveSelector::create(X4_SAVES_FOLDER, X4_STORAGE_FOLDER)
            ->setLoggingEnabled($this->isLoggingEnabled());
    }
}

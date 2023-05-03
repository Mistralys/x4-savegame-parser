<?php

declare(strict_types=1);

namespace X4\SaveGameParserTests\TestClasses;

use PHPUnit\Framework\TestCase;

abstract class X4ParserTestCase extends TestCase
{
    protected string $filesFolder;
    protected string $saveGameFile;

    protected function setUp() : void
    {
        parent::setUp();

        $this->filesFolder = __DIR__.'/../files';
        $this->saveGameFile = __DIR__.'/../files/quicksave.xml';
    }
}

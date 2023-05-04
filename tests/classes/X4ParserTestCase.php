<?php

declare(strict_types=1);

namespace X4\SaveGameParserTests\TestClasses;

use AppUtils\ClassHelper;
use PHPUnit\Framework\TestCase;

abstract class X4ParserTestCase extends TestCase
{
    protected string $filesFolder;
    protected string $saveGameFile;
    private bool $logging = false;
    private ?string $logPrefix = null;

    protected function setUp() : void
    {
        parent::setUp();

        $this->filesFolder = __DIR__.'/../files';
        $this->saveGameFile = __DIR__.'/../files/quicksave.xml';

        $this->disableLogging();
    }

    public function enableLogging() : void
    {
        $this->logging = true;
    }

    public function disableLogging() : void
    {
        $this->logging = false;
    }

    public function isLoggingEnabled() : bool
    {
        return $this->logging;
    }

    protected function log(string $message, ...$params) : void
    {
        if($this->logging === false) {
            return;
        }

        if(!isset($this->logPrefix)) {
            $this->logPrefix = sprintf(
                'Test [%s] | ',
                ClassHelper::getClassTypeName($this)
            );
        }

        if(empty($params)) {
            echo $this->logPrefix.$message.PHP_EOL;
            return;
        }

        echo sprintf(
            $this->logPrefix.$message.PHP_EOL,
            ...$params
        );
    }
}

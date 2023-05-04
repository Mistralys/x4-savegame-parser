<?php

declare(strict_types=1);

namespace X4\SaveGameParserTests\Tests;

use Mistralys\X4\SaveViewer\SaveParser;
use X4\SaveGameParserTests\TestClasses\X4ParserTestCase;

final class SplitFileTests extends X4ParserTestCase
{
     public function test_split() : void
     {
         $parser = new SaveParser($this->saveGameFile);
         $parser->setLoggingEnabled(true);
         $parser->setSaveGameFolder($this->filesFolder);
         $parser->processFile();

         $this->assertFileExists(dirname($this->saveGameFile));
     }

    public function test_postProcess() : void
    {
        $parser = new SaveParser($this->saveGameFile);
        $parser->setLoggingEnabled(true);
        $parser->setSaveGameFolder($this->filesFolder);
        $parser->postProcessFragments();

        $this->assertFileExists($parser->getOutputPath().'/analysis.json');
    }
}
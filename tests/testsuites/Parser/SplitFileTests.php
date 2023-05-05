<?php

declare(strict_types=1);

namespace X4\SaveGameParserTests\Tests;

use AppUtils\FileHelper;
use AppUtils\FileHelper\FileInfo;
use Mistralys\X4\SaveViewer\Parser\SaveSelector;
use Mistralys\X4\SaveViewer\SaveParser;
use X4\SaveGameParserTests\TestClasses\X4ParserTestCase;

final class SplitFileTests extends X4ParserTestCase
{
     public function test_split() : void
     {
         $parser = new SaveParser(FileInfo::factory($this->saveGameFile), $this->filesFolder);
         $parser->setLoggingEnabled(true);
         $parser->processFile();

         $this->assertFileExists($parser->getXMLFile());
     }

    public function test_postProcess() : void
    {
        $parser = new SaveParser(FileInfo::factory($this->saveGameFile), $this->filesFolder);
        $parser->setLoggingEnabled(true);
        $parser->postProcessFragments();

        $this->assertFileExists($parser->getOutputPath().'/analysis.json');
    }

    public function test_unpack_latest() : void
    {
        $this->enableLogging();

        $save = SaveSelector::create('J:\OneDrive\Documents\Egosoft\X4\70814229\save')
            ->setLoggingEnabled($this->isLoggingEnabled())
            ->requireMostRecent();

        $this->log('Detected most recent file: [%s].', $save->getName());

        if(!$save->isUnzipped())
        {
            $this->log('Unzipping the file.');
            $save->unzip();
        }

        $parser = SaveParser::create($save, $this->filesFolder)
            ->setAutoBackupEnabled()
            ->setLoggingEnabled($this->isLoggingEnabled())
            ->unpack();

        $this->assertTrue($parser->hasBackup());
    }
}

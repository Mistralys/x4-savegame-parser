<?php

declare(strict_types=1);

namespace X4\SaveGameParserTests\Tests;

use Mistralys\X4\SaveViewer\Parser\SaveSelector;
use Mistralys\X4\SaveViewer\SaveParser;
use X4\SaveGameParserTests\TestClasses\X4ParserTestCase;

final class SplitFileTests extends X4ParserTestCase
{
     public function test_split() : void
     {
         $save = $this->createSelector()->getSaveGameByName($this->saveGameFile);

         $parser = SaveParser::create($save)
            ->setLoggingEnabled(true)
            ->processFile();

         $this->assertFileExists($parser->getXMLFile());
     }

    public function test_postProcess() : void
    {
        $save = $this->createSelector()->getSaveGameByName($this->saveGameFile);

        $parser = SaveParser::create($save)
            ->setLoggingEnabled(true)
            ->postProcessFragments();

        $this->assertFileExists($parser->getOutputPath().'/analysis.json');
    }

    public function test_unpack_latest() : void
    {
        $save = $this->createSelector()->requireMostRecent();

        $this->log('Detected most recent file: [%s].', $save->getName());

        if(!$save->isUnzipped())
        {
            $this->log('Unzipping the file.');
            $save->unzip();
        }

        $parser = SaveParser::create($save)
            ->setAutoBackupEnabled()
            ->setLoggingEnabled($this->isLoggingEnabled())
            ->unpack();

        $this->assertTrue($parser->hasBackup());
    }
}

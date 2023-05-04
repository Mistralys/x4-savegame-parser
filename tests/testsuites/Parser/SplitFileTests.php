<?php

declare(strict_types=1);

namespace X4\SaveGameParserTests\Tests;

use AppUtils\FileHelper;
use AppUtils\FileHelper\FileInfo;
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

        $files = FileHelper::createFileFinder('J:\OneDrive\Documents\Egosoft\X4\70814229\save')
            ->includeExtensions(array('gz', 'xml'))
            ->setPathmodeAbsolute()
            ->getAll();

        usort($files, static function (string $a, string $b) : int {
            return filemtime($b) - filemtime($a);
        });

        $this->assertNotEmpty($files);

        $mostRecent = FileInfo::factory(array_shift($files));

        $this->log('Detected most recent file: [%s].', $mostRecent->getName());

        $modTime = $mostRecent->getModifiedDate();

        if($mostRecent->getExtension() === 'gz')
        {
            $this->log('File is zipped, unzipping.');

            $outFile = FileInfo::factory(str_replace('.xml.gz', '.xml', $mostRecent->getPath()));

            $this->unzip($mostRecent, $outFile);

            $mostRecent = $outFile;
        }

        $parser = new SaveParser($mostRecent, $this->filesFolder, $modTime);
        $parser->setLoggingEnabled($this->isLoggingEnabled());

        FileHelper::deleteTree($parser->getOutputPath());

        $parser->unpack();
    }

    private function unzip(FileInfo $sourceFile, FileInfo $targetFile) : void
    {
        $this->log('Unzipping file [%s].', $sourceFile->getName());

        // Raising this value may increase performance
        $buffer_size = 4096; // read 4kb at a time

        // Open our files (in binary mode)
        $file = gzopen($sourceFile->getPath(), 'rb');
        $out_file = fopen($targetFile->getPath(), 'wb');

        // Keep repeating until the end of the input file
        while(!gzeof($file)) {
            // Read buffer-size bytes
            // Both fwrite and gzread and binary-safe
            fwrite($out_file, gzread($file, $buffer_size));
        }

        // Files are done, close files
        fclose($out_file);
        gzclose($file);

        $this->log('Unzip complete.');
    }
}

<?php

declare(strict_types=1);

namespace X4\SaveGameParserTests\Tests;

use AppUtils\FileHelper;
use AppUtils\FileHelper\FileInfo;
use Mistralys\X4\SaveViewer\Parser\SaveSelector;
use Mistralys\X4\SaveViewer\Parser\SaveSelector\SaveGameFile;
use X4\SaveGameParserTests\TestClasses\X4ParserTestCase;

final class FileDetectionTests extends X4ParserTestCase
{
    // region: _Tests

    /**
     * Ensures that detecting savegame files works as
     * intended, recognizing pairs of GZ and XML files
     * as well as singletons.
     */
    public function test_loadFilesList() : void
    {
        $list = $this->createSelector()->compileFileInformation();

        $this->assertCount(3, $list);

        $this->assertArrayHasKey('1-archive-only', $list);
        $this->assertArrayHasKey('2-xml-only', $list);
        $this->assertArrayHasKey('3-both', $list);

        $this->assertInstanceOf(FileInfo::class, $list['1-archive-only']['gz']);
        $this->assertNull($list['1-archive-only']['xml']);

        $this->assertInstanceOf(FileInfo::class, $list['2-xml-only']['xml']);
        $this->assertNull($list['2-xml-only']['gz']);

        $this->assertInstanceOf(FileInfo::class, $list['3-both']['xml']);
        $this->assertInstanceOf(FileInfo::class, $list['3-both']['gz']);
    }

    /**
     * @see SaveSelector\SaveGameFile::resolveExtractionXMLFile()
     */
    public function test_savegameFileConfiguration() : void
    {
        $selector = $this->createSelector();

        $this->assertCount(3, $selector->getSaveGames());

        $archiveOnly = $selector->getSaveGameByFileName('1-archive-only.xml.gz');
        $this->assertFalse($archiveOnly->isUnzipped());
        $this->assertTrue($archiveOnly->getZipFile()->exists());
        $this->assertFalse($archiveOnly->getXMLFile()->exists());
        $this->assertSame(SaveGameFile::FILE_MODE_ZIP, $archiveOnly->getFileMode());

        $xmlOnly = $selector->getSaveGameByFileName('2-xml-only.xml');
        $this->assertTrue($xmlOnly->isUnzipped());
        $this->assertFalse($xmlOnly->getZipFile()->exists());
        $this->assertTrue($xmlOnly->getXMLFile()->exists());
        $this->assertSame(SaveGameFile::FILE_MODE_XML, $xmlOnly->getFileMode());

        // It is not considered unzipped, because when both
        // files exist, the XML file is ignored. As it cannot
        // be guaranteed that it is up-to-date with the archive,
        // a dynamically generated XML name is used.
        $both = $selector->getSaveGameByFileName('3-both.xml.gz');
        $this->assertFalse($both->isUnzipped());
        $this->assertSame(SaveGameFile::FILE_MODE_ZIP, $both->getFileMode());
        $this->assertTrue($both->getZipFile()->exists());
        $this->assertFalse(
            $both->getXMLFile()->exists(),
            'Must not exist yet, because it is not unzipped. '.
            'The XML file is the special extraction file used to avoid '.
            'conflicts with user-extracted XML files.'
        );
    }

    /**
     * The test file <code>save-files/source/3-both.xml</code> contains the
     * text "PlayerVersion". The XML file in the GZ archive contains the text
     * "GameVersion". When both the archive and XML file are present, the
     * archive file must take precedence, so it is unzipped and the archive
     * XML used instead of the existing XML file.
     */
    public function test_unzipWhenXMLAlreadyPresent() : void
    {
        $save = $this->createSelector()->getSaveGameByFileName('3-both.xml.gz');

        $this->assertFalse($save->isUnzipped());

        $save->unzip();

        $xmlFile = $save->getXMLFile();

        $this->assertNotNull($xmlFile);
        $this->assertTrue($xmlFile->exists());

        $this->assertStringContainsString('GameVersion', $xmlFile->getContents());
    }

    // endregion

    // region: Support methods

    private string $sourceFolder;
    private string $targetFolder;
    private string $storageFolder;

    protected function setUp() : void
    {
        parent::setUp();

        $baseFolder = __DIR__.'/../../files/save-files';
        $this->sourceFolder = $baseFolder.'/source';
        $this->targetFolder = $baseFolder.'/target';
        $this->storageFolder = $baseFolder.'/storage';

        FileHelper::deleteTree($this->targetFolder);

        FileHelper::copyTree($this->sourceFolder, $this->targetFolder);
    }

    protected function tearDown() : void
    {
        parent::tearDown();

        FileHelper::deleteTree($this->targetFolder);
    }

    public function createSelector() : SaveSelector
    {
        return SaveSelector::create(
            $this->targetFolder,
            $this->storageFolder
        );
    }

    // endregion
}

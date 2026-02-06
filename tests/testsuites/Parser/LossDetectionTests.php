<?php

declare(strict_types=1);

namespace X4\SaveGameParserTests\Tests;

use AppUtils\FileHelper\FolderInfo;
use AppUtils\FileHelper\JSONFile;
use Mistralys\X4\SaveViewer\Parser\Collections;
use Mistralys\X4\SaveViewer\Parser\DataProcessing\Processors\DetectShipLosses;
use Mistralys\X4\SaveViewer\Parser\Types\LogEntryType;
use X4\SaveGameParserTests\TestClasses\X4ParserTestCase;
use Mistralys\X4\SaveViewer\Config\Config;

final class LossDetectionTests extends X4ParserTestCase
{
    public function test_detectLoss() : void
    {
        $result = DetectShipLosses::parseTitle('ST+ TEL VTJ - Boa (LUD-229) was destroyed.');

        $this->assertNotNull($result);
        $this->assertSame('LUD-229', $result['shipCode']);
        $this->assertSame('ST+ TEL VTJ - Boa', $result['shipName']);
    }

    /**
     * Older savegames used a different format to define ship losses.
     */
    public function test_detectLossLegacy() : void
    {
        $result = DetectShipLosses::parseTitle('WGH Mercury Vanguard in sector The Void was destroyed by KHK Raiding Party Hive Guard.');

        $this->assertNotNull($result);
        $this->assertSame('', $result['shipCode']);
        $this->assertSame('WGH Mercury Vanguard', $result['shipName']);
        $this->assertSame('The Void', $result['location']);
        $this->assertSame('', $result['commander']);
        $this->assertSame('KHK Raiding Party Hive Guard', $result['destroyed by']);
    }

    public function test_parseText() : void
    {
        $result = DetectShipLosses::parseText("Location: Windfall IV Aurora's Dream[\\012]Commander: FO TEL Hull Parts Forge II (VTJ-380)[\\012]Destroyed by: FAF Marauder Dragon Raider (MIE-184)");

        $this->assertSame("Windfall IV Aurora's Dream", $result['location']);
        $this->assertSame("FO TEL Hull Parts Forge II (VTJ-380)", $result['commander']);
        $this->assertSame("FAF Marauder Dragon Raider (MIE-184)", $result['destroyed by']);
    }

    public function test_parseTextNoOriginator() : void
    {
        $result = DetectShipLosses::parseText("Location: Great Reef[\\012]Commander: FO BOR Quantum Tube Forge (WHS-258)");

        $this->assertSame("Great Reef", $result['location']);
        $this->assertEmpty($result['destroyed by']);
    }

    public function test_loadFromJSON() : void
    {
        $data = JSONFile::factory(__DIR__.'/../../files/test-saves/unpack-20260206211435-quicksave/JSON/data-losses.json')->parse();

        $this->assertCount(3, $data);

        $collections = new Collections(Config::getStorageFolder());
        $entry = new LogEntryType($collections, $data[0]);

        $info = DetectShipLosses::parseEntry($entry);

        $this->assertNotNull($info);
        $this->assertSame('BIE-447', $info['shipCode']);
        $this->assertSame('Aramean Shipyard TEL (IJU-471)', $info['commander']);
    }
}

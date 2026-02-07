<?php
/**
 * Script used to extract the savegame files from the
 * X4 savegame package, for use in the test suites.
 * The files are gzip compressed, so this script will
 * unpack and extract them for the tests to access the
 * extracted data.
 *
 * > NOTE: This is done to keep the file size to a minimum
 * > in the repository.
 */

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use AppUtils\FileHelper;
use AppUtils\FileHelper\FileInfo;
use AppUtils\FileHelper\FolderInfo;
use Mistralys\X4\SaveViewer\Config\Config;
use Mistralys\X4\SaveViewer\Data\SaveManager;
use Mistralys\X4\SaveViewer\SaveParser;

$savesFolder = FolderInfo::factory(__DIR__.'/../vendor/mistralys/x4-savegame/saves');
$storageFolder = FolderInfo::factory(__DIR__.'/files/test-saves');

// Configure for test environment
Config::setSavesFolder($savesFolder->getPath());
Config::setStorageFolder($storageFolder->getPath());
Config::setTestSuiteEnabled(true);

// Clean up the storage folder before extracting the test saves, to ensure a clean state for the tests
if($storageFolder->exists()) {
    FileHelper::deleteTree($storageFolder);
    $storageFolder->create();
}

$names = $savesFolder->createFileFinder()
    ->includeExtension('gz')
    ->makeRecursive()
    ->setPathmodeStrip()
    ->getMatches();

$manager = SaveManager::createFromConfig();

foreach($names as $saveName)
{
    $saveName = str_replace('.xml.gz', '', $saveName);

    $extractedXML = FileInfo::factory($savesFolder.'/'.$saveName.'.xml');

    // Clean up any existing extracted XML files for this save
    $extractedXML->delete();

    echo "=====================================================\n";
    echo "Looking for save: {$saveName}\n";

    if (!$manager->nameExists($saveName)) {
        echo "Error: Save '{$saveName}' not found in " . Config::getSavesFolder()->getPath() . "\n";
        echo "Available saves:\n";
        foreach ($manager->getSaves() as $save) {
            echo "  - " . $save->getSaveName() . "\n";
        }
        exit(1);
    }

    $save = $manager->getSaveByName($saveName);

    echo "- Found save.\n";

    if ($save->isUnpacked()) {
        echo "- Save is already unpacked.\n";
        continue;
    }

    echo "- Unpacking save...\n";

    $saveFile = $save->getSaveFile();

    // Unzip if needed
    if (!$saveFile->isUnzipped()) {
        echo "- Unzipping archive...\n";
        $saveFile->unzip();
    }

    // Use the same pattern as CLIHandler
    $parser = SaveParser::createFromMonitorConfig($save);
    $parser->optionKeepXML(true);

    echo "- Extracting XML fragments...\n";
    $parser->processFile();

    echo "- Analysing and writing JSON files...\n";
    $parser->postProcessFragments();

    echo "- Cleaning up...\n";
    $parser->cleanUp();
    $extractedXML->delete();

    echo "Done! Save extracted to: " . $save->getStorageFolder()->getPath() . "\n";
    echo "\n";
}

echo "Unit tests will now use the extracted savegame data.\n";
echo "\n";

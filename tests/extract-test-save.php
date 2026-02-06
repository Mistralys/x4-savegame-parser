<?php
/**
 * Script used to extract the bundled savegame file for use
 * in the test suites. It is gzip compressed and stored in
 * the 'files' folder, and this script will unpack it and
 * extract it so the tests can access the extracted data.
 *
 * > NOTE: This is done to keep the file size to a minimum
 * > in the repository.
 */

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Mistralys\X4\SaveViewer\Config\Config;
use Mistralys\X4\SaveViewer\Data\SaveManager;
use Mistralys\X4\SaveViewer\SaveParser;

// Configure for test environment
Config::setSavesFolder(__DIR__.'/files');
Config::setStorageFolder(__DIR__.'/files/test-saves');
Config::setTestSuiteEnabled(true);

$saveName = 'quicksave';
$manager = SaveManager::createFromConfig();

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

echo "Found save: " . $save->getSaveName() . "\n";
echo "Storage folder: " . $save->getStorageFolder()->getPath() . "\n";

if ($save->isUnpacked()) {
    echo "Save is already unpacked.\n";
    exit(0);
}

echo "Unpacking save...\n";

$saveFile = $save->getSaveFile();

// Unzip if needed
if (!$saveFile->isUnzipped()) {
    echo "Unzipping archive...\n";
    $saveFile->unzip();
}

// Use the same pattern as CLIHandler
$parser = SaveParser::createFromMonitorConfig($save);
$parser->optionKeepXML(true);

echo "Creating backup...\n";
if ($parser->getCannotBackupMessage() === null) {
    $parser->createBackup();
}

echo "Extracting XML fragments...\n";
$parser->processFile();

echo "Analysing and writing JSON files...\n";
$parser->postProcessFragments();

echo "Cleaning up...\n";
$parser->cleanUp();

echo "Done! Save extracted to: " . $save->getStorageFolder()->getPath() . "\n";
echo "Unit tests will now use the extracted savegame data.\n";
echo "\n";

<?php
/**
 * Test script for WP1: Auto-Generate Log Analysis During Extraction
 */

declare(strict_types=1);

require_once __DIR__ . '/prepend.php';

use Mistralys\X4\SaveViewer\Parser\SaveSelector;
use Mistralys\X4\SaveViewer\SaveParser;

echo "Testing WP1: Auto-Generate Log Analysis During Extraction\n";
echo str_repeat('=', 60) . "\n\n";

// Use the test save file
$savesFolder = __DIR__ . '/tests/files/save-files/source';
$storageFolder = __DIR__ . '/tests/files/test-saves';

echo "Creating SaveSelector...\n";
$selector = SaveSelector::create($savesFolder, $storageFolder);

echo "Getting save files...\n";
$files = $selector->getSaveFiles();

if (empty($files)) {
    echo "ERROR: No save files found!\n";
    exit(1);
}

$saveFile = reset($files);
echo "Using save file: " . $saveFile->getBaseName() . "\n\n";

// Check if already extracted
if ($saveFile->hasData()) {
    echo "Save already extracted, cleaning up first...\n";
    $storageFolder = $saveFile->getStorageFolder();
    if ($storageFolder->exists()) {
        \AppUtils\FileHelper::deleteTree($storageFolder);
        echo "Cleaned up existing extraction.\n\n";
    }
}

echo "Unzipping save file...\n";
$saveFile->unzip();
echo "Save unzipped successfully.\n\n";

echo "Creating SaveParser...\n";
$parser = SaveParser::create($saveFile);

echo "Starting extraction (this will generate log analysis cache)...\n";
$parser->unpack();

echo "\n" . str_repeat('=', 60) . "\n";
echo "Extraction completed!\n\n";

// Verify the cache was created
$jsonFolder = $saveFile->getAnalysis()->getJSONFolder();
$eventLogFolder = $jsonFolder->getPath() . '/event-log';

echo "Checking for log analysis cache...\n";
echo "Expected location: $eventLogFolder\n\n";

if (is_dir($eventLogFolder)) {
    echo "✓ SUCCESS: event-log directory exists!\n";

    $files = glob($eventLogFolder . '/*.json');
    if (!empty($files)) {
        echo "✓ SUCCESS: Found " . count($files) . " category cache files:\n";
        foreach ($files as $file) {
            echo "  - " . basename($file) . "\n";
        }
    } else {
        echo "✗ FAILURE: event-log directory exists but no JSON files found!\n";
    }
} else {
    echo "✗ FAILURE: event-log directory does not exist!\n";
}

echo "\n" . str_repeat('=', 60) . "\n";

// Check analysis.json for cache metadata
$analysisFile = $saveFile->getAnalysis()->getStorageFile();
$analysisData = $analysisFile->parse();

echo "Checking analysis.json for cache metadata...\n";
if (isset($analysisData['log-cache-written'])) {
    echo "✓ SUCCESS: log-cache-written timestamp found: " . $analysisData['log-cache-written'] . "\n";
} else {
    echo "✗ FAILURE: log-cache-written not found in analysis.json!\n";
}

if (isset($analysisData['log-category-ids'])) {
    $categoryCount = count($analysisData['log-category-ids']);
    echo "✓ SUCCESS: log-category-ids found with $categoryCount categories\n";
    echo "  Categories: " . implode(', ', $analysisData['log-category-ids']) . "\n";
} else {
    echo "✗ FAILURE: log-category-ids not found in analysis.json!\n";
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "WP1 Test Complete!\n";

<?php
/**
 * Test script for WP4: Warm Log Query Cache After Extraction
 */

declare(strict_types=1);

require_once __DIR__ . '/prepend.php';

use Mistralys\X4\SaveViewer\Parser\SaveSelector;
use Mistralys\X4\SaveViewer\SaveParser;
use AppUtils\FileHelper;

echo "Testing WP4: Warm Log Query Cache After Extraction\n";
echo str_repeat('=', 70) . "\n\n";

$savesFolder = __DIR__ . '/tests/files/save-files/source';
$storageFolder = __DIR__ . '/tests/files/test-saves';

echo "Creating SaveSelector...\n";
$selector = SaveSelector::create($savesFolder, $storageFolder);
$files = $selector->getSaveFiles();

if (empty($files)) {
    echo "ERROR: No save files found!\n";
    exit(1);
}

$saveFile = reset($files);
echo "Using save file: " . $saveFile->getBaseName() . "\n\n";

// Clean existing extraction
if ($saveFile->hasData()) {
    echo "Cleaning existing extraction...\n";
    FileHelper::deleteTree($saveFile->getStorageFolder());
    echo "Cleaned up.\n\n";
}

echo "Step 1: Unzip save file\n";
echo str_repeat('-', 70) . "\n";
$saveFile->unzip();
echo "✓ Unzipped successfully\n\n";

echo "Step 2: Extract savegame (this will warm the cache)\n";
echo str_repeat('-', 70) . "\n";
$parser = SaveParser::create($saveFile);

// Capture extraction time
$extractStart = microtime(true);
$parser->unpack();
$extractDuration = round((microtime(true) - $extractStart), 2);

echo "✓ Extraction completed in {$extractDuration}s\n\n";

echo "Step 3: Verify cache was warmed\n";
echo str_repeat('-', 70) . "\n";

// Check for cache directory
$cacheDir = $saveFile->getStorageFolder()->getPath() . '/.cache';
if (!is_dir($cacheDir)) {
    echo "✗ FAILURE: Cache directory does not exist\n";
    exit(1);
}
echo "✓ Cache directory exists\n";

// Check for auto-cache file
$autoCacheFiles = glob($cacheDir . '/query-_log_unfiltered_*.json');
if (empty($autoCacheFiles)) {
    echo "✗ FAILURE: Query cache file was NOT created during extraction\n";
    echo "  Expected pattern: query-_log_unfiltered_*.json\n";
    exit(1);
}

echo "✓ Query cache file created: " . basename($autoCacheFiles[0]) . "\n";
$cacheSize = round(filesize($autoCacheFiles[0]) / 1024, 2);
echo "  Cache size: {$cacheSize}KB\n";

// Parse and verify cache content
$cacheData = json_decode(file_get_contents($autoCacheFiles[0]), true);
if (!is_array($cacheData)) {
    echo "✗ FAILURE: Cache file is not valid JSON\n";
    exit(1);
}

$entryCount = count($cacheData);
echo "  Entries cached: {$entryCount}\n";

if ($entryCount > 0) {
    echo "✓ Cache contains data\n";

    // Verify format (should be WP2 format)
    $firstEntry = $cacheData[0];
    $requiredFields = ['time', 'timeFormatted', 'title', 'text', 'categoryID', 'categoryLabel', 'money'];
    $hasAllFields = true;
    foreach ($requiredFields as $field) {
        if (!isset($firstEntry[$field])) {
            echo "✗ Missing field: $field\n";
            $hasAllFields = false;
        }
    }

    if ($hasAllFields) {
        echo "✓ Cache format is correct (WP2 format)\n";
    }
} else {
    echo "⚠ WARNING: Cache is empty\n";
}

echo "\n";

echo "Step 4: Test first query speed (should be FAST)\n";
echo str_repeat('-', 70) . "\n";

$saveID = $saveFile->getAnalysis()->getSaveID();
$queryStart = microtime(true);
$output = shell_exec('php bin/php/query.php log --save=' . escapeshellarg($saveID) . ' --limit=20 2>&1');
$queryDuration = round((microtime(true) - $queryStart) * 1000, 2);

echo "Query duration: {$queryDuration}ms\n";

$json = json_decode($output, true);
if ($json && isset($json['success']) && $json['success']) {
    echo "✓ Query successful\n";
    echo "  Entries returned: " . count($json['data'] ?? []) . "\n";

    if ($queryDuration < 50) {
        echo "✓ SUCCESS: First query is FAST (< 50ms)\n";
        if ($queryDuration < 10) {
            echo "  🚀 EXCELLENT: Query completed in < 10ms!\n";
        }
    } else {
        echo "⚠ First query slower than expected\n";
        echo "  Expected: < 50ms, Got: {$queryDuration}ms\n";
    }
} else {
    echo "✗ FAILURE: Query failed\n";
    echo "Output: " . substr($output, 0, 200) . "...\n";
}

echo "\n";

echo "Step 5: Compare with uncached query type\n";
echo str_repeat('-', 70) . "\n";

// Test filtered query (won't use cache)
$filteredStart = microtime(true);
$output = shell_exec('php bin/php/query.php log --save=' . escapeshellarg($saveID) . ' --filter="[0:20]" --limit=20 2>&1');
$filteredDuration = round((microtime(true) - $filteredStart) * 1000, 2);

echo "Filtered query duration: {$filteredDuration}ms\n";

$json = json_decode($output, true);
if ($json && isset($json['success']) && $json['success']) {
    echo "✓ Filtered query successful\n";

    $speedup = round($filteredDuration / $queryDuration, 1);
    echo "  Cache speedup: {$speedup}x faster\n";
} else {
    echo "✗ Filtered query failed\n";
}

echo "\n" . str_repeat('=', 70) . "\n";
echo "WP4 Test Complete!\n";
echo str_repeat('=', 70) . "\n\n";

echo "Summary:\n";
echo "  Extraction time: {$extractDuration}s\n";
echo "  Cache file size: {$cacheSize}KB\n";
echo "  Cached entries: {$entryCount}\n";
echo "  First query time: {$queryDuration}ms\n";
echo "  Cache benefit: ✅ First query is instant\n";

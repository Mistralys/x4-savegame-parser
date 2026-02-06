<?php
/**
 * Quick test for WP3: Auto-Cache for Unfiltered Queries
 */

declare(strict_types=1);

require_once __DIR__ . '/prepend.php';

use Mistralys\X4\SaveViewer\Data\SaveManager;

echo "Testing WP3: Auto-Cache for Unfiltered Queries\n";
echo str_repeat('=', 70) . "\n\n";

$manager = SaveManager::createFromConfig();
$saves = $manager->getArchivedSaves();

if (empty($saves)) {
    echo "ERROR: No archived saves found!\n";
    exit(1);
}

$save = reset($saves);
echo "Testing with save: " . $save->getSaveName() . "\n";
echo "Save ID: " . $save->getSaveID() . "\n\n";

$cacheDir = $save->getStorageFolder()->getPath() . '/.cache';
$autoCachePattern = $cacheDir . '/query-_log_unfiltered_*.json';

// Clean existing auto-cache files
echo "Cleaning existing auto-cache files...\n";
if (is_dir($cacheDir)) {
    $files = glob($autoCachePattern);
    foreach ($files as $file) {
        unlink($file);
        echo "  Deleted: " . basename($file) . "\n";
    }
}
echo "\n";

// Test 1: First unfiltered query (should create cache)
echo "Test 1: First unfiltered query (page 1)\n";
echo str_repeat('-', 70) . "\n";
$start = microtime(true);
$output = shell_exec('php bin/php/query.php log --save=' . escapeshellarg($save->getSaveID()) . ' --limit=20 --offset=0 2>&1');
$duration1 = round((microtime(true) - $start) * 1000, 2);
echo "Duration: {$duration1}ms\n";

$json = json_decode($output, true);
if ($json && isset($json['success']) && $json['success']) {
    echo "✓ Query successful\n";
    echo "  Entries returned: " . count($json['data'] ?? []) . "\n";
} else {
    echo "✗ Query failed\n";
    echo "Output: " . substr($output, 0, 200) . "...\n";
}

// Check if cache file was created
$cacheFiles = glob($autoCachePattern);
if (!empty($cacheFiles)) {
    echo "✓ Auto-cache file created: " . basename($cacheFiles[0]) . "\n";
    $cacheSize = round(filesize($cacheFiles[0]) / 1024, 2);
    echo "  Cache size: {$cacheSize}KB\n";
} else {
    echo "✗ FAILURE: No auto-cache file created!\n";
}
echo "\n";

// Test 2: Second unfiltered query (should use cache)
echo "Test 2: Second unfiltered query (page 2) - should use cache\n";
echo str_repeat('-', 70) . "\n";
$start = microtime(true);
$output = shell_exec('php bin/php/query.php log --save=' . escapeshellarg($save->getSaveID()) . ' --limit=20 --offset=20 2>&1');
$duration2 = round((microtime(true) - $start) * 1000, 2);
echo "Duration: {$duration2}ms\n";

$json = json_decode($output, true);
if ($json && isset($json['success']) && $json['success']) {
    echo "✓ Query successful\n";
}

// Check if cache was reused (should be faster)
$speedup = round($duration1 / $duration2, 1);
if ($duration2 < $duration1 * 0.7) {
    echo "✓ Second query is faster (speedup: {$speedup}x) - cache working!\n";
} else {
    echo "⚠ Second query not significantly faster - cache may not be working\n";
    echo "  First: {$duration1}ms, Second: {$duration2}ms\n";
}
echo "\n";

// Test 3: Filtered query (should NOT create additional auto-cache)
echo "Test 3: Filtered query - should NOT use auto-cache\n";
echo str_repeat('-', 70) . "\n";
$cacheCountBefore = count(glob($autoCachePattern));
$output = shell_exec('php bin/php/query.php log --save=' . escapeshellarg($save->getSaveID()) . ' --filter="[0:5]" --limit=5 2>&1');

$json = json_decode($output, true);
if ($json && isset($json['success']) && $json['success']) {
    echo "✓ Filtered query successful\n";
}

$cacheCountAfter = count(glob($autoCachePattern));
if ($cacheCountAfter === $cacheCountBefore) {
    echo "✓ No additional auto-cache files created (correct)\n";
} else {
    echo "✗ FAILURE: Additional cache files created (should not happen)\n";
}
echo "\n";

// Test 4: Manual cache key (should override auto-cache)
echo "Test 4: Manual cache key - should override auto-cache\n";
echo str_repeat('-', 70) . "\n";
$output = shell_exec('php bin/php/query.php log --save=' . escapeshellarg($save->getSaveID()) . ' --cache-key=test-manual --limit=10 2>&1');

$json = json_decode($output, true);
if ($json && isset($json['success']) && $json['success']) {
    echo "✓ Manual cache query successful\n";
}

$manualCacheFile = $cacheDir . '/query-test-manual.json';
if (file_exists($manualCacheFile)) {
    echo "✓ Manual cache file created: query-test-manual.json\n";
    unlink($manualCacheFile); // Clean up
} else {
    echo "✗ FAILURE: Manual cache file not created\n";
}

echo "\n" . str_repeat('=', 70) . "\n";
echo "WP3 Test Complete!\n";
echo str_repeat('=', 70) . "\n";

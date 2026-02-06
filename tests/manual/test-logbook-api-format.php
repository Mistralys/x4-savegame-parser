<?php
/**
 * Test script for WP2: Switch CLI API to Use Cached Analysis Data
 */

declare(strict_types=1);

require_once __DIR__ . '/prepend.php';

use Mistralys\X4\SaveViewer\Data\SaveManager;

echo "Testing WP2: Switch CLI API to Use Cached Analysis Data\n";
echo str_repeat('=', 70) . "\n\n";

// Get test save
$manager = SaveManager::createFromConfig();
$saves = $manager->getArchivedSaves();

if (empty($saves)) {
    echo "ERROR: No archived saves found!\n";
    echo "Please run WP1 test first to extract a save.\n";
    exit(1);
}

$save = reset($saves);
echo "Testing with save: " . $save->getSaveName() . "\n";
echo "Save ID: " . $save->getSaveID() . "\n\n";

// Get log reader
$reader = $save->getDataReader();
$log = $reader->getLog();

echo "Checking cache validity...\n";
if ($log->isCacheValid()) {
    echo "✓ SUCCESS: Cache is valid\n";
} else {
    echo "⚠ WARNING: Cache is not valid, will be generated on first query\n";
}
echo "\n";

echo "Querying log data...\n";
$startTime = microtime(true);
$data = $log->toArrayForAPI();
$endTime = microtime(true);
$duration = round(($endTime - $startTime) * 1000, 2);

echo "Query completed in {$duration}ms\n";
echo "Total entries: " . count($data) . "\n\n";

if (empty($data)) {
    echo "WARNING: No log entries found in this save\n";
    exit(0);
}

echo str_repeat('=', 70) . "\n";
echo "Verifying Output Format\n";
echo str_repeat('=', 70) . "\n\n";

// Check first entry (newest)
echo "First Entry (Newest):\n";
echo str_repeat('-', 70) . "\n";
$first = $data[0];

$requiredFields = ['time', 'timeFormatted', 'title', 'text', 'categoryID', 'categoryLabel', 'money'];
$missingFields = [];
foreach ($requiredFields as $field) {
    if (!isset($first[$field])) {
        $missingFields[] = $field;
    }
}

if (empty($missingFields)) {
    echo "✓ SUCCESS: All required fields present\n";
} else {
    echo "✗ FAILURE: Missing fields: " . implode(', ', $missingFields) . "\n";
}

echo "\nField Values:\n";
echo "  time: " . $first['time'] . " (" . gettype($first['time']) . ")\n";
echo "  timeFormatted: " . $first['timeFormatted'] . "\n";
echo "  title: " . substr($first['title'], 0, 50) . "...\n";
echo "  categoryID: " . $first['categoryID'] . "\n";
echo "  categoryLabel: " . $first['categoryLabel'] . "\n";
echo "  money: " . $first['money'] . "\n\n";

// Check last entry (oldest)
echo "Last Entry (Oldest):\n";
echo str_repeat('-', 70) . "\n";
$last = end($data);
echo "  time: " . $last['time'] . "\n";
echo "  timeFormatted: " . $last['timeFormatted'] . "\n";
echo "  title: " . substr($last['title'], 0, 50) . "...\n\n";

// Verify descending order
echo str_repeat('=', 70) . "\n";
echo "Verifying Sort Order (Newest First)\n";
echo str_repeat('=', 70) . "\n\n";

$isDescending = true;
$previousTime = PHP_FLOAT_MAX;

for ($i = 0; $i < min(10, count($data)); $i++) {
    $currentTime = $data[$i]['time'];
    if ($currentTime > $previousTime) {
        $isDescending = false;
        echo "✗ FAILURE: Entry $i has time $currentTime > previous $previousTime\n";
        break;
    }
    $previousTime = $currentTime;
}

if ($isDescending) {
    echo "✓ SUCCESS: Entries are sorted in descending time order (newest first)\n";
    echo "  First entry time: " . $data[0]['time'] . "\n";
    echo "  Last entry time: " . $data[count($data)-1]['time'] . "\n";
} else {
    echo "✗ FAILURE: Entries are NOT sorted correctly\n";
}

echo "\n";

// Check for old 'category' field (should not exist)
echo str_repeat('=', 70) . "\n";
echo "Verifying Breaking Changes\n";
echo str_repeat('=', 70) . "\n\n";

if (isset($first['category'])) {
    echo "✗ FAILURE: Old 'category' field still present (should be removed)\n";
} else {
    echo "✓ SUCCESS: Old 'category' field removed\n";
}

if (isset($first['categoryID'])) {
    echo "✓ SUCCESS: New 'categoryID' field present\n";
} else {
    echo "✗ FAILURE: New 'categoryID' field missing\n";
}

if (isset($first['categoryLabel'])) {
    echo "✓ SUCCESS: New 'categoryLabel' field present\n";
} else {
    echo "✗ FAILURE: New 'categoryLabel' field missing\n";
}

if (isset($first['timeFormatted'])) {
    echo "✓ SUCCESS: New 'timeFormatted' field present\n";
} else {
    echo "✗ FAILURE: New 'timeFormatted' field missing\n";
}

echo "\n";

// Show category distribution
echo str_repeat('=', 70) . "\n";
echo "Category Distribution\n";
echo str_repeat('=', 70) . "\n\n";

$categoryCounts = [];
foreach ($data as $entry) {
    $catID = $entry['categoryID'];
    if (!isset($categoryCounts[$catID])) {
        $categoryCounts[$catID] = [
            'count' => 0,
            'label' => $entry['categoryLabel']
        ];
    }
    $categoryCounts[$catID]['count']++;
}

arsort($categoryCounts);
foreach ($categoryCounts as $catID => $info) {
    echo sprintf("  %-20s %-25s %5d entries\n", $catID, $info['label'], $info['count']);
}

echo "\n" . str_repeat('=', 70) . "\n";
echo "WP2 Test Complete!\n";
echo str_repeat('=', 70) . "\n";

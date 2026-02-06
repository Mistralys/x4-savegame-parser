<?php
/**
 * Quick validation test for WP2
 */

declare(strict_types=1);

require_once __DIR__ . '/prepend.php';

use Mistralys\X4\SaveViewer\Data\SaveManager;

// Get test save
$manager = SaveManager::createFromConfig();
$saves = $manager->getArchivedSaves();

if (empty($saves)) {
    echo "ERROR: No archived saves\n";
    exit(1);
}

$save = reset($saves);
$reader = $save->getDataReader();
$log = $reader->getLog();

echo "Getting log data...\n";
$data = $log->toArrayForAPI();

echo "Entries: " . count($data) . "\n";

if (!empty($data)) {
    $first = $data[0];
    echo "\nFirst entry fields:\n";
    foreach ($first as $key => $value) {
        if (is_string($value) && strlen($value) > 50) {
            $value = substr($value, 0, 47) . '...';
        }
        echo "  $key: $value\n";
    }

    echo "\nNew fields check:\n";
    echo "  categoryID: " . (isset($first['categoryID']) ? '✓' : '✗') . "\n";
    echo "  categoryLabel: " . (isset($first['categoryLabel']) ? '✓' : '✗') . "\n";
    echo "  timeFormatted: " . (isset($first['timeFormatted']) ? '✓' : '✗') . "\n";
    echo "  Old 'category': " . (isset($first['category']) ? '✗ (should not exist)' : '✓') . "\n";
}

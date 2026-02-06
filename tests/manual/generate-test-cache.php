<?php
/**
 * Generate cache for test save
 */

declare(strict_types=1);

require_once __DIR__ . '/prepend.php';

use Mistralys\X4\SaveViewer\Data\SaveManager;

echo "Generating cache for test save...\n";

$manager = SaveManager::createFromConfig();
$saves = $manager->getArchivedSaves();

if (empty($saves)) {
    echo "ERROR: No archived saves found!\n";
    exit(1);
}

$save = reset($saves);
echo "Using save: " . $save->getSaveName() . "\n";

$reader = $save->getDataReader();
$log = $reader->getLog();

if ($log->isCacheValid()) {
    echo "Cache already valid, skipping generation.\n";
} else {
    echo "Generating cache...\n";
    $log->generateAnalysisCache();
    echo "Cache generated successfully!\n";
}

// Verify
$cacheInfo = $log->getCacheInfo();
$date = $cacheInfo->getCacheDate();
if ($date !== null) {
    echo "Cache date: " . $date->getISODate() . "\n";
}

$categories = $cacheInfo->getCategoryIDs();
echo "Categories: " . implode(', ', $categories) . "\n";

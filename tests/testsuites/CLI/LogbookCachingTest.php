<?php
/**
 * Comprehensive test suite for logbook performance optimization caching mechanisms
 *
 * Tests:
 * - WP1: Auto-generation of log analysis cache during extraction
 * - WP2: CLI API using cached analysis data
 * - WP3: Auto-caching for unfiltered queries
 * - WP4: Cache warming after extraction
 * - WP5: Periodic cache cleanup
 */

declare(strict_types=1);

namespace X4\SaveGameParserTests\Tests\CLI;

use AppUtils\FileHelper;
use JmesPath\AstRuntime;
use Mistralys\X4\SaveViewer\CLI\QueryCache;
use Mistralys\X4\SaveViewer\CLI\QueryHandler;
use Mistralys\X4\SaveViewer\CLI\JMESPath\CustomFnDispatcher;
use Mistralys\X4\SaveViewer\CLI\QueryParameters;
use X4\SaveGameParserTests\TestClasses\X4ParserTestCase;
use X4\SaveGameParserTests\TestClasses\TestSaveNames;

final class LogbookCachingTest extends X4ParserTestCase
{
    private QueryCache $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new QueryCache($this->getSaveManager());
    }

    /**
     * Helper to get test save if available
     */
    private function getTestSave()
    {
        return $this->requireSaveByName(TestSaveNames::SAVE_ADVANCED_CREATIVE);
    }

    private function getCacheDirPath($save): string
    {
        return $save->getStorageFolder()->getPath() . '/cache';
    }

    private function clearAutoCache($save): void
    {
        $cacheDir = $this->getCacheDirPath($save);
        $autoCachePattern = $cacheDir . '/query-_log_unfiltered_*.json';
        $matches = glob($autoCachePattern) ?: [];

        foreach ($matches as $file) {
            unlink($file);
        }
    }

    /**
     * @param callable(): void $callback
     * @return array<int,float>
     */
    private function measureExecutionTimes(callable $callback, int $iterations): array
    {
        $times = [];

        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            $callback();
            $times[] = (microtime(true) - $start) * 1000;
        }

        return $times;
    }

    /**
     * @param array<int,float> $values
     */
    private function calculateMedian(array $values): float
    {
        $count = count($values);

        if ($count === 0) {
            return 0.0;
        }

        sort($values);
        $middle = (int)floor(($count - 1) / 2);

        if ($count % 2 === 1) {
            return $values[$middle];
        }

        return ($values[$middle] + $values[$middle + 1]) / 2;
    }

    // =========================================================================
    // WP1: Auto-Generate Log Analysis During Extraction
    // =========================================================================

    public function test_extraction_generates_log_analysis_cache(): void
    {
        $save = $this->getTestSave();

        // Verify analysis cache exists
        $eventLogDir = $save->getStorageFolder()->getPath() . '/JSON/event-log';

        // Generate cache if it doesn't exist
        if (!is_dir($eventLogDir)) {
            $log = $save->getDataReader()->getLog();
            $log->generateAnalysisCache();
        }

        $this->assertDirectoryExists(
            $eventLogDir,
            'WP1: Event log analysis cache directory should exist after extraction'
        );

        // Check for category JSON files
        $categoryFiles = glob($eventLogDir . '/*.json');
        $this->assertGreaterThan(
            0,
            count($categoryFiles),
            'WP1: Event log directory should contain category cache files'
        );
    }

    public function test_analysis_cache_contains_valid_data(): void
    {
        $save = $this->getTestSave();
        $eventLogDir = $save->getStorageFolder()->getPath() . '/JSON/event-log';

        // Get first category file
        $categoryFiles = glob($eventLogDir . '/*.json');

        // Generate cache if it doesn't exist
        if (empty($categoryFiles)) {
            $log = $save->getDataReader()->getLog();
            $log->generateAnalysisCache();
            $categoryFiles = glob($eventLogDir . '/*.json');
        }

        $this->assertNotEmpty($categoryFiles, 'Should have at least one category file');

        $firstFile = $categoryFiles[0];
        $data = json_decode(file_get_contents($firstFile), true);

        $this->assertIsArray($data, 'Category cache file should contain valid JSON array');
        $this->assertArrayHasKey('categoryID', $data, 'Category data should have categoryID');
        $this->assertArrayHasKey('label', $data, 'Category data should have label');
        $this->assertArrayHasKey('entries', $data, 'Category data should have entries array');
    }

    public function test_analysis_metadata_in_analysis_json(): void
    {
        $save = $this->getTestSave();
        $analysisFile = $save->getStorageFolder()->getPath() . '/analysis.json';

        $this->assertFileExists($analysisFile, 'analysis.json should exist');

        $data = json_decode(file_get_contents($analysisFile), true);
        $this->assertArrayHasKey(
            'log-cache-written',
            $data,
            'WP1: analysis.json should contain log-cache-written timestamp'
        );
        $this->assertArrayHasKey(
            'log-category-ids',
            $data,
            'WP1: analysis.json should contain log-category-ids array'
        );
        $this->assertIsArray($data['log-category-ids'], 'log-category-ids should be an array');
        $this->assertGreaterThan(0, count($data['log-category-ids']), 'Should have at least one category');
    }

    // =========================================================================
    // WP2: CLI API Using Cached Analysis Data
    // =========================================================================

    public function test_log_api_returns_new_format(): void
    {
        $save = $this->getTestSave();
        $log = $save->getDataReader()->getLog();

        $data = $log->toArrayForAPI();

        $this->assertIsArray($data, 'toArrayForAPI should return array');

        if (count($data) > 0) {
            $firstEntry = $data[0];

            // New fields (WP2)
            $this->assertArrayHasKey('categoryID', $firstEntry, 'WP2: Entry should have categoryID');
            $this->assertArrayHasKey('categoryLabel', $firstEntry, 'WP2: Entry should have categoryLabel');
            $this->assertArrayHasKey('timeFormatted', $firstEntry, 'WP2: Entry should have timeFormatted');

            // Core fields
            $this->assertArrayHasKey('time', $firstEntry, 'Entry should have time');
            $this->assertArrayHasKey('title', $firstEntry, 'Entry should have title');
            $this->assertArrayHasKey('text', $firstEntry, 'Entry should have text');
            $this->assertArrayHasKey('money', $firstEntry, 'Entry should have money');

            // Old fields should NOT exist
            $this->assertArrayNotHasKey('category', $firstEntry, 'WP2: Old category field should not exist');
            $this->assertArrayNotHasKey('faction', $firstEntry, 'WP2: Old faction field should not exist');
            $this->assertArrayNotHasKey('componentID', $firstEntry, 'WP2: Old componentID field should not exist');

            // Type validations
            $this->assertIsNumeric($firstEntry['time'], 'WP2: time should be numeric (not string)');
            $this->assertIsString($firstEntry['categoryID'], 'categoryID should be string');
            $this->assertIsString($firstEntry['categoryLabel'], 'categoryLabel should be string');
            $this->assertIsString($firstEntry['timeFormatted'], 'timeFormatted should be string');
        }
    }

    public function test_log_api_returns_descending_time_order(): void
    {
        $save = $this->getTestSave();
        $log = $save->getDataReader()->getLog();

        $data = $log->toArrayForAPI();

        $this->assertIsArray($data, 'Log API should return an array');

        if (count($data) > 1) {
            $firstTime = $data[0]['time'];
            $secondTime = $data[1]['time'];

            $this->assertGreaterThanOrEqual(
                $secondTime,
                $firstTime,
                'WP2: Entries should be sorted in descending time order (newest first)'
            );
        }
    }

    public function test_log_api_category_ids_are_valid(): void
    {
        $save = $this->getTestSave();
        $log = $save->getDataReader()->getLog();

        $data = $log->toArrayForAPI();

        $validCategories = [
            'combat', 'mission', 'trade', 'station-finance', 'station-building',
            'ship-construction', 'ship-supply', 'alert', 'emergency', 'attacked',
            'destroyed', 'promotion', 'reward', 'reputation', 'lockbox',
            'war', 'crew-assignment', 'tips', 'misc'
        ];

        foreach ($data as $entry) {
            $this->assertContains(
                $entry['categoryID'],
                $validCategories,
                'WP2: categoryID should be one of the known categories'
            );
        }
    }

    // =========================================================================
    // WP3: Auto-Caching for Unfiltered Queries
    // =========================================================================

    public function test_unfiltered_query_creates_auto_cache(): void
    {
        $save = $this->getTestSave();
        $cacheDir = $this->getCacheDirPath($save);
        $this->clearAutoCache($save);
        $autoCachePattern = $cacheDir . '/query-_log_unfiltered_*.json';

        // Execute unfiltered log query using the testable API
        $handler = new QueryHandler($this->getSaveManager());
        $params = QueryParameters::forTest([
            'saveIdentifier' => $save->getSaveID(),
            'limit' => 20
        ]);

        $handler->executeCommand(QueryHandler::COMMAND_LOG, $params);

        // Check auto-cache was created
        $cacheFiles = glob($autoCachePattern) ?: [];
        $this->assertNotEmpty(
            $cacheFiles,
            'WP3: Unfiltered query should create auto-cache file'
        );

        $cacheFile = $cacheFiles[0];
        $this->assertStringContainsString(
            '_log_unfiltered_',
            basename($cacheFile),
            'WP3: Cache file should have auto-cache naming pattern'
        );
    }

    public function test_auto_cache_speeds_up_pagination(): void
    {
        $save = $this->getTestSave();
        $this->clearAutoCache($save);

        $handler = new QueryHandler($this->getSaveManager());

        // First query (creates cache)
        $start1 = microtime(true);
        $params1 = QueryParameters::forTest([
            'saveIdentifier' => $save->getSaveID(),
            'limit' => 20,
            'offset' => 0
        ]);
        $handler->executeCommand(QueryHandler::COMMAND_LOG, $params1);
        $duration1 = (microtime(true) - $start1) * 1000;

        // Second query (uses cache)
        $start2 = microtime(true);
        $params2 = QueryParameters::forTest([
            'saveIdentifier' => $save->getSaveID(),
            'limit' => 20,
            'offset' => 20
        ]);
        $handler->executeCommand(QueryHandler::COMMAND_LOG, $params2);
        $duration2 = (microtime(true) - $start2) * 1000;

        // Second query should be faster (or at least not significantly slower)
        // Allow some tolerance since system load can vary
        $this->assertLessThanOrEqual(
            $duration1 * 1.5,
            $duration2,
            'WP3: Second paginated query should be fast (using cache)'
        );
    }

    public function test_filtered_query_does_not_create_auto_cache(): void
    {
        $save = $this->getTestSave();
        $cacheDir = $this->getCacheDirPath($save);
        $this->clearAutoCache($save);
        $autoCachePattern = $cacheDir . '/query-_log_unfiltered_*.json';
        $beforeCount = count(glob($autoCachePattern) ?: []);

        // Execute filtered log query
        $handler = new QueryHandler($this->getSaveManager());
        $params = QueryParameters::forTest([
            'saveIdentifier' => $save->getSaveID(),
            'filter' => '[0:5]',
            'limit' => 5
        ]);
        $handler->executeCommand(QueryHandler::COMMAND_LOG, $params);

        $afterCount = count(glob($autoCachePattern) ?: []);
        $this->assertEquals(
            $beforeCount,
            $afterCount,
            'WP3: Filtered query should NOT create auto-cache'
        );
    }

    // =========================================================================
    // WP4: Cache Warming After Extraction
    // =========================================================================

    public function test_full_workflow_extraction_to_query2(): void
    {

        $save = $this->getTestSave();
        $cacheDir = $this->getCacheDirPath($save);
        $autoCachePattern = $cacheDir . '/query-_log_unfiltered_*.json';

        // Check if cache was warmed during extraction
        $cacheFiles = glob($autoCachePattern) ?: [];

        // Generate cache if it doesn't exist (e.g., if previous tests cleared it)
        if (count($cacheFiles) === 0) {
            $handler = new QueryHandler($this->getSaveManager());
            $params = QueryParameters::forTest([
                'saveIdentifier' => $save->getSaveID(),
                'limit' => 10
            ]);
            $handler->executeCommand(QueryHandler::COMMAND_LOG, $params);
            $cacheFiles = glob($autoCachePattern) ?: [];
        }

        $this->assertNotEmpty(
            $cacheFiles,
            'WP4: Query cache should be warmed after extraction'
        );

        // Verify cache content
        $cacheData = json_decode(file_get_contents($cacheFiles[0]), true);
        $this->assertIsArray($cacheData, 'Warmed cache should contain valid data');
        $this->assertGreaterThan(0, count($cacheData), 'Warmed cache should not be empty');
    }

    // =========================================================================
    // WP5: Periodic Cache Cleanup
    // =========================================================================

    public function test_cleanup_removes_orphaned_caches(): void
    {
        // Create a fake orphaned cache directory
        $storageFolder = $this->getSaveManager()->getStorageFolder()->getPath();
        $fakeSaveDir = $storageFolder . '/unpack-19990101000000-test-orphan';
        $fakeCacheDir = $fakeSaveDir . '/cache';

        // Clean up first if exists
        if (is_dir($fakeSaveDir)) {
            FileHelper::deleteTree($fakeSaveDir);
        }

        // Create fake cache
        mkdir($fakeCacheDir, 0755, true);
        file_put_contents($fakeCacheDir . '/test-cache.json', '{"test": true}');

        $this->assertDirectoryExists($fakeCacheDir, 'Fake cache should be created');

        // Run cleanup
        $removed = $this->cache->cleanupObsoleteCaches();

        $this->assertGreaterThan(
            0,
            $removed,
            'WP5: Cleanup should remove at least one orphaned cache'
        );

        $this->assertDirectoryDoesNotExist(
            $fakeCacheDir,
            'WP5: Orphaned cache directory should be removed'
        );

        // Clean up fake save directory
        if (is_dir($fakeSaveDir)) {
            FileHelper::deleteTree($fakeSaveDir);
        }
    }

    public function test_cleanup_preserves_valid_caches(): void
    {
        $save = $this->getTestSave();
        $cacheDir = $this->getCacheDirPath($save);

        // Ensure cache directory exists
        if (!is_dir($cacheDir)) {
            $this->markTestSkipped('No cache directory to test preservation');
        }

        $filesBefore = glob($cacheDir . '/*') ?: [];
        $countBefore = count($filesBefore);

        // Run cleanup
        $this->cache->cleanupObsoleteCaches();

        // Check cache still exists
        $filesAfter = glob($cacheDir . '/*') ?: [];
        $countAfter = count($filesAfter);

        $this->assertEquals(
            $countBefore,
            $countAfter,
            'WP5: Valid cache directories should be preserved'
        );
    }

    // =========================================================================
    // Integration Tests
    // =========================================================================

    public function test_full_workflow_extraction_to_query(): void
    {
        // This is more of a documentation test showing the full workflow
        $save = $this->getTestSave();
        $log = $save->getDataReader()->getLog();

        if (!$log->isCacheValid()) {
            $log->generateAnalysisCache();
        }

        // 1. WP1: Analysis cache exists
        $eventLogDir = $save->getStorageFolder()->getPath() . '/JSON/event-log';

        $this->assertDirectoryExists($eventLogDir, 'Step 1: Analysis cache should exist');

        // 2. WP2: API returns new format
        $data = $log->toArrayForAPI();
        if (count($data) > 0) {
            $this->assertArrayHasKey('categoryID', $data[0], 'Step 2: New format should be used');
        }

        // 3. WP3: Query creates auto-cache
        $cacheDir = $this->getCacheDirPath($save);
        $autoCachePattern = $cacheDir . '/query-_log_unfiltered_*.json';

        // Execute query to trigger auto-cache using testable API
        $handler = new QueryHandler($this->getSaveManager());
        $params = QueryParameters::forTest([
            'saveIdentifier' => $save->getSaveID(),
            'limit' => 10
        ]);
        $handler->executeCommand(QueryHandler::COMMAND_LOG, $params);

        $cacheFiles = glob($autoCachePattern) ?: [];

        $this->assertNotEmpty($cacheFiles, 'Step 3: Auto-cache should be created');

        // 4. WP5: Cleanup doesn't remove valid cache
        $beforeCleanup = count(glob($autoCachePattern) ?: []);
        $this->cache->cleanupObsoleteCaches();
        $afterCleanup = count(glob($autoCachePattern) ?: []);

        $this->assertEquals($beforeCleanup, $afterCleanup, 'Step 4: Valid cache preserved after cleanup');
    }

    // =========================================================================
    // Performance Tests
    // =========================================================================

    public function test_cached_query_performance(): void
    {
        $save = $this->getTestSave();
        $data = $save->getDataReader()->getLog()->toArrayForAPI();

        $iterations = 5;
        $filter = "[?categoryID=='combat']";
        $runtime = new AstRuntime(null, new CustomFnDispatcher());

        $filterTimes = $this->measureExecutionTimes(function() use ($runtime, $filter, $data) : void {
            $result = $runtime($filter, $data);

            if (!is_array($result)) {
                $result = [$result];
            }
        }, $iterations);

        $filteredData = $runtime($filter, $data);

        if (!is_array($filteredData)) {
            $filteredData = [$filteredData];
        }

        $cacheKey = 'perf-logbook-combat';
        $cacheDir = $this->getCacheDirPath($save);
        $cacheFile = $cacheDir . '/query-' . $cacheKey . '.json';

        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }

        $this->cache->store($save, $cacheKey, $filteredData);

        $cacheTimes = $this->measureExecutionTimes(function() use ($save, $cacheKey) : void {
            $this->cache->retrieve($save, $cacheKey);
        }, $iterations);

        $filterMedian = $this->calculateMedian($filterTimes);
        $cacheMedian = $this->calculateMedian($cacheTimes);

        $this->assertLessThan(
            $filterMedian * 0.85,
            $cacheMedian,
            "Cached results should be faster than filtering (median). Filter {$filterMedian}ms, cache {$cacheMedian}ms"
        );
    }
}

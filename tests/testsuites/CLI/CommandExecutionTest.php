<?php
/**
 * Tests for QueryHandler command execution.
 *
 * These tests verify that CLI commands execute properly, including
 * error handling, filtering, pagination, and caching.
 *
 * @package X4SaveViewer
 * @subpackage Tests
 */

declare(strict_types=1);

namespace testsuites\CLI;

use Mistralys\X4\SaveViewer\CLI\QueryHandler;
use Mistralys\X4\SaveViewer\CLI\QueryValidationException;
use Mistralys\X4\SaveViewer\Data\SaveManager;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CommandExecutionTest extends TestCase
{
    private const TEST_SAVE_NAME = 'unpack-20230524120000-quicksave';

    private ?SaveManager $manager = null;
    private ?QueryHandler $handler = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = SaveManager::createFromConfig();
        $this->handler = new QueryHandler($this->manager);
    }

    protected function tearDown(): void
    {
        $this->manager = null;
        $this->handler = null;
        parent::tearDown();
    }

    /**
     * Helper method to simulate CLI arguments
     */
    private function simulateCLIArguments(array $args): void
    {
        $_SERVER['argv'] = array_merge(['query.php'], $args);
    }

    /**
     * Helper method to call private methods
     */
    private function callPrivateMethod(object $object, string $methodName, array $args = []): mixed
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invoke($object, ...$args);
    }

    /**
     * Helper to get test save if available
     */
    private function getTestSave()
    {
        if (!$this->manager->nameExists(self::TEST_SAVE_NAME)) {
            $this->markTestSkipped('Test save not found');
        }

        $save = $this->manager->getSaveByName(self::TEST_SAVE_NAME);

        if (!$save->isUnpacked()) {
            $this->markTestSkipped('Test save not unpacked');
        }

        return $save;
    }

    // =========================================================================
    // Test: Command Execution with Valid Save
    // =========================================================================

    public function test_queryCommand_shipsWithValidSave(): void
    {
        $save = $this->getTestSave();

        // Capture output
        ob_start();

        try {
            $this->simulateCLIArguments(['ships', '--save=' . self::TEST_SAVE_NAME]);
            $this->handler->handle();
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertNotEmpty($output, 'Ships command should produce output');

        $json = json_decode($output, true);
        $this->assertIsArray($json, 'Output should be valid JSON');
        $this->assertTrue($json['success'] ?? false, 'Response should indicate success');
        $this->assertEquals('ships', $json['command'] ?? '', 'Command should be "ships"');
        $this->assertArrayHasKey('data', $json, 'Response should have data field');
    }

    public function test_queryCommand_stationsWithValidSave(): void
    {
        $save = $this->getTestSave();

        ob_start();

        try {
            $this->simulateCLIArguments(['stations', '--save=' . self::TEST_SAVE_NAME]);
            $this->handler->handle();
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $json = json_decode($output, true);
        $this->assertIsArray($json);
        $this->assertTrue($json['success'] ?? false);
        $this->assertEquals('stations', $json['command'] ?? '');
    }

    public function test_queryCommand_playerWithValidSave(): void
    {
        $save = $this->getTestSave();

        ob_start();

        try {
            $this->simulateCLIArguments(['player', '--save=' . self::TEST_SAVE_NAME]);
            $this->handler->handle();
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $json = json_decode($output, true);
        $this->assertIsArray($json);
        $this->assertTrue($json['success'] ?? false);
        $this->assertEquals('player', $json['command'] ?? '');
        $this->assertNotEmpty($json['data'] ?? [], 'Player data should not be empty');
    }

    // =========================================================================
    // Test: Error Handling - Missing Save Parameter
    // =========================================================================

    public function test_queryCommand_withoutSaveParameter_showsError(): void
    {
        ob_start();

        try {
            $this->simulateCLIArguments(['ships']); // No --save parameter
            $this->handler->handle();
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $json = json_decode($output, true);
        $this->assertIsArray($json);
        $this->assertFalse($json['success'] ?? true, 'Response should indicate failure');
        $this->assertStringContainsString('save', strtolower($json['message'] ?? ''), 'Error should mention save parameter');
    }

    // =========================================================================
    // Test: Error Handling - Non-Existent Save
    // =========================================================================

    public function test_queryCommand_withNonExistentSave_showsError(): void
    {
        ob_start();

        try {
            $this->simulateCLIArguments(['ships', '--save=nonexistent-save-xyz']);
            $this->handler->handle();
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $json = json_decode($output, true);
        $this->assertIsArray($json);
        $this->assertFalse($json['success'] ?? true, 'Response should indicate failure');
        $this->assertStringContainsString('not found', strtolower($json['message'] ?? ''), 'Error should mention save not found');
    }

    // =========================================================================
    // Test: Error Handling - Non-Extracted Save
    // =========================================================================

    public function test_queryCommand_withNonExtractedSave_showsError(): void
    {
        // Find a save that exists but is not extracted
        $saves = $this->manager->getSaves();
        $nonExtractedSave = null;

        foreach ($saves as $save) {
            if (!$save->isUnpacked()) {
                $nonExtractedSave = $save;
                break;
            }
        }

        if ($nonExtractedSave === null) {
            $this->markTestSkipped('No non-extracted saves available for testing');
        }

        ob_start();

        try {
            $this->simulateCLIArguments(['ships', '--save=' . $nonExtractedSave->getSaveName()]);
            $this->handler->handle();
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $json = json_decode($output, true);
        $this->assertIsArray($json);
        $this->assertFalse($json['success'] ?? true, 'Response should indicate failure');
        $this->assertStringContainsString('extract', strtolower($json['message'] ?? ''), 'Error should mention extraction');
    }

    // =========================================================================
    // Test: Error Handling - Invalid Command
    // =========================================================================

    public function test_queryCommand_withInvalidCommand_showsError(): void
    {
        ob_start();

        try {
            $this->simulateCLIArguments(['invalid-command-xyz', '--save=' . self::TEST_SAVE_NAME]);
            $this->handler->handle();
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $json = json_decode($output, true);
        $this->assertIsArray($json);
        $this->assertFalse($json['success'] ?? true, 'Response should indicate failure');
        $this->assertStringContainsString('unknown command', strtolower($json['message'] ?? ''), 'Error should mention unknown command');
    }

    // =========================================================================
    // Test: Filtering with JMESPath
    // =========================================================================

    public function test_queryCommand_withJMESPathFilter(): void
    {
        $save = $this->getTestSave();

        ob_start();

        try {
            // Use a simple filter that always matches (e.g., filter to get first element)
            $this->simulateCLIArguments([
                'ships',
                '--save=' . self::TEST_SAVE_NAME,
                '--filter=[0]', // Get first item
                '--limit=1'
            ]);
            $this->handler->handle();
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $json = json_decode($output, true);
        $this->assertIsArray($json);
        $this->assertTrue($json['success'] ?? false, 'Filtered query should succeed');

        // If data is not empty, it should be filtered
        if (!empty($json['data'])) {
            $this->assertIsArray($json['data'], 'Filtered data should be an array');
        }
    }

    public function test_queryCommand_withInvalidFilter_showsError(): void
    {
        $save = $this->getTestSave();

        ob_start();

        try {
            // Use invalid JMESPath syntax
            $this->simulateCLIArguments([
                'ships',
                '--save=' . self::TEST_SAVE_NAME,
                '--filter=[invalid syntax'
            ]);
            $this->handler->handle();
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            // JMESPath syntax errors may throw exceptions
            $this->assertStringContainsString('syntax', strtolower($e->getMessage()));
            return;
        }

        // If no exception, check for error response
        $json = json_decode($output, true);
        $this->assertIsArray($json);
        // Filter errors might be caught and returned as error response
        if (isset($json['success'])) {
            $this->assertFalse($json['success'], 'Invalid filter should cause error');
        }
    }

    // =========================================================================
    // Test: Pagination
    // =========================================================================

    public function test_queryCommand_withLimitAndOffset(): void
    {
        $save = $this->getTestSave();

        ob_start();

        try {
            $this->simulateCLIArguments([
                'ships',
                '--save=' . self::TEST_SAVE_NAME,
                '--limit=5',
                '--offset=0'
            ]);
            $this->handler->handle();
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $json = json_decode($output, true);
        $this->assertIsArray($json);
        $this->assertTrue($json['success'] ?? false);
        $this->assertArrayHasKey('pagination', $json, 'Response should include pagination metadata');

        $pagination = $json['pagination'];
        $this->assertEquals(5, $pagination['limit'] ?? 0, 'Limit should be 5');
        $this->assertEquals(0, $pagination['offset'] ?? -1, 'Offset should be 0');
        $this->assertArrayHasKey('total', $pagination, 'Pagination should include total');
        $this->assertArrayHasKey('hasMore', $pagination, 'Pagination should include hasMore');

        // Verify data doesn't exceed limit
        if (!empty($json['data'])) {
            $this->assertLessThanOrEqual(5, count($json['data']), 'Data should not exceed limit');
        }
    }

    public function test_queryCommand_paginationMetadata(): void
    {
        $save = $this->getTestSave();

        ob_start();

        try {
            $this->simulateCLIArguments([
                'ships',
                '--save=' . self::TEST_SAVE_NAME,
                '--limit=3'
            ]);
            $this->handler->handle();
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $json = json_decode($output, true);
        $pagination = $json['pagination'] ?? [];

        $this->assertArrayHasKey('total', $pagination);
        $this->assertArrayHasKey('limit', $pagination);
        $this->assertArrayHasKey('offset', $pagination);
        $this->assertArrayHasKey('hasMore', $pagination);

        // hasMore should be true if total > limit + offset
        $total = $pagination['total'];
        $limit = $pagination['limit'];
        $offset = $pagination['offset'];
        $expectedHasMore = ($offset + $limit) < $total;

        $this->assertEquals($expectedHasMore, $pagination['hasMore'], 'hasMore should be calculated correctly');
    }

    // =========================================================================
    // Test: Caching
    // =========================================================================

    public function test_queryCommand_withCacheKey_storesResult(): void
    {
        $save = $this->getTestSave();
        $cacheKey = 'test-cache-' . time();

        ob_start();

        try {
            $this->simulateCLIArguments([
                'ships',
                '--save=' . self::TEST_SAVE_NAME,
                '--filter=[0:5]', // Filter to get first 5 items
                '--cache-key=' . $cacheKey
            ]);
            $this->handler->handle();
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $json = json_decode($output, true);
        $this->assertTrue($json['success'] ?? false, 'Query with cache key should succeed');

        // Note: We can't directly verify cache was stored without accessing cache internals,
        // but the command should execute without errors
        $this->assertIsArray($json['data']);
    }

    public function test_queryCommand_withCacheKey_retrievesCached(): void
    {
        $save = $this->getTestSave();
        $cacheKey = 'test-cache-retrieve-' . time();

        // First request - stores in cache
        ob_start();
        try {
            $this->simulateCLIArguments([
                'ships',
                '--save=' . self::TEST_SAVE_NAME,
                '--filter=[0:3]',
                '--cache-key=' . $cacheKey
            ]);
            $this->handler->handle();
            $firstOutput = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $firstJson = json_decode($firstOutput, true);
        $this->assertTrue($firstJson['success'] ?? false);

        // Second request - should retrieve from cache
        ob_start();
        try {
            $this->simulateCLIArguments([
                'ships',
                '--save=' . self::TEST_SAVE_NAME,
                '--filter=[0:3]',
                '--cache-key=' . $cacheKey
            ]);
            $this->handler->handle();
            $secondOutput = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $secondJson = json_decode($secondOutput, true);
        $this->assertTrue($secondJson['success'] ?? false);

        // Results should be identical (cached)
        $this->assertEquals($firstJson['data'], $secondJson['data'], 'Cached results should match original');
    }

    // =========================================================================
    // Test: Special Commands
    // =========================================================================

    public function test_queryCommand_listSaves_noSaveRequired(): void
    {
        ob_start();

        try {
            $this->simulateCLIArguments(['list-saves']);
            $this->handler->handle();
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $json = json_decode($output, true);
        $this->assertIsArray($json);
        $this->assertTrue($json['success'] ?? false, 'list-saves should succeed without --save parameter');
        $this->assertArrayHasKey('main', $json['data'] ?? []);
        $this->assertArrayHasKey('archived', $json['data'] ?? []);
    }

    public function test_queryCommand_clearCache_noSaveRequired(): void
    {
        ob_start();

        try {
            $this->simulateCLIArguments(['clear-cache']);
            $this->handler->handle();
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $json = json_decode($output, true);
        $this->assertIsArray($json);
        $this->assertTrue($json['success'] ?? false, 'clear-cache should succeed');
        $this->assertArrayHasKey('cleared', $json['data'] ?? [], 'Response should include cleared count');
    }
}

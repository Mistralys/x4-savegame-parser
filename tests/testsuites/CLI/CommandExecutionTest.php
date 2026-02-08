<?php
/**
 * Tests for QueryHandler command execution.
 *
 * These tests verify that CLI commands execute properly, including
 * error handling, filtering, pagination, and caching.
 *
 * Tests use QueryParameters directly to bypass league/climate argument parsing,
 * allowing comprehensive testing without CLI simulation limitations.
 *
 * @package X4SaveViewer
 * @subpackage Tests
 */

declare(strict_types=1);

namespace testsuites\CLI;

use Mistralys\X4\SaveViewer\CLI\QueryHandler;
use Mistralys\X4\SaveViewer\CLI\QueryParameters;
use X4\SaveGameParserTests\TestClasses\X4ParserTestCase;
use X4\SaveGameParserTests\TestClasses\TestSaveNames;
use ReflectionClass;

class CommandExecutionTest extends X4ParserTestCase
{
    private ?QueryHandler $handler = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new QueryHandler($this->getSaveManager());
    }

    protected function tearDown(): void
    {
        $this->handler = null;
        parent::tearDown();
    }

    /**
     * Helper method to execute a command with parameters and return decoded JSON.
     *
     * @param string $command The command to execute
     * @param array<string, mixed> $params Optional parameter overrides
     * @return array Decoded JSON response
     */
    private function executeCommand(string $command, array $params = []): array
    {
        $queryParams = QueryParameters::forTest($params);
        $output = $this->handler->executeCommand($command, $queryParams);
        return json_decode($output, true);
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
        return $this->requireSaveByName(TestSaveNames::SAVE_ADVANCED_CREATIVE);
    }

    /**
     * Helper to get test save identifier for use in commands.
     * Returns save ID for archived saves (which CLI supports).
     */
    private function getTestSaveName(): string
    {
        return $this->getTestSave()->getSaveID();
    }

    // =========================================================================
    // Test: Command Execution with Valid Save
    // =========================================================================

    public function test_queryCommand_shipsWithValidSave(): void
    {
        $save = $this->getTestSave();

        $json = $this->executeCommand('ships', [
            'saveIdentifier' => $this->getTestSaveName()
        ]);

        $this->assertIsArray($json);
        $this->assertTrue($json['success'] ?? false, 'Ships command should succeed');
        $this->assertIsArray($json['data'] ?? null, 'Ships data should be an array');
        $this->assertNotEmpty($json['data'], 'Ships data should not be empty');
    }

    public function test_queryCommand_stationsWithValidSave(): void
    {
        $save = $this->getTestSave();

        $json = $this->executeCommand('stations', [
            'saveIdentifier' => $this->getTestSaveName()
        ]);

        $this->assertIsArray($json);
        $this->assertTrue($json['success'] ?? false);
        $this->assertEquals('stations', $json['command'] ?? '');
    }

    public function test_queryCommand_playerWithValidSave(): void
    {
        $save = $this->getTestSave();

        $json = $this->executeCommand('player', [
            'saveIdentifier' => $this->getTestSaveName()
        ]);

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
        $this->expectException(\Mistralys\X4\SaveViewer\CLI\QueryValidationException::class);
        $this->expectExceptionMessage('save parameter is required');

        $this->executeCommand('ships'); // No save parameter
    }

    // =========================================================================
    // Test: Error Handling - Non-Existent Save
    // =========================================================================

    public function test_queryCommand_withNonExistentSave_showsError(): void
    {
        $this->expectException(\Mistralys\X4\SaveViewer\CLI\QueryValidationException::class);
        $this->expectExceptionMessage('not found');

        $this->executeCommand('ships', [
            'saveIdentifier' => 'nonexistent-save-xyz'
        ]);
    }

    // =========================================================================
    // Test: Error Handling - Non-Extracted Save
    // =========================================================================

    public function test_queryCommand_withNonExtractedSave_showsError(): void
    {
        // Find a save that exists but is not extracted
        $saves = $this->getSaveManager()->getSaves();
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

        $this->expectException(\Mistralys\X4\SaveViewer\CLI\QueryValidationException::class);
        $this->expectExceptionMessage('not extracted');

        $this->executeCommand('ships', [
            'saveIdentifier' => $nonExtractedSave->getSaveName()
        ]);
    }

    // =========================================================================
    // Test: Error Handling - Invalid Command
    // =========================================================================

    public function test_queryCommand_withInvalidCommand_showsError(): void
    {
        $this->expectException(\Mistralys\X4\SaveViewer\CLI\QueryValidationException::class);
        $this->expectExceptionMessage('Unknown command');

        $this->executeCommand('invalid-command-xyz', [
            'saveIdentifier' => $this->getTestSaveName()
        ]);
    }

    // =========================================================================
    // Test: Filtering with JMESPath
    // =========================================================================

    public function test_queryCommand_withJMESPathFilter(): void
    {
        $save = $this->getTestSave();

        $json = $this->executeCommand('ships', [
            'saveIdentifier' => $this->getTestSaveName(),
            'filter' => '[0]', // Get first item
            'limit' => 1
        ]);

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

        $this->expectException(\JmesPath\SyntaxErrorException::class);

        $this->executeCommand('ships', [
            'saveIdentifier' => $this->getTestSaveName(),
            'filter' => '[invalid syntax'
        ]);
    }

    // =========================================================================
    // Test: Pagination
    // =========================================================================

    public function test_queryCommand_withLimitAndOffset(): void
    {
        $save = $this->getTestSave();

        $json = $this->executeCommand('ships', [
            'saveIdentifier' => $this->getTestSaveName(),
            'limit' => 5,
            'offset' => 0
        ]);

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

        $json = $this->executeCommand('ships', [
            'saveIdentifier' => $this->getTestSaveName(),
            'limit' => 3
        ]);

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

        $json = $this->executeCommand('ships', [
            'saveIdentifier' => $this->getTestSaveName(),
            'filter' => '[0:5]', // Filter to get first 5 items
            'cacheKey' => $cacheKey
        ]);

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
        $firstJson = $this->executeCommand('ships', [
            'saveIdentifier' => $this->getTestSaveName(),
            'filter' => '[0:3]',
            'cacheKey' => $cacheKey
        ]);

        $this->assertTrue($firstJson['success'] ?? false);

        // Second request - should retrieve from cache
        $secondJson = $this->executeCommand('ships', [
            'saveIdentifier' => $this->getTestSaveName(),
            'filter' => '[0:3]',
            'cacheKey' => $cacheKey
        ]);

        $this->assertTrue($secondJson['success'] ?? false);

        // Results should be identical (cached)
        $this->assertEquals($firstJson['data'], $secondJson['data'], 'Cached results should match original');
    }

    // =========================================================================
    // Test: Special Commands
    // =========================================================================

    public function test_queryCommand_listSaves_noSaveRequired(): void
    {
        $json = $this->executeCommand('list-saves');

        $this->assertIsArray($json);
        $this->assertTrue($json['success'] ?? false, 'list-saves should succeed without --save parameter');
        $this->assertArrayHasKey('main', $json['data'] ?? []);
        $this->assertArrayHasKey('archived', $json['data'] ?? []);
    }

    public function test_queryCommand_clearCache_noSaveRequired(): void
    {
        $json = $this->executeCommand('clear-cache');

        $this->assertIsArray($json);
        $this->assertTrue($json['success'] ?? false, 'clear-cache should succeed');
        $this->assertArrayHasKey('cleared', $json['data'] ?? [], 'Response should include cleared count');
    }

    // =========================================================================
    // Test: Custom JMESPath Functions - Case-Insensitive Search
    // =========================================================================

    public function test_queryCommand_withContainsICaseInsensitive(): void
    {
        $save = $this->getTestSave();

        $json = $this->executeCommand('ships', [
            'saveIdentifier' => $this->getTestSaveName(),
            'filter' => '[?contains_i(name, \'scout\')]',
            'limit' => 5
        ]);

        $this->assertIsArray($json);
        $this->assertTrue($json['success'] ?? false);
        $this->assertIsArray($json['data'] ?? null);

        // Verify all results contain 'scout' (case-insensitive)
        foreach ($json['data'] as $ship) {
            $this->assertIsArray($ship);
            $this->assertArrayHasKey('name', $ship);
            $name = strtolower($ship['name']);
            $this->assertStringContainsString('scout', $name,
                "Ship name '{$ship['name']}' should contain 'scout' (case-insensitive)");
        }
    }

    public function test_queryCommand_withStartsWithICaseInsensitive(): void
    {
        $save = $this->getTestSave();

        $json = $this->executeCommand('ships', [
            'saveIdentifier' => $this->getTestSaveName(),
            'filter' => '[?starts_with_i(name, \'argon\')]',
            'limit' => 5
        ]);

        $this->assertIsArray($json);
        $this->assertTrue($json['success'] ?? false);
        $this->assertIsArray($json['data'] ?? null);

        // Verify all results start with 'argon' (case-insensitive)
        foreach ($json['data'] as $ship) {
            $this->assertIsArray($ship);
            $this->assertArrayHasKey('name', $ship);
            $name = strtolower($ship['name']);
            $this->assertStringStartsWith('argon', $name,
                "Ship name '{$ship['name']}' should start with 'argon' (case-insensitive)");
        }
    }

    public function test_queryCommand_withEndsWithICaseInsensitive(): void
    {
        $save = $this->getTestSave();

        $json = $this->executeCommand('ships', [
            'saveIdentifier' => $this->getTestSaveName(),
            'filter' => '[?ends_with_i(name, \'mk2\')]',
            'limit' => 5
        ]);

        $this->assertIsArray($json);
        $this->assertTrue($json['success'] ?? false);
        $this->assertIsArray($json['data'] ?? null);

        // Verify all results end with 'mk2' (case-insensitive)
        foreach ($json['data'] as $ship) {
            $this->assertIsArray($ship);
            $this->assertArrayHasKey('name', $ship);
            $name = strtolower($ship['name']);
            $this->assertStringEndsWith('mk2', $name,
                "Ship name '{$ship['name']}' should end with 'mk2' (case-insensitive)");
        }
    }

    public function test_queryCommand_withToLowerAndContains(): void
    {
        $save = $this->getTestSave();

        $json = $this->executeCommand('ships', [
            'saveIdentifier' => $this->getTestSaveName(),
            'filter' => '[?contains(to_lower(name), \'fighter\')]',
            'limit' => 5
        ]);

        $this->assertIsArray($json);
        $this->assertTrue($json['success'] ?? false);
        $this->assertIsArray($json['data'] ?? null);

        // Verify all results contain 'fighter'
        foreach ($json['data'] as $ship) {
            $this->assertIsArray($ship);
            $this->assertArrayHasKey('name', $ship);
            $name = strtolower($ship['name']);
            $this->assertStringContainsString('fighter', $name,
                "Ship name '{$ship['name']}' should contain 'fighter'");
        }
    }

    public function test_queryCommand_withChainedFiltersPerformancePattern(): void
    {
        $save = $this->getTestSave();

        $json = $this->executeCommand('ships', [
            'saveIdentifier' => $this->getTestSaveName(),
            'filter' => '[?faction==\'argon\'] | [?contains_i(name, \'scout\')]',
            'limit' => 10
        ]);

        $this->assertIsArray($json);
        $this->assertTrue($json['success'] ?? false);
        $this->assertIsArray($json['data'] ?? null);

        // Verify all results are Argon ships with 'scout' in name
        foreach ($json['data'] as $ship) {
            $this->assertIsArray($ship);
            $this->assertArrayHasKey('name', $ship);
            $this->assertArrayHasKey('faction', $ship);
            $this->assertSame('argon', $ship['faction'],
                "Ship should be from Argon faction");
            $name = strtolower($ship['name']);
            $this->assertStringContainsString('scout', $name,
                "Ship name '{$ship['name']}' should contain 'scout'");
        }
    }

    public function test_queryCommand_withMultipleCaseInsensitiveConditions(): void
    {
        $save = $this->getTestSave();

        $json = $this->executeCommand('ships', [
            'saveIdentifier' => $this->getTestSaveName(),
            'filter' => '[?starts_with_i(name, \'argon\') && contains_i(name, \'scout\')]',
            'limit' => 5
        ]);

        $this->assertIsArray($json);
        $this->assertTrue($json['success'] ?? false);
        $this->assertIsArray($json['data'] ?? null);

        // Verify all results start with 'argon' AND contain 'scout'
        foreach ($json['data'] as $ship) {
            $this->assertIsArray($ship);
            $this->assertArrayHasKey('name', $ship);
            $name = strtolower($ship['name']);
            $this->assertStringStartsWith('argon', $name,
                "Ship name '{$ship['name']}' should start with 'argon'");
            $this->assertStringContainsString('scout', $name,
                "Ship name '{$ship['name']}' should contain 'scout'");
        }
    }

    public function test_queryCommand_withStationsCommand(): void
    {
        $save = $this->getTestSave();

        $json = $this->executeCommand('stations', [
            'saveIdentifier' => $this->getTestSaveName(),
            'filter' => '[?contains_i(name, \'trading\')]',
            'limit' => 5
        ]);

        $this->assertIsArray($json);
        $this->assertTrue($json['success'] ?? false);
        $this->assertIsArray($json['data'] ?? null);

        // Verify results contain 'trading' (case-insensitive)
        foreach ($json['data'] as $station) {
            $this->assertIsArray($station);
            $this->assertArrayHasKey('name', $station);
            $name = strtolower($station['name']);
            $this->assertStringContainsString('trading', $name,
                "Station name '{$station['name']}' should contain 'trading'");
        }
    }
}


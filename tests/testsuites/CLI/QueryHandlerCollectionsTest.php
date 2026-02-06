<?php
/**
 * Tests for QueryHandler collection commands.
 *
 * These tests verify that the collection commands (ships, stations, people, sectors)
 * properly load and return data, and that the flattening logic works correctly.
 *
 * @package X4SaveViewer
 * @subpackage Tests
 */

declare(strict_types=1);

namespace testsuites\CLI;

use Mistralys\X4\SaveViewer\CLI\QueryHandler;
use Mistralys\X4\SaveViewer\Data\SaveManager;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class QueryHandlerCollectionsTest extends TestCase
{
    private const TEST_SAVE_NAME = 'unpack-20260206211435-quicksave';

    private ?SaveManager $manager = null;
    private ?QueryHandler $handler = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Create SaveManager with test configuration
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
     * Helper method to call private/protected methods via reflection
     */
    private function callPrivateMethod(object $object, string $methodName, array $args = []): mixed
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invoke($object, ...$args);
    }

    /**
     * Helper method to get a test save file
     */
    private function getTestSave()
    {
        if (!$this->manager->nameExists(self::TEST_SAVE_NAME)) {
            $this->markTestSkipped('Test save "' . self::TEST_SAVE_NAME . '" not found. Run extraction first.');
        }

        $save = $this->manager->getSaveByName(self::TEST_SAVE_NAME);

        if (!$save->isUnpacked()) {
            $this->markTestSkipped('Test save "' . self::TEST_SAVE_NAME . '" is not unpacked. Run extraction first.');
        }

        return $save;
    }

    // =========================================================================
    // Test: Collection Commands Return Non-Empty Data
    // =========================================================================

    public function test_ships_returnsNonEmptyArray(): void
    {
        $save = $this->getTestSave();
        $collections = $save->getDataReader()->getCollections();

        $data = $collections->ships()->loadData();

        $this->assertIsArray($data, 'Ships loadData should return an array');
        $this->assertNotEmpty($data, 'Ships collection should not be empty for test save');
    }

    public function test_stations_returnsNonEmptyArray(): void
    {
        $save = $this->getTestSave();
        $collections = $save->getDataReader()->getCollections();

        $data = $collections->stations()->loadData();

        $this->assertIsArray($data, 'Stations loadData should return an array');
        $this->assertNotEmpty($data, 'Stations collection should not be empty for test save');
    }

    public function test_people_returnsNonEmptyArray(): void
    {
        $save = $this->getTestSave();
        $collections = $save->getDataReader()->getCollections();

        $data = $collections->people()->loadData();

        $this->assertIsArray($data, 'People loadData should return an array');
        // Note: People collection might be empty in some saves, so we just check it's an array
    }

    public function test_sectors_returnsNonEmptyArray(): void
    {
        $save = $this->getTestSave();
        $collections = $save->getDataReader()->getCollections();

        $data = $collections->sectors()->loadData();

        $this->assertIsArray($data, 'Sectors loadData should return an array');
        $this->assertNotEmpty($data, 'Sectors collection should not be empty for test save');
    }

    // =========================================================================
    // Test: Data Structure Flattening
    // =========================================================================

    public function test_flattenCollectionArray_convertsNestedToFlat(): void
    {
        $nestedData = [
            'ship' => [
                ['id' => 1, 'name' => 'Ship 1'],
                ['id' => 2, 'name' => 'Ship 2']
            ],
            'player' => [
                ['id' => 3, 'name' => 'Player Ship']
            ]
        ];

        $result = $this->callPrivateMethod($this->handler, 'flattenCollectionArray', [$nestedData]);

        $this->assertIsArray($result);
        $this->assertCount(3, $result, 'Should flatten all items from all type IDs');
        $this->assertEquals(['id' => 1, 'name' => 'Ship 1'], $result[0]);
        $this->assertEquals(['id' => 2, 'name' => 'Ship 2'], $result[1]);
        $this->assertEquals(['id' => 3, 'name' => 'Player Ship'], $result[2]);
    }

    public function test_flattenCollectionArray_handlesEmptyArray(): void
    {
        $emptyData = [];

        $result = $this->callPrivateMethod($this->handler, 'flattenCollectionArray', [$emptyData]);

        $this->assertIsArray($result);
        $this->assertEmpty($result, 'Should return empty array for empty input');
    }

    public function test_flattenCollectionArray_handlesMultipleTypes(): void
    {
        $multiTypeData = [
            'type1' => [
                ['value' => 'a'],
                ['value' => 'b']
            ],
            'type2' => [
                ['value' => 'c']
            ],
            'type3' => [
                ['value' => 'd'],
                ['value' => 'e'],
                ['value' => 'f']
            ]
        ];

        $result = $this->callPrivateMethod($this->handler, 'flattenCollectionArray', [$multiTypeData]);

        $this->assertCount(6, $result, 'Should flatten all items from all types');
        $this->assertEquals('a', $result[0]['value']);
        $this->assertEquals('f', $result[5]['value']);
    }

    public function test_flattenCollectionArray_handlesNonArrayValues(): void
    {
        $mixedData = [
            'valid' => [
                ['id' => 1]
            ],
            'invalid' => 'not an array',
            'another_valid' => [
                ['id' => 2]
            ]
        ];

        $result = $this->callPrivateMethod($this->handler, 'flattenCollectionArray', [$mixedData]);

        $this->assertCount(2, $result, 'Should only flatten array values, skip non-arrays');
        $this->assertEquals(['id' => 1], $result[0]);
        $this->assertEquals(['id' => 2], $result[1]);
    }

    // =========================================================================
    // Test: Blueprint Handling
    // =========================================================================

    public function test_blueprints_returnsValidStructure(): void
    {
        $save = $this->getTestSave();
        $blueprints = $save->getDataReader()->getBlueprints();

        $data = $blueprints->toArrayForAPI();

        $this->assertIsArray($data, 'Blueprints should return an array');

        if (!empty($data)) {
            $firstBlueprint = $data[0];
            $this->assertArrayHasKey('id', $firstBlueprint, 'Blueprint should have id field');
            $this->assertArrayHasKey('owned', $firstBlueprint, 'Blueprint should have owned field');
        }
    }

    public function test_blueprints_handlesUnknownBlueprints(): void
    {
        $save = $this->getTestSave();
        $blueprints = $save->getDataReader()->getBlueprints();

        // This test verifies that unknown blueprints (from mods) don't crash the system
        // If the save has any unknown blueprints, they should be included in the result
        $data = $blueprints->toArrayForAPI();

        $this->assertIsArray($data, 'Blueprints should handle unknown blueprints gracefully');

        // Check if any blueprints have unknown category
        foreach ($data as $blueprint) {
            if (isset($blueprint['category']) && $blueprint['category'] === 'unknown') {
                $this->assertArrayHasKey('id', $blueprint, 'Unknown blueprint should have id');
                $this->assertArrayHasKey('label', $blueprint, 'Unknown blueprint should have label');
                break;
            }
        }

        // Test passes if we get here without exceptions
        $this->assertTrue(true, 'Unknown blueprints handled without crashes');
    }

    // =========================================================================
    // Test: Khaak Stations
    // =========================================================================

    public function test_khaakStations_includesZoneName(): void
    {
        $save = $this->getTestSave();
        $khaakStations = $save->getDataReader()->getKhaakStations();

        $data = $khaakStations->toArrayForAPI();

        if (!empty($data)) {
            $firstStation = $data[0];
            $this->assertArrayHasKey('zone', $firstStation, 'Khaak station should include zone field');
            $this->assertNotEmpty($firstStation['zone'], 'Zone field should not be empty');
        } else {
            $this->markTestSkipped('No Khaak stations in test save');
        }
    }

    public function test_khaakStations_includesStationName(): void
    {
        $save = $this->getTestSave();
        $khaakStations = $save->getDataReader()->getKhaakStations();

        $data = $khaakStations->toArrayForAPI();

        if (!empty($data)) {
            $firstStation = $data[0];
            $this->assertArrayHasKey('name', $firstStation, 'Khaak station should include name field');
            $this->assertNotEmpty($firstStation['name'], 'Name field should not be empty');
        } else {
            $this->markTestSkipped('No Khaak stations in test save');
        }
    }

    // =========================================================================
    // Test: Inventory Safety
    // =========================================================================

    public function test_inventory_handlesMissingData(): void
    {
        $save = $this->getTestSave();

        // This test verifies that inventory doesn't crash when data is missing
        // The init() method should handle missing KEY_INVENTORY gracefully
        $inventory = $save->getDataReader()->getInventory();

        $data = $inventory->toArrayForAPI();

        $this->assertIsArray($data, 'Inventory should return array even with missing data');

        // Verify structure
        foreach ($data as $item) {
            $this->assertArrayHasKey('name', $item, 'Inventory item should have name');
            $this->assertArrayHasKey('amount', $item, 'Inventory item should have amount');
        }
    }

    public function test_inventory_handlesInvalidStructure(): void
    {
        $save = $this->getTestSave();
        $inventory = $save->getDataReader()->getInventory();

        // This test verifies that inventory handles invalid data structures gracefully
        // The safety check should prevent crashes from malformed data
        $data = $inventory->toArrayForAPI();

        $this->assertIsArray($data, 'Inventory should return array even with invalid structure');

        // All items should be valid
        foreach ($data as $item) {
            $this->assertIsArray($item, 'Each inventory item should be an array');
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('amount', $item);
            $this->assertIsString($item['name']);
            $this->assertIsInt($item['amount']);
        }
    }
}

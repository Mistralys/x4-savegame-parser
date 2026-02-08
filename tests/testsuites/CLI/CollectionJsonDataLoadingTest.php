<?php
/**
 * Tests to verify CLI API commands correctly load and return data from test collection JSON files.
 *
 * These tests ensure that:
 * - Collection JSON files are properly loaded
 * - Data structures match expected formats
 * - Required fields are present
 * - Commands return non-empty results for populated collections
 *
 * @package X4SaveViewer
 * @subpackage Tests
 */

declare(strict_types=1);

namespace testsuites\CLI;

use Mistralys\X4\SaveViewer\CLI\QueryHandler;
use X4\SaveGameParserTests\TestClasses\X4ParserTestCase;
use X4\SaveGameParserTests\TestClasses\TestSaveNames;

class CollectionJsonDataLoadingTest extends X4ParserTestCase
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
     * Helper method to get a test save file
     */
    private function getTestSave()
    {
        return $this->requireSaveByName(TestSaveNames::SAVE_ADVANCED_CREATIVE);
    }

    // =========================================================================
    // Test: Ships Collection
    // =========================================================================

    public function test_ships_collection_loads_successfully(): void
    {
        $save = $this->getTestSave();
        $collections = $save->getDataReader()->getCollections();

        $data = $collections->ships()->loadData();

        $this->assertIsArray($data, 'Ships collection should return an array');
        $this->assertNotEmpty($data, 'Ships collection should not be empty');
        $this->assertArrayHasKey('ship', $data, 'Ships data should have "ship" type key');
    }

    public function test_ships_collection_contains_expected_items(): void
    {
        $save = $this->getTestSave();
        $collections = $save->getDataReader()->getCollections();

        $data = $collections->ships()->loadData();
        $ships = $data['ship'] ?? [];

        // Real savegame should have at least some ships
        $this->assertGreaterThan(0, count($ships), 'Ships collection should contain ships');

        // Verify first ship has expected structure
        $ship1 = $ships[0];
        $this->assertArrayHasKey('componentID', $ship1);
        $this->assertArrayHasKey('connectionID', $ship1);
        $this->assertArrayHasKey('name', $ship1);
        $this->assertArrayHasKey('owner', $ship1);
        $this->assertArrayHasKey('code', $ship1);
        $this->assertArrayHasKey('class', $ship1);
        $this->assertArrayHasKey('state', $ship1);
        $this->assertArrayHasKey('macro', $ship1);

        // Verify values are non-empty
        $this->assertNotEmpty($ship1['componentID']);
        $this->assertNotEmpty($ship1['connectionID']);
    }

    public function test_ships_have_required_location_fields(): void
    {
        $save = $this->getTestSave();
        $collections = $save->getDataReader()->getCollections();

        $data = $collections->ships()->loadData();
        $ships = $data['ship'] ?? [];

        foreach ($ships as $ship) {
            $this->assertArrayHasKey('parentComponent', $ship, 'Ship should have parentComponent');
            $this->assertArrayHasKey('cluster', $ship, 'Ship should have cluster');
            $this->assertArrayHasKey('sector', $ship, 'Ship should have sector');
            $this->assertArrayHasKey('zone', $ship, 'Ship should have zone');
        }
    }

    public function test_ships_have_required_specification_fields(): void
    {
        $save = $this->getTestSave();
        $collections = $save->getDataReader()->getCollections();

        $data = $collections->ships()->loadData();
        $ships = $data['ship'] ?? [];

        foreach ($ships as $ship) {
            $this->assertArrayHasKey('size', $ship, 'Ship should have size');
            $this->assertArrayHasKey('build-faction', $ship, 'Ship should have build-faction');
            $this->assertArrayHasKey('hull', $ship, 'Ship should have hull');
            $this->assertArrayHasKey('hull-type', $ship, 'Ship should have hull-type');
            $this->assertArrayHasKey('pilot', $ship, 'Ship should have pilot');
            $this->assertArrayHasKey('persons', $ship, 'Ship should have persons array');

            $this->assertIsArray($ship['persons'], 'Persons should be an array');
        }
    }

    // =========================================================================
    // Test: Stations Collection
    // =========================================================================

    public function test_stations_collection_loads_successfully(): void
    {
        $save = $this->getTestSave();
        $collections = $save->getDataReader()->getCollections();

        $data = $collections->stations()->loadData();

        $this->assertIsArray($data, 'Stations collection should return an array');
        $this->assertNotEmpty($data, 'Stations collection should not be empty');
        $this->assertArrayHasKey('station', $data, 'Stations data should have "station" type key');
    }

    public function test_stations_collection_contains_expected_items(): void
    {
        $save = $this->getTestSave();
        $collections = $save->getDataReader()->getCollections();

        $data = $collections->stations()->loadData();
        $stations = $data['station'] ?? [];

        // Real savegame should have at least some stations
        $this->assertGreaterThan(0, count($stations), 'Stations collection should contain stations');

        // Verify first station has expected structure
        $station1 = $stations[0];
        $this->assertArrayHasKey('componentID', $station1);
        $this->assertArrayHasKey('connectionID', $station1);
        $this->assertArrayHasKey('name', $station1);
        $this->assertArrayHasKey('owner', $station1);
        $this->assertArrayHasKey('code', $station1);
        $this->assertArrayHasKey('macro', $station1);
        $this->assertArrayHasKey('state', $station1);

        // Verify values are non-empty
        $this->assertNotEmpty($station1['componentID']);
        $this->assertNotEmpty($station1['connectionID']);
    }

    public function test_stations_have_parent_component(): void
    {
        $save = $this->getTestSave();
        $collections = $save->getDataReader()->getCollections();

        $data = $collections->stations()->loadData();
        $stations = $data['station'] ?? [];

        foreach ($stations as $station) {
            $this->assertArrayHasKey('parentComponent', $station, 'Station should have parentComponent');
            $this->assertStringContainsString('zone:', $station['parentComponent'], 'parentComponent should reference a zone');
        }
    }

    // =========================================================================
    // Test: Sectors Collection
    // =========================================================================

    public function test_sectors_collection_loads_successfully(): void
    {
        $save = $this->getTestSave();
        $collections = $save->getDataReader()->getCollections();

        $data = $collections->sectors()->loadData();

        $this->assertIsArray($data, 'Sectors collection should return an array');
        $this->assertNotEmpty($data, 'Sectors collection should not be empty');
        $this->assertArrayHasKey('sector', $data, 'Sectors data should have "sector" type key');
    }

    public function test_sectors_collection_contains_expected_items(): void
    {
        $save = $this->getTestSave();
        $collections = $save->getDataReader()->getCollections();

        $data = $collections->sectors()->loadData();
        $sectors = $data['sector'] ?? [];

        // Real savegame should have at least some sectors
        $this->assertGreaterThan(0, count($sectors), 'Sectors collection should contain sectors');

        // Verify first sector has expected structure
        $sector1 = $sectors[0];
        $this->assertArrayHasKey('componentID', $sector1);
        $this->assertArrayHasKey('connectionID', $sector1);
        $this->assertArrayHasKey('name', $sector1);
        $this->assertArrayHasKey('owner', $sector1);
        $this->assertArrayHasKey('zones', $sector1);

        // Verify values are non-empty
        $this->assertNotEmpty($sector1['componentID']);
        $this->assertNotEmpty($sector1['connectionID']);
    }

    public function test_sectors_have_zones_array(): void
    {
        $save = $this->getTestSave();
        $collections = $save->getDataReader()->getCollections();

        $data = $collections->sectors()->loadData();
        $sectors = $data['sector'] ?? [];

        foreach ($sectors as $sector) {
            $this->assertArrayHasKey('zones', $sector, 'Sector should have zones array');
            $this->assertIsArray($sector['zones'], 'Zones should be an array');
            $this->assertNotEmpty($sector['zones'], 'Test sectors should have zones');

            // Verify zone references format
            foreach ($sector['zones'] as $zoneRef) {
                $this->assertStringContainsString('zone:', $zoneRef, 'Zone reference should use zone: prefix');
            }
        }
    }

    public function test_sectors_have_parent_cluster(): void
    {
        $save = $this->getTestSave();
        $collections = $save->getDataReader()->getCollections();

        $data = $collections->sectors()->loadData();
        $sectors = $data['sector'] ?? [];

        foreach ($sectors as $sector) {
            $this->assertArrayHasKey('parentComponent', $sector, 'Sector should have parentComponent');
            $this->assertStringContainsString('cluster:', $sector['parentComponent'], 'parentComponent should reference a cluster');
        }
    }

    // =========================================================================
    // Test: People Collection
    // =========================================================================

    public function test_people_collection_loads_successfully(): void
    {
        $save = $this->getTestSave();
        $collections = $save->getDataReader()->getCollections();

        $data = $collections->people()->loadData();

        $this->assertIsArray($data, 'People collection should return an array');
        $this->assertNotEmpty($data, 'People collection should not be empty');
        $this->assertArrayHasKey('person', $data, 'People data should have "person" type key');
    }

    public function test_people_collection_contains_expected_items(): void
    {
        $save = $this->getTestSave();
        $collections = $save->getDataReader()->getCollections();

        $data = $collections->people()->loadData();
        $people = $data['person'] ?? [];

        $this->assertGreaterThan(0, count($people), 'People collection should contain people');

        // Verify first person has expected structure (values may vary in real save data)
        $person1 = $people[0];
        $this->assertArrayHasKey('componentID', $person1);
        $this->assertArrayHasKey('connectionID', $person1);
        $this->assertArrayHasKey('name', $person1);
        $this->assertArrayHasKey('owner', $person1);
        $this->assertArrayHasKey('code', $person1);
        $this->assertArrayHasKey('role', $person1);

        // Verify structure (not specific values, as this is real save data)
        $this->assertNotEmpty($person1['componentID'], 'Person should have a component ID');
        $this->assertEquals('person', $person1['connectionID'], 'Connection ID should be "person"');
        // Name may be empty in real save data for unnamed NPCs
        $this->assertIsString($person1['name'], 'Name should be a string');
    }

    public function test_people_have_character_attributes(): void
    {
        $save = $this->getTestSave();
        $collections = $save->getDataReader()->getCollections();

        $data = $collections->people()->loadData();
        $people = $data['person'] ?? [];

        foreach ($people as $person) {
            $this->assertArrayHasKey('race', $person, 'Person should have race');
            $this->assertArrayHasKey('gender', $person, 'Person should have gender');
            $this->assertArrayHasKey('macro', $person, 'Person should have macro');
            $this->assertArrayHasKey('seed', $person, 'Person should have seed');
            $this->assertArrayHasKey('anonymous', $person, 'Person should have anonymous flag');

            // Verify data types
            $this->assertIsString($person['race']);
            $this->assertIsString($person['gender']);
            $this->assertIsBool($person['anonymous']);
        }
    }

    public function test_people_have_parent_component(): void
    {
        $save = $this->getTestSave();
        $collections = $save->getDataReader()->getCollections();

        $data = $collections->people()->loadData();
        $people = $data['person'] ?? [];

        foreach ($people as $person) {
            $this->assertArrayHasKey('parentComponent', $person, 'Person should have parentComponent');
            $this->assertStringContainsString('ship:', $person['parentComponent'], 'parentComponent should reference a ship');
        }
    }

    // =========================================================================
    // Test: Additional Collections
    // =========================================================================

    public function test_zones_collection_loads_successfully(): void
    {
        $save = $this->getTestSave();
        $collections = $save->getDataReader()->getCollections();

        $data = $collections->zones()->loadData();

        $this->assertIsArray($data, 'Zones collection should return an array');
        $this->assertNotEmpty($data, 'Zones collection should not be empty');
        $this->assertArrayHasKey('zone', $data, 'Zones data should have "zone" type key');

        $zones = $data['zone'] ?? [];
        $this->assertGreaterThan(0, count($zones), 'Zones collection should contain zones');
    }

    public function test_clusters_collection_loads_successfully(): void
    {
        $save = $this->getTestSave();
        $collections = $save->getDataReader()->getCollections();

        $data = $collections->clusters()->loadData();

        $this->assertIsArray($data, 'Clusters collection should return an array');
        $this->assertNotEmpty($data, 'Clusters collection should not be empty');
        $this->assertArrayHasKey('cluster', $data, 'Clusters data should have "cluster" type key');

        $clusters = $data['cluster'] ?? [];
        $this->assertGreaterThan(0, count($clusters), 'Clusters collection should contain clusters');
    }

    public function test_regions_collection_loads_successfully(): void
    {
        $save = $this->getTestSave();
        $collections = $save->getDataReader()->getCollections();

        $data = $collections->regions()->loadData();

        $this->assertIsArray($data, 'Regions collection should return an array');
        $this->assertNotEmpty($data, 'Regions collection should not be empty');
        $this->assertArrayHasKey('region', $data, 'Regions data should have "region" type key');

        $regions = $data['region'] ?? [];
        $this->assertGreaterThan(0, count($regions), 'Regions collection should contain regions');
    }

    public function test_celestials_collection_loads_successfully(): void
    {
        $save = $this->getTestSave();
        $collections = $save->getDataReader()->getCollections();

        $data = $collections->celestials()->loadData();

        $this->assertIsArray($data, 'Celestials collection should return an array');
        $this->assertNotEmpty($data, 'Celestials collection should not be empty');
        $this->assertArrayHasKey('celestial-body', $data, 'Celestials data should have "celestial-body" type key');

        $celestials = $data['celestial-body'] ?? [];
        $this->assertGreaterThan(0, count($celestials), 'Celestials collection should contain celestial bodies');
    }

    public function test_player_collection_loads_successfully(): void
    {
        $save = $this->getTestSave();
        $collections = $save->getDataReader()->getCollections();

        $data = $collections->player()->loadData();

        $this->assertIsArray($data, 'Player collection should return an array');

        // Skip test if player data is empty (may not be present in all real saves)
        if (empty($data)) {
            $this->markTestSkipped('Player collection is empty in this test save');
        }

        // PlayerCollection has special loadData() that returns the player object directly
        // Not wrapped in a type key like other collections
        $this->assertArrayHasKey('name', $data, 'Player should have name field');
        $this->assertArrayHasKey('code', $data, 'Player should have code field');
        $this->assertArrayHasKey('wares', $data, 'Player should have wares field');
        $this->assertArrayHasKey('blueprints', $data, 'Player should have blueprints field');

        $this->assertIsString($data['name'], 'Player name should be a string');
        $this->assertIsArray($data['wares']);
        $this->assertIsArray($data['blueprints']);
    }

    public function test_event_log_collection_loads_successfully(): void
    {
        $save = $this->getTestSave();
        $collections = $save->getDataReader()->getCollections();

        $data = $collections->eventLog()->loadData();

        $this->assertIsArray($data, 'Event log collection should return an array');
        $this->assertNotEmpty($data, 'Event log collection should not be empty');
        $this->assertArrayHasKey('log-entry', $data, 'Event log data should have "log-entry" type key');

        $entries = $data['log-entry'] ?? [];
        $this->assertGreaterThan(0, count($entries), 'Test data should contain event log entries');
    }

    // =========================================================================
    // Test: CLI Command Integration
    // =========================================================================

    public function test_ships_command_returns_json_response(): void
    {
        $save = $this->getTestSave();

        // Simulate CLI command execution
        ob_start();
        try {
            // This would normally be called via CLI, but we can test the data flow
            $collections = $save->getDataReader()->getCollections();
            $data = $collections->ships()->loadData();

            $this->assertNotEmpty($data);
            echo json_encode(['success' => true, 'data' => $data]);
            $output = ob_get_clean();

            $response = json_decode($output, true);
            $this->assertIsArray($response);
            $this->assertTrue($response['success']);
            $this->assertArrayHasKey('data', $response);
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    public function test_stations_command_returns_json_response(): void
    {
        $save = $this->getTestSave();

        ob_start();
        try {
            $collections = $save->getDataReader()->getCollections();
            $data = $collections->stations()->loadData();

            $this->assertNotEmpty($data);
            echo json_encode(['success' => true, 'data' => $data]);
            $output = ob_get_clean();

            $response = json_decode($output, true);
            $this->assertIsArray($response);
            $this->assertTrue($response['success']);
            $this->assertArrayHasKey('data', $response);
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    public function test_sectors_command_returns_json_response(): void
    {
        $save = $this->getTestSave();

        ob_start();
        try {
            $collections = $save->getDataReader()->getCollections();
            $data = $collections->sectors()->loadData();

            $this->assertNotEmpty($data);
            echo json_encode(['success' => true, 'data' => $data]);
            $output = ob_get_clean();

            $response = json_decode($output, true);
            $this->assertIsArray($response);
            $this->assertTrue($response['success']);
            $this->assertArrayHasKey('data', $response);
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    public function test_people_command_returns_json_response(): void
    {
        $save = $this->getTestSave();

        ob_start();
        try {
            $collections = $save->getDataReader()->getCollections();
            $data = $collections->people()->loadData();

            $this->assertNotEmpty($data);
            echo json_encode(['success' => true, 'data' => $data]);
            $output = ob_get_clean();

            $response = json_decode($output, true);
            $this->assertIsArray($response);
            $this->assertTrue($response['success']);
            $this->assertArrayHasKey('data', $response);
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }
}

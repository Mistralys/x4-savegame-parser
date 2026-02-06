<?php

declare(strict_types=1);

namespace X4\SaveGameParserTests\TestClasses;

use Mistralys\X4\SaveViewer\Data\SaveManager;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log\LogCategories;

abstract class X4LogCategoriesTestCase extends X4ParserTestCase
{
    protected string $testSaveFolder;

    protected array $categoryAssignments = array(
        'tip' => LogCategories::CATEGORY_TIPS,
        'station-credits-dropped' => LogCategories::CATEGORY_STATION_FINANCE,
        'station-constructed' => LogCategories::CATEGORY_STATION_BUILDING,
        'mount-defence' => LogCategories::CATEGORY_WAR,
        'assigned-npc' => LogCategories::CATEGORY_CREW_ASSIGNMENT,
        'mission' => LogCategories::CATEGORY_MISSIONS,
        'forced-to-flee' => LogCategories::CATEGORY_ATTACKED,
        'reconnaissance' => LogCategories::CATEGORY_WAR,
        'pirate-harassment' => LogCategories::CATEGORY_ATTACKED,
        'reputation-gained' => LogCategories::CATEGORY_REPUTATION,
        'ship-constructed' => LogCategories::CATEGORY_SHIP_CONSTRUCTION,
        'ship-repaired' => LogCategories::CATEGORY_SHIP_SUPPLY,
        'reward-station-defense' => LogCategories::CATEGORY_REWARD,
        'ship-destroyed' => LogCategories::CATEGORY_DESTROYED,
        'found-lockbox' => LogCategories::CATEGORY_LOCKBOX,
        'promotion' => LogCategories::CATEGORY_PROMOTION,
        'discount' => LogCategories::CATEGORY_PROMOTION,
        'alert' => LogCategories::CATEGORY_ALERT
    );

    protected function setUp() : void
    {
        parent::setUp();

        $this->testSaveFolder = __DIR__.'/../files/test-saves';
    }

    public function createSaveManager() : SaveManager
    {
        return SaveManager::create(
            $this->testSaveFolder,
            $this->testSaveFolder
        );
    }

    public function getTestLog() : Log
    {
        $manager = $this->createSaveManager();
        $saves = $manager->getArchivedSaves();

        // Find the specific test save folder with minimal test data
        $testSave = null;
        foreach ($saves as $save) {
            // Use the original test data folder (not the real savegame)
            if ($save->getStorageFolder()->getName() === 'unpack-20260206211435-quicksave') {
                $testSave = $save;
                break;
            }
        }

        $this->assertNotNull($testSave, 'Test save with log test data not found');

        $log = $testSave->getDataReader()->getLog();

        $this->addFolderToRemove($log->getCacheInfo()->getWriter()->getPath());

        return $log;
    }
}

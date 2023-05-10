<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data;

use AppUtils\FileHelper;
use Mistralys\X4\SaveViewer\Data\SaveReader\Blueprints;
use Mistralys\X4\SaveViewer\Data\SaveReader\Factions;
use Mistralys\X4\SaveViewer\Data\SaveReader\Inventory;
use Mistralys\X4\SaveViewer\Data\SaveReader\KhaakStationsReader;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log;
use Mistralys\X4\SaveViewer\Data\SaveReader\PlayerInfo;
use Mistralys\X4\SaveViewer\Data\SaveReader\SaveInfo;
use Mistralys\X4\SaveViewer\Data\SaveReader\Statistics;
use Mistralys\X4\SaveViewer\Parser\Collections;

class SaveReader
{
    private BaseSaveFile $saveFile;
    protected Collections $collections;

    public function __construct(BaseSaveFile $saveFile)
    {
        $this->saveFile = $saveFile;
        $this->collections = new Collections($saveFile->getStorageFolder()->getPath().'/JSON');
    }

    public function getSaveFile() : BaseSaveFile
    {
        return $this->saveFile;
    }

    public function getCollections() : Collections
    {
        return $this->collections;
    }

    public function getSaveInfo() : SaveInfo
    {
        return new SaveInfo($this);
    }

    public function getPlayer() : PlayerInfo
    {
        return new PlayerInfo($this);
    }

    public function getBlueprints() : Blueprints
    {
        return new Blueprints($this);
    }

    public function getStatistics() : Statistics
    {
        return new Statistics($this);
    }

    public function getLog() : Log
    {
        return new Log($this);
    }

    public function getFactions() : Factions
    {
        return new Factions($this);
    }

    public function getInventory() : Inventory
    {
        return new Inventory($this);
    }

    public function saveData(string $dataID, array $data) : void
    {
        FileHelper::saveAsJSON($data, $this->getDataPath($dataID), true);
    }

    public function getRawData(string $dataID) : array
    {
        return FileHelper::parseJSONFile($this->getDataPath($dataID));
    }

    public function dataExists(string $dataID) : bool
    {
        return false;
    }

    public function getDataPath(string $dataID) : string
    {
        return sprintf(
            '%s/%s.json',
            $this->saveFile->getJSONPath(),
            $dataID
        );
    }

    public function countLosses() : int
    {
        return $this->getLog()->getDestroyed()->countEntries();
    }

    public function getKhaakStations() : KhaakStationsReader
    {
        return new KhaakStationsReader($this);
    }
}

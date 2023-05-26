<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data;

use AppUtils\FileHelper;
use Mistralys\X4\SaveViewer\Data\SaveReader\Blueprints;
use Mistralys\X4\SaveViewer\Data\SaveReader\Factions;
use Mistralys\X4\SaveViewer\Data\SaveReader\GameTime;
use Mistralys\X4\SaveViewer\Data\SaveReader\Inventory;
use Mistralys\X4\SaveViewer\Data\SaveReader\KhaakStationsReader;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log;
use Mistralys\X4\SaveViewer\Data\SaveReader\PlayerInfo;
use Mistralys\X4\SaveViewer\Data\SaveReader\SaveInfo;
use Mistralys\X4\SaveViewer\Data\SaveReader\ShipLossesReader;
use Mistralys\X4\SaveViewer\Data\SaveReader\Statistics;
use Mistralys\X4\SaveViewer\Parser\Collections;
use testsuites\FileHelperTests\ResolvePathTypeTest;

class SaveReader
{
    private BaseSaveFile $saveFile;
    protected Collections $collections;
    private ?Blueprints $blueprints = null;
    private ?PlayerInfo $player = null;
    private ?SaveInfo $saveInfo = null;

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
        if(!isset($this->saveInfo)) {
            $this->saveInfo = new SaveInfo($this);
        }

        return $this->saveInfo;
    }

    public function getPlayer() : PlayerInfo
    {
        if(!isset($this->player))
        {
            $this->player = new PlayerInfo($this);
        }

        return $this->player;
    }


    public function getBlueprints() : Blueprints
    {
        if(!isset($this->blueprints))
        {
            $this->blueprints = new Blueprints($this);
        }

        return $this->blueprints;
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

    public function getRawData(string $dataID) : array
    {
        return FileHelper::parseJSONFile($this->getDataPath($dataID));
    }

    public function dataExists(string $dataID) : bool
    {
        return file_exists($this->getDataPath($dataID));
    }

    public function getDataPath(string $dataID) : string
    {
        return sprintf(
            '%s/%s.json',
            $this->saveFile->getJSONPath()->getPath(),
            $dataID
        );
    }

    private ?KhaakStationsReader $khaak = null;

    public function getKhaakStations() : KhaakStationsReader
    {
        if(!isset($this->khaak)) {
            $this->khaak = new KhaakStationsReader($this);
        }

        return $this->khaak;
    }

    private ?ShipLossesReader $losses = null;

    public function getShipLosses() : ShipLossesReader
    {
        if(!isset($this->losses)) {
            $this->losses = new ShipLossesReader($this);
        }

        return $this->losses;
    }

    public function createTime($time) : GameTime
    {
        return GameTime::create($time, $this->getSaveInfo()->getGameStartTime());
    }

    public function getGameStartTime() : float
    {
        return $this->getSaveInfo()->getGameStartTime();
    }
}

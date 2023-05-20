<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader\KhaakStations;

use AppUtils\ArrayDataCollection;
use Mistralys\X4\SaveViewer\Parser\DataProcessing\Processors\KhaakStationsList;

class KhaakSector extends ArrayDataCollection
{
    /**
     * @var KhaakStation[]|null
     */
    private ?array $stations = null;

    public function getName() : string
    {
        $name = $this->getString(KhaakStationsList::KEY_SECTOR_NAME);

        if(!empty($name)) {
            return $name;
        }

        return $this->getConnectionID();
    }

    public function getConnectionID() : string
    {
        return $this->getString(KhaakStationsList::KEY_SECTOR_CONNECTION_ID);
    }

    /**
     * @return KhaakStation[]
     */
    public function getStations() : array
    {
        if(isset($this->stations)) {
            return $this->stations;
        }

        $data = $this->getArray(KhaakStationsList::KEY_STATIONS);
        $result = array();

        foreach($data as $stationData)
        {
            $result[] = new KhaakStation($this, $stationData);
        }

        $this->stations = $result;

        return $result;
    }

    public function countHives() : int
    {
        $stations = $this->getStations();
        $total = 0;

        foreach ($stations as $station)
        {
            if($station->isHive()) {
                $total++;
            }
        }

        return $total;
    }

    public function countNests() : int
    {
        $stations = $this->getStations();
        $total = 0;

        foreach ($stations as $station)
        {
            if($station->isNest()) {
                $total++;
            }
        }

        return $total;
    }

    public function countPlayerShips() : int
    {
        return count($this->getRawShips());
    }

    public function countPlayerStations() : int
    {
        $assets = $this->getArray(KhaakStationsList::KEY_PLAYER_ASSETS);

        return count($assets[KhaakStationsList::KEY_PLAYER_STATIONS]);
    }

    public function getRawShips() : array
    {
        $assets = $this->getArray(KhaakStationsList::KEY_PLAYER_ASSETS);

        return $assets[KhaakStationsList::KEY_PLAYER_SHIPS];
    }
}

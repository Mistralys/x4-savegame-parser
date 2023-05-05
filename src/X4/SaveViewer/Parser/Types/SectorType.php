<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Types;

use AppUtils\ClassHelper;
use AppUtils\ClassHelper\BaseClassHelperException;
use Mistralys\X4\SaveViewer\SaveViewerException;

class SectorType extends BaseComponentType
{
    public const TYPE_ID = 'sector';

    public const KEY_ZONES = 'zones';
    public const KEY_OWNER = 'owner';
    public const KEY_NAME = 'name';

    public function __construct(ClusterType $cluster, string $connectionID, string $componentID)
    {
        parent::__construct($cluster->getCollections(), $connectionID, $componentID);

        $this->setParentComponent($cluster);
    }

    /**
     * @return StationType[]
     */
    public function getPlayerStations() : array
    {
        $stations = $this->getStations();
        $result = array();

        foreach($stations as $station)
        {
            if($station->getOwner() === 'player')
            {
                $result[] = $station;
            }
        }

        return $result;
    }

    /**
     * @return StationType[]
     */
    public function getStations() : array
    {
        $stations = $this->collections->stations()->getAll();
        $id = $this->getUniqueID();
        $result = array();

        foreach($stations as $station)
        {
            if($station->getSector()->getUniqueID() === $id) {
                $result[] = $station;
            }
        }

        return $result;
    }

    /**
     * @return ZoneType[]
     * @throws SaveViewerException
     */
    public function getZones() : array
    {
        $list = $this->getComponentsByKey(self::KEY_ZONES);
        $result = array();

        foreach ($list as $item)
        {
            if($item instanceof ZoneType) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * @return ShipType[]
     */
    public function getPlayerShips() : array
    {
        $ships = $this->getShips();
        $result = array();

        foreach($ships as $ship)
        {
            if($ship->getOwner() === 'player') {
                $result[] = $ship;
            }
        }

        return $result;
    }

    /**
     * @return ShipType[]
     */
    public function getShips() : array
    {
        $ships = $this->collections->ships()->getAll();
        $result = array();
        $id = $this->getUniqueID();

        foreach($ships as $ship)
        {
            if($ship->getSector()->getUniqueID() === $id) {
                $result[] = $ship;
            }
        }

        return $result;
    }

    protected function getDefaultData() : array
    {
        return array(
            self::KEY_NAME => '',
            self::KEY_OWNER => '',
            self::KEY_ZONES => array()
        );
    }

    public function getTypeID() : string
    {
        return self::TYPE_ID;
    }

    /**
     * @return ClusterType
     * @throws BaseClassHelperException
     */
    public function getCluster() : ClusterType
    {
        return ClassHelper::requireObjectInstanceOf(
            ClusterType::class,
            $this->getParentComponent()
        );
    }

    /**
     * @param string $owner
     * @return $this
     */
    public function setOwner(string $owner) : self
    {
        return $this->setKey(self::KEY_OWNER, $owner);
    }

    /**
     * @param ZoneType $zone
     * @return $this
     */
    public function addZone(ZoneType $zone) : self
    {
        return $this->setKeyComponent(self::KEY_ZONES, $zone);
    }

    private const SECTOR_NAMES = array(
        'cluster_606_sector002' => 'Reflected Stars',
        'cluster_606_sector001' => 'Kingdom End 1',
        'cluster_606_sector003' => 'Towering Wave',
        'cluster_603_sector001' => 'Great Reef',
        'cluster_602_sector001' => 'Barren Shores',
        'cluster_601_sector001' => 'Watchful Gaze',
        'cluster_112_sector001' => 'Savage Spur 1',
        'cluster_28_sector001' => 'Antigone Memorial',
        'cluster_604_sector001' => 'Ocean of Fantasy',
        'cluster_605_sector001' => 'Sanctuary of Darkness',
        'cluster_607_sector001' => 'Rolk\'s demise',
        'cluster_608_sector001' => 'Atreus\'s Clouds',
        'cluster_31_sector001' => 'Heretic\'s End',
        'cluster_403_sector001' => 'Wretched Skies V Family Phi',
        'cluster_422_sector001' => 'Wretched Skies X',
        'cluster_423_sector001' => 'Litany of Fury IX',
        'cluster_400_sector001' => 'Wretched Skies IV Family Valka',
        'cluster_401_sector001' => 'Family Zhin',
        'cluster_402_sector001' => 'Family Kritt',
        'cluster_32_sector001' => 'Tharka\'s Cascade XV',
        'cluster_32_sector002' => 'Tharka\'s Cascade XVII',
        'cluster_33_sector001' => 'Matrix #79B',
        'cluster_14_sector001' => 'Argon Prime',
        'cluster_12_sector001' => 'True Sight',
        'cluster_24_sector001' => 'Holy Vision',
        'cluster_36_sector001' => 'Cardinal\'s Redress',
        'cluster_35_sector001' => 'Lasting Vengeance',
        'cluster_22_sector001' => 'Pious Mists II',
        'cluster_37_sector001' => 'Pious Mists IV',
        'cluster_04_sector001' => 'Nopileos\' Fortune II',
        'cluster_01_sector001' => 'Grand Exchange I',
        'cluster_01_sector002' => 'Grand Exchange III',
        'cluster_01_sector003' => 'Grand Exchange IV',
        'cluster_504_sector001' => 'Leap of Faith',
        'cluster_02_sector001' => 'Eighteen Billion',
        'cluster_08_sector001' => 'Silent Witness I',
        'cluster_34_sector001' => 'Profit Center Alpha',
        'cluster_420_sector001' => 'Two Grand',
        'cluster_404_sector001' => 'Zyarth\'s Dominion I',
        'cluster_416_sector002' => 'Guiding Star VII',
        'cluster_417_sector001' => 'Eleventh Hour',
        'cluster_421_sector001' => 'Fires of Defeat',
        'cluster_407_sector001' => 'Family Tkr',
        'cluster_409_sector001' => 'Tharka\'s Ravine XXIV',
        'cluster_410_sector001' => 'Tharka\'s Ravine XVI',
        'cluster_411_sector001' => 'Heart Of Acrimony II',
        'cluster_412_sector001' => 'Tharka\'s Ravine VIII',
        'cluster_413_sector001' => 'Tharka\'s Ravine IV Tharka\'s Fall',
        'cluster_408_sector001' => 'Thuruk\'s Demise III',
        'cluster_15_sector001' => 'Ianamus Zura IV',
        'cluster_16_sector001' => 'Matrix Prime',
        'cluster_20_sector001' => 'Company Regard',
        'cluster_49_sector001' => 'Frontier Edge',
        'cluster_113_sector001' => 'Segaris',
        'cluster_114_sector001' => 'Gaian Prophecy',
        'cluster_18_sector001' => 'Trinity Sanctum III',
        'cluster_425_sector001' => ' Heart of Acrimony I The Boneyard',
        'cluster_06_sector002' => 'Black Hole Sun V',
        'cluster_27_sector001' => 'The Void'
    );

    public function getName() : string
    {
        return $this->getString(self::KEY_NAME);
    }

    public function resolveName() : string
    {
        $connectionID = $this->getConnectionID();

        foreach(self::SECTOR_NAMES as $id => $name) {
            if(strpos($connectionID, $id) !== false) {
                return $name;
            }
        }

        return '';
    }

    public function toArray() : array
    {
        $this->setKey(self::KEY_NAME, $this->resolveName());

        return parent::toArray();
    }
}

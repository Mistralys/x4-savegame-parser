<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Collections;

use AppUtils\ClassHelper;
use AppUtils\ClassHelper\BaseClassHelperException;
use Mistralys\X4\SaveViewer\Parser\Types\StationType;
use Mistralys\X4\SaveViewer\Parser\Types\ZoneType;

class StationsCollection extends BaseCollection
{
    public const COLLECTION_ID = 'stations';

    public function getCollectionID() : string
    {
        return self::COLLECTION_ID;
    }

    public function createStation(ZoneType $zone, string $connectionID, string $componentID) : StationType
    {
        $station = new StationType($zone, $connectionID, $componentID);

        $this->addComponent($station);

        return $station;
    }

    /**
     * @return StationType[]
     */
    public function getAll() : array
    {
        $components = $this->getComponentsByType(StationType::TYPE_ID);
        $result = array();

        foreach($components as $component)
        {
            if($component instanceof StationType) {
                $result[] = $component;
            }
        }

        return $result;
    }
}

<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Collections;

use Mistralys\X4\SaveViewer\Parser\Types\BaseComponentType;
use Mistralys\X4\SaveViewer\Parser\Types\ShipType;
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
}

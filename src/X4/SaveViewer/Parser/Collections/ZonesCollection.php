<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Collections;

use Mistralys\X4\SaveViewer\Parser\Types\SectorType;
use Mistralys\X4\SaveViewer\Parser\Types\ZoneType;

class ZonesCollection extends BaseCollection
{
    public const string COLLECTION_ID = 'zones';

    public function getCollectionID() : string
    {
        return self::COLLECTION_ID;
    }

    public function createZone(SectorType $sector, string $collectionID, string $componentID) : ZoneType
    {
        $zone = new ZoneType($sector, $collectionID, $componentID);

        $this->addComponent($zone);

        return $zone;
    }
}

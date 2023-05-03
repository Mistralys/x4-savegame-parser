<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Collections;

use Mistralys\X4\SaveViewer\Parser\Types\CelestialBodyType;
use Mistralys\X4\SaveViewer\Parser\Types\ClusterType;

class CelestialsCollection extends BaseCollection
{
    public const COLLECTION_ID = 'celestial-bodies';

    public function getCollectionID() : string
    {
        return self::COLLECTION_ID;
    }

    public function createCelestial(ClusterType $cluster, string $collectionID, string $componentID) : CelestialBodyType
    {
        $region = new CelestialBodyType($cluster, $collectionID, $componentID);

        $this->addComponent($region);

        return $region;
    }
}

<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Collections;

use Mistralys\X4\SaveViewer\Parser\Types\ClusterType;
use Mistralys\X4\SaveViewer\Parser\Types\RegionType;

class RegionsCollection extends BaseCollection
{
    public const COLLECTION_ID = 'regions';

    public function getCollectionID() : string
    {
        return self::COLLECTION_ID;
    }

    public function createRegion(ClusterType $cluster, string $collectionID, string $componentID) : RegionType
    {
        $region = new RegionType($cluster, $collectionID, $componentID);

        $this->addComponent($region);

        return $region;
    }
}

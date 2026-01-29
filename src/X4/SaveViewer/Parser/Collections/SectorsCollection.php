<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Collections;

use Mistralys\X4\SaveViewer\Parser\Types\ClusterType;
use Mistralys\X4\SaveViewer\Parser\Types\SectorType;

class SectorsCollection extends BaseCollection
{
    public const string COLLECTION_ID = 'sectors';

    public function getCollectionID() : string
    {
        return self::COLLECTION_ID;
    }

    public function createSector(ClusterType $cluster, string $collectionID, string $componentID) : SectorType
    {
        $sector = new SectorType($cluster, $collectionID, $componentID);

        $this->addComponent($sector);

        return $sector;
    }
}

<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Collections;

use Mistralys\X4\SaveViewer\Parser\Types\ClusterType;

class ClustersCollection extends BaseCollection
{
    public const COLLECTION_ID = 'clusters';

    public function getCollectionID() : string
    {
        return self::COLLECTION_ID;
    }

    public function createCluster(string $collectionID, string $componentID) : ClusterType
    {
        $cluster = new ClusterType($this->collections, $collectionID, $componentID);

        $this->addComponent($cluster);

        return $cluster;
    }
}

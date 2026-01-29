<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Types;

use AppUtils\ClassHelper;

class RegionType extends BaseComponentType
{
    public const string TYPE_ID = 'region';

    public const string KEY_SECTOR_CONNECTIONS = 'sector-connections';

    public function __construct(ClusterType $cluster, string $connectionID, string $componentID)
    {
        parent::__construct($cluster->getCollections(), $connectionID, $componentID);

        $this->setParentComponent($cluster);
    }

    protected function getDefaultData() : array
    {
        return array(
            self::KEY_SECTOR_CONNECTIONS => array()
        );
    }

    public function getTypeID() : string
    {
        return self::TYPE_ID;
    }

    public function getCluster() : ClusterType
    {
        return ClassHelper::requireObjectInstanceOf(
            ClusterType::class,
            $this->getParentComponent()
        );
    }

    /**
     * Adds a connection to another region. These connections
     * are identified by their ID, so to find the target region,
     * the region with the target connection ID must be found.
     *
     * @param string $connectionID
     * @param string $targetConnectionID
     * @return $this
     */
    public function addSectorConnection(string $connectionID, string $targetConnectionID) : self
    {
        $sectorConnections = $this->getArray(self::KEY_SECTOR_CONNECTIONS);
        $sectorConnections[$connectionID] = $targetConnectionID;

        return $this->setKey(self::KEY_SECTOR_CONNECTIONS, $sectorConnections);
    }
}

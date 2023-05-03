<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Types;

use AppUtils\ClassHelper;
use AppUtils\ClassHelper\BaseClassHelperException;

class SectorType extends BaseComponentType
{
    public const TYPE_ID = 'sector';

    public const KEY_ZONES = 'zones';
    public const KEY_OWNER = 'owner';

    public function __construct(ClusterType $cluster, string $connectionID, string $componentID)
    {
        parent::__construct($cluster->getCollections(), $connectionID, $componentID);

        $this->setParentComponent($cluster);
    }

    protected function getDefaultData() : array
    {
        return array(
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
        'cluster_112_sector001' => 'Savage Spur 1'
    );

    public function toArray() : array
    {
        $this->setKey('name', $this->getName());

        return parent::toArray();
    }
}

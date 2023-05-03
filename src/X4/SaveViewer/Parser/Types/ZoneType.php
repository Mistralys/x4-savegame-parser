<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Types;

use AppUtils\ClassHelper;
use AppUtils\ClassHelper\BaseClassHelperException;
use Mistralys\X4\SaveViewer\Parser\Traits\ShipContainerInterface;
use Mistralys\X4\SaveViewer\Parser\Traits\ShipContainerTrait;

class ZoneType extends BaseComponentType implements ShipContainerInterface
{
    use ShipContainerTrait;

    public const TYPE_ID = 'zone';

    public const KEY_CODE = 'code';
    public const KEY_STATIONS = 'stations';

    public function __construct(SectorType $sector, string $connectionID, string $componentID)
    {
        parent::__construct($sector->getCollections(), $connectionID, $componentID);

        $this->setParentComponent($sector);
    }

    protected function getDefaultData() : array
    {
        return array(
            self::KEY_CODE => '',
            self::KEY_STATIONS => array()
        );
    }

    public function getTypeID() : string
    {
        return self::TYPE_ID;
    }

    /**
     * @return SectorType
     * @throws BaseClassHelperException
     */
    public function getSector() : SectorType
    {
        return ClassHelper::requireObjectInstanceOf(
            SectorType::class,
            $this->getParentComponent()
        );
    }

    public function getZone() : ZoneType
    {
        return $this;
    }

    public function getCluster() : ClusterType
    {
        return $this->getSector()->getCluster();
    }

    public function setCode(string $code) : self
    {
        return $this->setKey(self::KEY_CODE, $code);
    }

    public function addStation(StationType $station) : self
    {
        return $this->setKeyComponent(self::KEY_STATIONS, $station);
    }
}

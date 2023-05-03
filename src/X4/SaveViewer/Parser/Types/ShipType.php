<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Types;

use AppUtils\ClassHelper;
use AppUtils\ClassHelper\BaseClassHelperException;
use Mistralys\X4\SaveViewer\Parser\Traits\PersonContainerInterface;
use Mistralys\X4\SaveViewer\Parser\Traits\PersonContainerTrait;
use Mistralys\X4\SaveViewer\Parser\Traits\ShipContainerInterface;
use Mistralys\X4\SaveViewer\Parser\Traits\ShipContainerTrait;

class ShipType extends BaseComponentType implements PersonContainerInterface, ShipContainerInterface
{
    use PersonContainerTrait;
    use ShipContainerTrait;

    public const TYPE_ID = 'ship';

    public const KEY_NAME = 'name';
    public const KEY_OWNER = 'owner';
    public const KEY_STATE = 'state';
    public const STATE_WRECK = 'wreck';
    public const STATE_NORMAL = 'normal';
    public const KEY_CODE = 'code';
    public const KEY_CLASS = 'class';
    public const KEY_SIZE = 'size';
    public const KEY_COVER = 'cover';
    public const KEY_PILOT = 'pilot';
    public const KEY_BUILD_FACTION = 'build-faction';
    public const KEY_HULL = 'hull';
    public const KEY_HULL_TYPE = 'hull-type';
    public const KEY_MACRO = 'macro';
    public const KEY_CLUSTER = 'cluster';

    private ShipContainerInterface $container;

    public function __construct(ShipContainerInterface $parentComponent, string $connectionID, string $componentID)
    {
        $this->container = $parentComponent;

        parent::__construct($parentComponent->getCollections(), $connectionID, $componentID);

        $this->setParentComponent($parentComponent);
    }

    public function getZone() : ZoneType
    {
        return $this->container->getZone();
    }

    public function getSector() : SectorType
    {
        return $this->container->getSector();
    }

    /**
     * @return ShipContainerInterface
     */
    public function getContainer() : ShipContainerInterface
    {
        return $this->container;
    }

    public function getCluster() : ClusterType
    {
        return $this->container->getCluster();
    }

    protected function getDefaultData() : array
    {
        return array(
            self::KEY_CLUSTER => '',
            'sector' => '',
            'zone' => '',
            self::KEY_CLASS => '',
            self::KEY_CODE => '',
            self::KEY_NAME => '',
            self::KEY_STATE => '',
            self::KEY_OWNER => '',
            self::KEY_COVER => '',
            self::KEY_SIZE => '',
            self::KEY_BUILD_FACTION => '',
            self::KEY_HULL => '',
            self::KEY_HULL_TYPE => '',
            self::KEY_MACRO => '',
            self::KEY_PILOT => '',
            self::KEY_PERSONS => array()
        );
    }

    public function getTypeID() : string
    {
        return self::TYPE_ID;
    }

    public function setName(string $name) : self
    {
        return $this->setKey(self::KEY_NAME, $name);
    }

    public function setOwner(string $raceID) : self
    {
        return $this->setKey(self::KEY_OWNER, $raceID);
    }

    public function setState(string $state) : self
    {
        if(empty($state)) {
            $state = self::STATE_NORMAL;
        }

        return $this->setKey(self::KEY_STATE, $state);
    }

    public function getState() : string
    {
        return $this->getString(self::KEY_STATE);
    }

    public function isWreck() : bool
    {
        return $this->getState() === self::STATE_WRECK;
    }

    public function setCode(string $code) : self
    {
        return $this->setKey(self::KEY_CODE, $code);
    }

    public function setClass(string $class) : self
    {
        return $this->setKey(self::KEY_CLASS, $class);
    }

    public function getClass() : string
    {
        return $this->getString(self::KEY_CLASS);
    }

    public function getSize() : string
    {
        return $this->getString(self::KEY_SIZE);
    }

    public function setCover(string $raceID) : self
    {
        return $this->setKey(self::KEY_COVER, $raceID);
    }

    public function setPilot(PersonType $pilot) : self
    {
        return $this->setKey(self::KEY_PILOT, $pilot->getUniqueID());
    }

    public function setMacro(string $macro) : self
    {
        $parts = explode('_', $macro);
        array_shift($parts); // Remove "ship"

        $this->setKey(self::KEY_BUILD_FACTION, array_shift($parts));
        $this->setKey(self::KEY_SIZE, array_shift($parts));

        $hull = array_shift($parts);
        $this->setKey(self::KEY_HULL, $hull);

        if($hull === 'trans' || $hull === 'miner') {
            $this->setKey(self::KEY_HULL_TYPE, array_shift($parts));
        }

        return $this->setKey(self::KEY_MACRO, $macro);
    }

    public function toArray() : array
    {
        $sector = $this->getContainer()->getSector();
        $cluster = $this->getContainer()->getCluster();
        $zone = $this->getContainer()->getZone();

        $this->setKey('sector', $sector->getComponentID().' '.$sector->getConnectionID());
        $this->setKey(self::KEY_CLUSTER, $cluster->getComponentID().' '.$cluster->getConnectionID());
        $this->setKey('zone', $zone->getComponentID().' '.$zone->getConnectionID());

        return parent::toArray();
    }
}

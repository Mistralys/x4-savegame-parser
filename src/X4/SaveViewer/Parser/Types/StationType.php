<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Types;

use Mistralys\X4\SaveViewer\Parser\Traits\ShipContainerInterface;
use Mistralys\X4\SaveViewer\Parser\Traits\ShipContainerTrait;

class StationType extends BaseComponentType implements ShipContainerInterface
{
    use ShipContainerTrait;

    public const TYPE_ID = 'station';

    public const KEY_MACRO = 'macro';
    public const KEY_OWNER = 'owner';
    public const KEY_CODE = 'code';
    public const KEY_CLASS = 'class';
    public const KEY_NAME = 'name';

    private ZoneType $zone;

    public function __construct(ZoneType $zone, string $connectionID, string $componentID)
    {
        $this->zone = $zone;

        parent::__construct($zone->getCollections(), $connectionID, $componentID);

        $this->setParentComponent($zone);
    }

    public function getZone() : ZoneType
    {
        return $this->zone;
    }

    public function getSector() : SectorType
    {
        return $this->getZone()->getSector();
    }

    public function getCluster() : ClusterType
    {
        return $this->getSector()->getCluster();
    }

    public function getOwner() : string
    {
        return $this->getString(self::KEY_OWNER);
    }

    public function getMacro() : string
    {
        return $this->getString(self::KEY_MACRO);
    }

    public function getLabel() : string
    {
        $label = $this->getCode();
        $name = $this->getName();

        if(!empty($name)) {
            $label .= ' '.$name;
        }

        return $label;
    }

    public function getName() : string
    {
        return $this->getString(self::KEY_NAME);
    }

    public function getCode() : string
    {
        return $this->getString(self::KEY_CODE);
    }

    protected function getDefaultData() : array
    {
        return array(
            self::KEY_CLASS => '',
            self::KEY_CODE => '',
            self::KEY_NAME => '',
            self::KEY_OWNER => '',
            self::KEY_MACRO => '',
        );
    }

    public function getTypeID() : string
    {
        return self::TYPE_ID;
    }

    public function setMacro(string $macro) : self
    {
        return $this->setKey(self::KEY_MACRO, $macro);
    }

    public function setOwner(string $owner) : self
    {
        return $this->setKey(self::KEY_OWNER, $owner);
    }

    public function setCode(string $code) : self
    {
        return $this->setKey(self::KEY_CODE, $code);
    }

    public function setClass(string $class) : self
    {
        return $this->setKey(self::KEY_CLASS, $class);
    }

    public function setName(string $name) : self
    {
        return $this->setKey(self::KEY_NAME, $name);
    }
}

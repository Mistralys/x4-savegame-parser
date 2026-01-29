<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Types;

use Mistralys\X4\SaveViewer\Parser\Traits\PlayerContainerInterface;
use Mistralys\X4\SaveViewer\Parser\Traits\PlayerContainerTrait;
use Mistralys\X4\SaveViewer\Parser\Traits\ShipContainerInterface;
use Mistralys\X4\SaveViewer\Parser\Traits\ShipContainerTrait;

class StationType extends BaseComponentType implements ShipContainerInterface, PlayerContainerInterface
{
    use ShipContainerTrait;
    use PlayerContainerTrait;

    public const string TYPE_ID = 'station';

    public const string KEY_MACRO = 'macro';
    public const string KEY_OWNER = 'owner';
    public const string KEY_CODE = 'code';
    public const string KEY_CLASS = 'class';
    public const string KEY_NAME = 'name';
    public const string KEY_STATE = 'state';

    public const string STATE_NORMAL = 'normal';
    public const string STATE_WRECK = 'wreck';

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

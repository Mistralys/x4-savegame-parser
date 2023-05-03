<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Types;

class StationType extends BaseComponentType
{
    public const TYPE_ID = 'station';

    public const KEY_MACRO = 'macro';
    public const KEY_OWNER = 'owner';
    public const KEY_CODE = 'code';
    public const KEY_CLASS = 'class';
    public const KEY_NAME = 'name';

    public function __construct(ZoneType $zone, string $connectionID, string $componentID)
    {
        parent::__construct($zone->getCollections(), $connectionID, $componentID);

        $this->setParentComponent($zone);
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

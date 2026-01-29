<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Types;

class PlayerType extends BaseComponentType
{
    public const string TYPE_ID = 'player';

    public const string KEY_NAME = 'name';
    public const string KEY_CODE = 'code';
    public const string KEY_WARES = 'wares';
    public const string KEY_BLUEPRINTS = 'blueprints';

    protected function getDefaultData() : array
    {
        return array(
            self::KEY_NAME => '',
            self::KEY_CODE => '',
            self::KEY_WARES => array(),
            self::KEY_BLUEPRINTS => array()
        );
    }

    public function getTypeID() : string
    {
        return self::TYPE_ID;
    }

    public function addBlueprint(string $blueprintID) : self
    {
        $list = $this->getArray(self::KEY_BLUEPRINTS);
        if(!in_array($blueprintID, $list, true)) {
            $list[] = $blueprintID;
            usort($list, 'strnatcasecmp');
            $this->setKey(self::KEY_BLUEPRINTS, $list);
        }

        return $this;
    }

    public function addWare(string $wareID, int $amount) : self
    {
        if($amount === 0) {
            $amount = 1;
        }

        $wares = $this->getArray(self::KEY_WARES);
        $wares[$wareID] = $amount;

        return $this->setKey(self::KEY_WARES, $wares);
    }

    public function setCode(string $code) : self
    {
        return $this->setKey(self::KEY_CODE, $code);
    }

    public function setName(string $name) : self
    {
        return $this->setKey(self::KEY_NAME, $name);
    }
}

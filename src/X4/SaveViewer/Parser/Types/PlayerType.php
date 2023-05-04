<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Types;

class PlayerType extends BaseComponentType
{
    public const TYPE_ID = 'player';

    protected function getDefaultData() : array
    {
        return array(
            'name' => '',
            'code' => '',
            'wares' => array(),
            'blueprints' => array()
        );
    }

    public function getTypeID() : string
    {
        return self::TYPE_ID;
    }

    public function addBlueprint(string $blueprintID) : self
    {
        $list = $this->getArray('blueprints');
        if(!in_array($blueprintID, $list, true)) {
            $list[] = $blueprintID;
            $this->setKey('blueprints', $list);
        }

        return $this;
    }

    public function addWare(string $wareID, int $amount) : self
    {
        if($amount === 0) {
            $amount = 1;
        }

        $wares = $this->getArray('wares');
        $wares[$wareID] = $amount;

        return $this->setKey('wares', $wares);
    }

    public function setCode(string $code) : self
    {
        return $this->setKey('code', $code);
    }

    public function setName(string $name) : self
    {
        return $this->setKey('name', $name);
    }
}

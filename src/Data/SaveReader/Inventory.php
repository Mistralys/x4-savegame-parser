<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\Data\SaveReader;

use Mistralys\X4Saves\SaveParser\Tags\Tag\PlayerComponentTag;

class Inventory extends Info
{
    /**
     * @var Ware[]
     */
    private array $wares = array();

    protected function getAutoDataName(): string
    {
        return PlayerComponentTag::SAVE_NAME;
    }

    protected function init(): void
    {
        foreach($this->data[PlayerComponentTag::KEY_INVENTORY] as $name => $amount) {
            $this->wares[] = new Ware($name, intval($amount));
        }

        usort($this->wares, function (Ware $a, Ware $b) {
            return strnatcasecmp($a->getName(), $b->getName());
        });
    }

    public function getWares() : array
    {
        return $this->wares;
    }
}

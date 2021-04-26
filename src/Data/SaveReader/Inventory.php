<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\Data\SaveReader;

class Inventory extends Info
{
    /**
     * @var Ware[]
     */
    private array $wares = array();

    protected function getAutoDataName(): string
    {
        return 'inventory';
    }

    protected function init(): void
    {
        foreach($this->data as $name => $amount) {
            $this->wares[] = new Ware($name, $amount);
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

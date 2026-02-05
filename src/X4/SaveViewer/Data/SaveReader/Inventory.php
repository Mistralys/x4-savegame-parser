<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader;

use Mistralys\X4\SaveViewer\Parser\Tags\Tag\PlayerComponentTag;

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
        // Safety check: ensure inventory data exists and is an array
        if(!isset($this->data[PlayerComponentTag::KEY_INVENTORY]) || !is_array($this->data[PlayerComponentTag::KEY_INVENTORY])) {
            $this->wares = [];
            return;
        }

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

    /**
     * Convert Inventory to array suitable for CLI API output.
     *
     * @return array<int,array<string,mixed>> JSON-serializable array of ware objects
     */
    public function toArrayForAPI(): array
    {
        $result = [];

        foreach ($this->wares as $ware) {
            $result[] = [
                'name' => $ware->getName(),
                'amount' => $ware->getAmount()
            ];
        }

        return $result;
    }
}

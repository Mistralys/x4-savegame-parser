<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader;

use Mistralys\X4\SaveViewer\Parser\Types\PlayerType;

class Inventory extends Info
{
    /**
     * @var Ware[]
     */
    private array $wares = array();

    protected function init(): void
    {
        // Load player data from collection
        $data = $this->collections->player()->loadData();

        // Safety check: ensure wares data exists and is an array
        if(!isset($data[PlayerType::KEY_WARES]) || !is_array($data[PlayerType::KEY_WARES])) {
            $this->wares = [];
            return;
        }

        foreach($data[PlayerType::KEY_WARES] as $name => $amount) {
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

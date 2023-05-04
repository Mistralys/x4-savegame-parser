<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Collections;

use Mistralys\X4\SaveViewer\Parser\Traits\ShipContainerInterface;
use Mistralys\X4\SaveViewer\Parser\Types\ShipType;

class ShipsCollection extends BaseCollection
{
    public const COLLECTION_ID = 'ships';

    public function getCollectionID() : string
    {
        return self::COLLECTION_ID;
    }

    public function createShip(ShipContainerInterface $parentComponent, string $connectionID, string $componentID) : ShipType
    {
        $ship = new ShipType($parentComponent, $connectionID, $componentID);

        $this->addComponent($ship);

        return $ship;
    }

    /**
     * @return ShipType[]
     */
    public function getAll() : array
    {
        $entries = $this->getComponentsByType(ShipType::TYPE_ID);
        $result = array();

        foreach($entries as $entry)
        {
            if($entry instanceof ShipType) {
                $result[] = $entry;
            }
        }

        return $result;
    }
}

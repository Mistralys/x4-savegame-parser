<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Traits;

use Mistralys\X4\SaveViewer\Parser\Types\ShipType;

trait ShipContainerTrait
{
    /**
     * @param ShipType $ship
     * @return $this
     */
    public function addShip(ShipType $ship) : self
    {
        return $this->setKeyComponent(ShipContainerInterface::KEY_SHIPS, $ship);
    }
}

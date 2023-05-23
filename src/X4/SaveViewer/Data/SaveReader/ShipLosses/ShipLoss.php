<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader\ShipLosses;

use Mistralys\X4\SaveViewer\Data\SaveReader\GameTime;

class ShipLoss
{
    private GameTime $time;
    private string $name;
    private string $code;
    private string $location;
    private string $commander;
    private string $destroyedBy;

    public function __construct(GameTime $time, string $shipName, string $shipCode, string $location, string $commander, string $destroyedBy)
    {
        $this->time = $time;
        $this->name = $shipName;
        $this->code = $shipCode;
        $this->location = $location;
        $this->commander = $commander;
        $this->destroyedBy = $destroyedBy;
    }

    public function getTime() : GameTime
    {
        return $this->time;
    }

    public function getShipName() : string
    {
        return $this->name;
    }

    public function getShipCode() : string
    {
        return $this->code;
    }

    public function getLocation() : string
    {
        return $this->location;
    }

    public function getCommander() : string
    {
        return $this->commander;
    }

    public function getDestroyedBy() : string
    {
        return $this->destroyedBy;
    }
}

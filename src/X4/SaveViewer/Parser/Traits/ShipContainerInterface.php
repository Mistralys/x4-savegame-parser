<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Traits;

use Mistralys\X4\SaveViewer\Parser\Types\ClusterType;
use Mistralys\X4\SaveViewer\Parser\Types\SectorType;
use Mistralys\X4\SaveViewer\Parser\Types\ShipType;
use Mistralys\X4\SaveViewer\Parser\Types\ZoneType;

interface ShipContainerInterface extends ComponentInterface
{
    public const KEY_SHIPS = 'ships';

    /**
     * @param ShipType $ship
     * @return $this
     */
    public function addShip(ShipType $ship) : self;

    public function getSector() : SectorType;

    public function getZone() : ZoneType;

    public function getCluster() : ClusterType;
}

<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader\KhaakStations;

use AppUtils\ArrayDataCollection;
use Mistralys\X4\SaveViewer\Parser\DataProcessing\Processors\KhaakStationsList;
use function AppLocalize\t;

class KhaakStation extends ArrayDataCollection
{
    private KhaakSector $sector;

    public function __construct(KhaakSector $sector, array $data)
    {
        $this->sector = $sector;
        parent::__construct($data);
    }

    public function getSector() : KhaakSector
    {
        return $this->sector;
    }

    public function getTypeID() : string
    {
        return $this->getString(KhaakStationsList::KEY_STATION_TYPE);
    }

    public function isHive() : bool
    {
        return $this->getTypeID() === KhaakStationsList::TYPE_HIVE;
    }

    public function isNest() : bool
    {
        return $this->getTypeID() === KhaakStationsList::TYPE_NEST;
    }

    public function getTypeLabel() : string
    {
        if($this->getTypeID() === KhaakStationsList::TYPE_HIVE) {
            return t('Hive');
        }

        return t('Nest');
    }

    /**
     * Get the zone name where the station is located.
     * Fallback to "Unknown" if zone information is not available.
     */
    public function getZoneName() : string
    {
        $zoneName = $this->getString(KhaakStationsList::KEY_ZONE_NAME);
        return !empty($zoneName) ? $zoneName : t('Unknown');
    }

    /**
     * Get the station name/label.
     * Returns the type label (Hive/Nest) as stations don't have custom names.
     */
    public function getName() : string
    {
        $name = $this->getString(KhaakStationsList::KEY_STATION_NAME);
        if(!empty($name)) {
            return $name;
        }
        return $this->getTypeLabel();
    }
}

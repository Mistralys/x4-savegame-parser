<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader\KhaakStations;

use AppUtils\ArrayDataCollection;
use Mistralys\X4\SaveViewer\Parser\DataProcessing\Processors\KhaakStationsList;

class KhaakSector extends ArrayDataCollection
{
    public function getName() : string
    {
        $name = $this->getString(KhaakStationsList::KEY_SECTOR_NAME);

        if(!empty($name)) {
            return $name;
        }

        return $this->getConnectionID();
    }

    public function getConnectionID() : string
    {
        return $this->getString(KhaakStationsList::KEY_SECTOR_CONNECTION_ID);
    }
}

<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader;

use AppUtils\FileHelper\JSONFile;
use Mistralys\X4\SaveViewer\Data\SaveReader\KhaakStations\KhaakSector;
use Mistralys\X4\SaveViewer\Parser\DataProcessing\Processors\KhaakStationsList;

class KhaakStationsReader extends Info
{
    /**
     * @var KhaakSector[]
     */
    private array $sectors = array();

    protected function init() : void
    {
        $data = JSONFile::factory($this->collections->getOutputFolder().'/data-'.KhaakStationsList::FILE_ID.'.json')->parse();

        foreach($data as $sectorData)
        {
            $this->sectors[] = new KhaakSector($sectorData);
        }
    }

    /**
     * @return KhaakSector[]
     */
    public function getSectors() : array
    {
        return $this->sectors;
    }
}

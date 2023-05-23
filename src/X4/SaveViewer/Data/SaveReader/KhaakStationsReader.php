<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader;

use AppUtils\FileHelper\JSONFile;
use Mistralys\X4\SaveViewer\Data\SaveReader\KhaakStations\KhaakSector;
use Mistralys\X4\SaveViewer\Parser\DataProcessing\Processors\KhaakStationsList;
use Mistralys\X4\SaveViewer\UI\Pages\ViewSave\KhaakOverviewPage;
use Mistralys\X4\UI\Page\BasePage;

class KhaakStationsReader extends Info
{
    /**
     * @var KhaakSector[]
     */
    private array $sectors = array();

    protected function init() : void
    {
        if(!$this->reader->dataExists(KhaakStationsList::FILE_ID)) {
            return;
        }

        $data = $this->reader->getRawData(KhaakStationsList::FILE_ID);

        foreach($data as $sectorData)
        {
            $this->sectors[] = new KhaakSector($sectorData);
        }
    }

    public function getURLView() : string
    {
        return $this->reader->getSaveFile()->getURLView(array(
            BasePage::REQUEST_PARAM_VIEW => KhaakOverviewPage::URL_NAME
        ));
    }

    /**
     * @return KhaakSector[]
     */
    public function getSectors() : array
    {
        return $this->sectors;
    }

    public function countStations() : int
    {
        $sectors = $this->getSectors();
        $total = 0;

        foreach($sectors as $sector)
        {
            $total += $sector->countHives() + $sector->countNests();
        }

        return $total;
    }
}

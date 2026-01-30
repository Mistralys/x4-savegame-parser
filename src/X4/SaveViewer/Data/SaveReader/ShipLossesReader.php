<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader;

use Mistralys\X4\SaveViewer\Data\SaveReader\ShipLosses\ShipLoss;
use Mistralys\X4\SaveViewer\Parser\DataProcessing\Processors\DetectShipLosses;
use Mistralys\X4\SaveViewer\UI\Pages\ViewSave\KhaakOverviewPage;
use Mistralys\X4\SaveViewer\UI\Pages\ViewSave\Losses;
use Mistralys\X4\UI\Page\BasePage;

class ShipLossesReader extends Info
{
    /**
     * @var ShipLoss[]
     */
    private array $entries = array();

    protected function init() : void
    {
        $fileID = 'data-'.DetectShipLosses::FILE_ID;

        if(!$this->reader->dataExists($fileID)) {
            return;
        }

        $data = $this->reader->getRawData($fileID);

        foreach($data as $entry)
        {
            $this->entries[] = new ShipLoss(
                $this->reader->createTime($entry[DetectShipLosses::KEY_TIME]),
                $entry[DetectShipLosses::KEY_SHIP_NAME],
                $entry[DetectShipLosses::KEY_SHIP_CODE],
                $entry[DetectShipLosses::KEY_LOCATION],
                $entry[DetectShipLosses::KEY_COMMANDER],
                $entry[DetectShipLosses::KEY_DESTROYED_BY]
            );
        }

        usort($this->entries, static function (ShipLoss $a, ShipLoss $b) : float {
            return $a->getTime()->getDuration() - $b->getTime()->getDuration();
        });
    }

    /**
     * @param int $lastXHours If higher than 0, only losses within this amount of hours will be included.
     * @return int
     */
    public function countLosses(int $lastXHours=0) : int
    {
        if($lastXHours === 0)
        {
            return count($this->entries);
        }

        $total = 0;
        foreach($this->entries as $entry) {
            if($entry->getTime()->getHours() <= $lastXHours) {
                $total++;
            }
        }

        return $total;
    }

    /**
     * @return ShipLoss[]
     */
    public function getLosses() : array
    {
        return $this->entries;
    }

    public function getURLView() : string
    {
        return $this->reader->getSaveFile()->getURLView(array(
            BasePage::REQUEST_PARAM_VIEW => Losses::URL_NAME
        ));
    }

    /**
     * Convert ship losses to array suitable for CLI API output.
     *
     * @return array<int,array<string,mixed>> JSON-serializable array of loss entries
     */
    public function toArrayForAPI(): array
    {
        $result = [];

        foreach ($this->entries as $entry) {
            $result[] = [
                'time' => $entry->getTime()->getDuration(),
                'timeFormatted' => $entry->getTime()->getIntervalStr(),
                'shipName' => $entry->getShipName(),
                'shipCode' => $entry->getShipCode(),
                'location' => $entry->getLocation(),
                'commander' => $entry->getCommander(),
                'destroyedBy' => $entry->getDestroyedBy()
            ];
        }

        return $result;
    }
}

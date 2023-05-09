<?php
/**
 * @package X4SaveViewer
 * @subpackage Parser
 * @see \Mistralys\X4\SaveViewer\Parser\DataProcessing\Processors\KhaakStationsList
 */

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\DataProcessing\Processors;

use Mistralys\X4\SaveViewer\Parser\DataProcessing\BaseDataProcessor;
use Mistralys\X4\SaveViewer\Parser\Types\SectorType;
use Mistralys\X4\SaveViewer\Parser\Types\StationType;

/**
 * Identifies all Khaak stations in the universe,
 * and compiles a list with sector names to easily
 * find them ingame, together with a list of player
 * assets in these systems that may be at risk.
 *
 * Generates the file <code>data-khaak-stations.json</code>.
 *
 * @package X4SaveViewer
 * @subpackage Parser
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */
class KhaakStationsList extends BaseDataProcessor
{
    private array $data = array();

    protected function _process() : void
    {
        $stations = $this->collections->stations()->getAll();

        foreach($stations as $station)
        {
            if($station->getOwner() !== 'khaak') {
                continue;
            }

            $this->processStation($station);
        }

        $this->saveAsJSON($this->data, 'khaak-stations');
    }

    private function processStation(StationType $station) : void
    {
        $macro = $station->getMacro();

        // Ignore installations that have already been wrecked
        if($station->isWreck()) {
            return;
        }

        // Ignore the individual weapon platforms
        if(strpos($macro, 'weaponplatform') !== false) {
            return;
        }

        $sector = $station->getSector();
        $sectorID = $sector->getUniqueID();

        if(!isset($this->data[$sectorID])) {
            $this->data[$sectorID] = array(
                'sectorName' => $sector->getName(),
                'sectorID' => $sector->getUniqueID(),
                'sectorConnectionID' => $sector->getConnectionID(),
                'khaakStations' => array(),
                'playerAssets' => $this->resolveSectorAssets($sector)
            );
        }

        $type = 'nest';
        if(strpos($macro, 'kha_hive') !== false)
        {
            $type = 'hive';
        }

        $this->data[$sectorID]['khaakStations'][] = array(
            'stationID' => $station->getUniqueID(),
            'type' => $type
        );
    }

    private function resolveSectorAssets(SectorType $sector) : array
    {
        $result = array(
            'ships' => array(),
            'stations' => array()
        );

        $stations = $sector->getPlayerStations();
        $ships = $sector->getPlayerShips();

        foreach($stations as $playerStation)
        {
            $result['stations'][] = array(
                'id' => $playerStation->getUniqueID(),
                'name' => $playerStation->getLabel()
            );
        }

        foreach($ships as $ship) {
            $result['ships'][] = array(
                'id' => $ship->getUniqueID(),
                'name' => $ship->getLabel(),
                'size' => $ship->getSize()
            );
        }

        return $result;
    }
}

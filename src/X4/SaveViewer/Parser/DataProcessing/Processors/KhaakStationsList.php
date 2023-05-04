<?php
/**
 * @package X4SaveViewer
 * @subpackage Parser
 * @see \Mistralys\X4\SaveViewer\Parser\DataProcessing\Processors\KhaakStationsList
 */

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\DataProcessing\Processors;

use Mistralys\X4\SaveViewer\Parser\DataProcessing\BaseDataProcessor;
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
        $data = array();

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

        // Ignore the individual weapon platforms
        if(strpos($macro, 'weaponplatform') !== false) {
            return;
        }

        $sector = $station->getSector();

        $type = 'nest';
        if(strpos($macro, 'kha_hive') !== false)
        {
            $type = 'hive';
        }

        $this->data[] = array(
            'type' => $type,
            'sectorName' => $sector->getName(),
            'sectorID' => $sector->getUniqueID(),
            'sectorConnectionID' => $sector->getConnectionID(),
            'playerAssets' => $this->resolveAssets($station)
        );
    }

    private function resolveAssets(StationType $khaakStation) : array
    {
        $result = array(
            'ships' => array(),
            'stations' => array()
        );

        $sector = $khaakStation->getSector();

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

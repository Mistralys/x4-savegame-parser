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
    public const FILE_ID = 'khaak-stations';
    const KEY_SECTOR_NAME = 'sectorName';
    const KEY_SECTOR_ID = 'sectorID';
    const KEY_SECTOR_CONNECTION_ID = 'sectorConnectionID';
    const KEY_STATIONS = 'khaakStations';
    const KEY_PLAYER_ASSETS = 'playerAssets';
    const TYPE_NEST = 'nest';
    const TYPE_HIVE = 'hive';
    const KEY_STATION_ID = 'stationID';
    const KEY_STATION_TYPE = 'type';
    const KEY_PLAYER_SHIPS = 'ships';
    const KEY_PLAYER_STATIONS = 'stations';

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

        $this->saveAsJSON($this->data, self::FILE_ID);
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
                self::KEY_SECTOR_NAME => $sector->getName(),
                self::KEY_SECTOR_ID => $sector->getUniqueID(),
                self::KEY_SECTOR_CONNECTION_ID => $sector->getConnectionID(),
                self::KEY_STATIONS => array(),
                self::KEY_PLAYER_ASSETS => $this->resolveSectorAssets($sector)
            );
        }

        $type = self::TYPE_NEST;
        if(strpos($macro, 'kha_hive') !== false)
        {
            $type = self::TYPE_HIVE;
        }

        $this->data[$sectorID][self::KEY_STATIONS][] = array(
            self::KEY_STATION_ID => $station->getUniqueID(),
            self::KEY_STATION_TYPE => $type
        );
    }

    private function resolveSectorAssets(SectorType $sector) : array
    {
        $result = array(
            self::KEY_PLAYER_SHIPS => array(),
            self::KEY_PLAYER_STATIONS => array()
        );

        $stations = $sector->getPlayerStations();
        $ships = $sector->getPlayerShips();

        foreach($stations as $playerStation)
        {
            $result[self::KEY_PLAYER_STATIONS][] = array(
                'id' => $playerStation->getUniqueID(),
                'name' => $playerStation->getLabel()
            );
        }

        foreach($ships as $ship) {
            $result[self::KEY_PLAYER_SHIPS][] = array(
                'id' => $ship->getUniqueID(),
                'name' => $ship->getLabel(),
                'size' => $ship->getSize()
            );
        }

        return $result;
    }
}

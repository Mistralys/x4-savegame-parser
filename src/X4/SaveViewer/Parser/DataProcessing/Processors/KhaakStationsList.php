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
use Mistralys\X4\SaveViewer\Parser\Types\ZoneType;

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
    public const KEY_SECTOR_NAME = 'sectorName';
    public const KEY_SECTOR_ID = 'sectorID';
    public const KEY_SECTOR_CONNECTION_ID = 'sectorConnectionID';
    public const KEY_STATIONS = 'khaakStations';
    public const KEY_PLAYER_ASSETS = 'playerAssets';
    public const TYPE_NEST = 'nest';
    public const TYPE_HIVE = 'hive';
    public const KEY_STATION_ID = 'stationID';
    public const KEY_STATION_TYPE = 'type';
    public const KEY_ZONE_NAME = 'zoneName';
    public const KEY_STATION_NAME = 'stationName';
    public const KEY_PLAYER_SHIPS = 'ships';
    public const KEY_PLAYER_STATIONS = 'stations';

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

        $zoneName = $station->getZone()->getString(ZoneType::KEY_CODE);
        if(empty($zoneName)) {
            $zoneName = $station->getZone()->getConnectionID();
        }

        $this->data[$sectorID][self::KEY_STATIONS][] = array(
            self::KEY_STATION_ID => $station->getUniqueID(),
            self::KEY_STATION_TYPE => $type,
            self::KEY_ZONE_NAME => $zoneName,
            self::KEY_STATION_NAME => $station->getName()
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

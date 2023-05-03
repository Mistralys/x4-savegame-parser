<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Fragment;

use DOMDocument;
use DOMElement;
use Mistralys\X4\SaveViewer\Parser\BaseDOMFragment;
use Mistralys\X4\SaveViewer\Parser\ConnectionComponent;
use Mistralys\X4\SaveViewer\Parser\Types\BaseComponentType;
use Mistralys\X4\SaveViewer\Parser\Types\CelestialBodyType;
use Mistralys\X4\SaveViewer\Parser\Types\ClusterType;
use Mistralys\X4\SaveViewer\Parser\Types\PersonType;
use Mistralys\X4\SaveViewer\Parser\Types\RegionType;
use Mistralys\X4\SaveViewer\Parser\Types\SectorType;
use Mistralys\X4\SaveViewer\Parser\Types\ShipType;
use Mistralys\X4\SaveViewer\Parser\Types\StationType;
use Mistralys\X4\SaveViewer\Parser\Types\ZoneType;

class ClusterConnectionFragment extends BaseDOMFragment
{
    protected function parseDOM(DOMDocument $dom) : void
    {
        $el = $this->checkIsElement($dom->firstChild, 'connection');
        if($el === null) {
            echo $this->getXMLFile().PHP_EOL;
        }
        $entry = $this->findConnectionComponent($this->checkIsElement($dom->firstChild, 'connection'));

        if($entry === null)
        {
            return;
        }

        $cluster = $this->collections->clusters()->createCluster($entry->connectionID, $entry->componentID);
        $cluster->setCode($entry->componentNode->getAttribute('code'));

        $connections = $this->findConnectionComponents($entry->componentNode);

        foreach($connections as $connection)
        {
            $this->parseClusterConnection($cluster, $connection);
        }
    }

    private function parseClusterConnection(ClusterType $cluster, ConnectionComponent $entry) : void
    {
        // TODO Find out how to recognize sectors
        // TODO Find out what zones actually are

        $class = $entry->componentNode->getAttribute('class');

        switch($class)
        {
            case 'sector':
                $sector = $this->collections->sectors()->createSector($cluster, $entry->connectionID, $entry->componentID);
                $cluster->addSector($sector);
                $this->parseClusterSector($sector, $entry->componentNode);
                break;

            case 'region':
                $region = $this->collections->regions()->createRegion($cluster, $entry->connectionID, $entry->componentID);
                $cluster->addRegion($region);
                $this->parseClusterRegion($region, $entry->componentNode);
                break;

            case 'celestialbody':
                $celestial = $this->collections->celestials()->createCelestial($cluster, $entry->connectionID, $entry->componentID);
                $cluster->addCelestial($celestial);
                $this->parseClusterCelestialBody($celestial, $entry->componentNode);
                break;
        }
    }

    private function parseClusterSector(SectorType $sector, DOMElement $component) : void
    {
        $sector->setOwner($component->getAttribute('owner'));

        $items = $this->findConnectionComponents($component);

        foreach($items as $item)
        {
            $this->parseSectorConnection($sector, $item);
        }
    }

    private function parseSectorConnection(SectorType $sector, ConnectionComponent $entry) : void
    {
        // Ignore these connection IDs.
        if($entry->connectionID === 'adjacentregions') {
            return;
        }

        if($entry->componentClass === 'zone')
        {
            $zone = $this->collections->zones()->createZone($sector, $entry->connectionID, $entry->componentID);
            $sector->addZone($zone);
            $this->parseSectorZone($zone, $entry->componentNode);
        }
    }

    private function parseSectorZone(ZoneType $zone, DOMElement $component) : void
    {
        $zone->setCode($component->getAttribute('code'));

        $items = $this->findConnectionComponents($component);

        foreach($items as $item)
        {
            $this->parseSectorZoneConnection($zone, $item);
        }
    }

    private function parseSectorZoneConnection(ZoneType $zone, ConnectionComponent $entry) : void
    {
        switch($entry->connectionID)
        {
            // TODO: Add gates

            case 'ships':
                $ship = $this->collections->ships()->createShip($zone, $entry->connectionID, $entry->componentID);
                $zone->addShip($ship);
                $this->parseShipComponent($ship, $entry->componentNode);
                break;

            case 'stations':
                $station = $this->collections->stations()->createStation($zone, $entry->connectionID, $entry->componentID);
                $zone->addStation($station);
                $this->parseStationComponent($station, $entry->componentNode);
                break;
        }
    }

    // region: Stations

    private function parseStationComponent(StationType $station, DOMElement $component) : void
    {
        $station
            ->setName($component->getAttribute('name'))
            ->setMacro($component->getAttribute('macro'))
            ->setOwner($component->getAttribute('owner'))
            ->setCode($component->getAttribute('code'));

        $connections = $this->findConnectionComponents($component);

        foreach($connections as $connection)
        {
            if($connection->componentClass === 'buildmodule')
            {
                $this->parseStationBuildModule($connection);
            }
        }

        // TODO: Add productions
    }

    private function parseStationBuildModule(ConnectionComponent $component) : void
    {
        $connections = $this->findConnectionComponents($component);

        foreach($connections as $connection)
        {
            if($connection->componentClass === 'dockingbay')
            {
                $this->parseStationDockingBay($connection);
            }
        }
    }

    private function parseStationDockingBay(ConnectionComponent $component) : void
    {
        
    }

    // endregion

    // region: Ships

    private function parseShipComponent(ShipType $ship, DOMElement $component) : void
    {
        // TODO: Find out why some player ships have no name

        $ship
            ->setName($component->getAttribute('name'))
            ->setState($component->getAttribute('state'))
            ->setMacro($component->getAttribute('macro'))
            ->setOwner($component->getAttribute('owner'))
            ->setCode($component->getAttribute('code'))
            ->setClass($component->getAttribute('class'))
            ->setCover($component->getAttribute('cover'));

        $items = $this->findConnectionComponents($component);

        foreach($items as $item)
        {
            $this->parseShipConnection($ship, $item);
        }

        $people = $this->getFirstChildByName($component, 'people');

        if($people !== null)
        {
            foreach($people->childNodes as $childNode)
            {
                $this->parseShipPerson($ship, $this->checkIsElement($childNode));
            }
        }
    }

    private function parseShipConnection(ShipType $ship, ConnectionComponent $entry) : void
    {
        if($entry->componentClass === 'cockpit') {
            $this->parseShipCockpit($ship, $entry->componentNode);
        }
    }

    private function parseShipCockpit(ShipType $ship, DOMElement $componentNode) : void
    {
        $items = $this->findConnectionComponents($componentNode);

        foreach($items as $item)
        {
            $this->parseShipCockpitConnection($ship, $item);
        }
    }

    private function parseShipCockpitConnection(ShipType $ship, ConnectionComponent $entry) : void
    {
        if($entry->connectionID === 'entities' && $entry->componentClass === 'npc')
        {
            $this->parseShipCaptain($ship, $entry);
        }

        if($entry->connectionID === 'player')
        {
            $this->parsePlayer($entry, $ship);
        }
    }

    private function parseShipCaptain(ShipType $ship, ConnectionComponent $entry) : void
    {
        $pilot = $this->collections->people()->createPerson($ship, $entry->componentAttr('name'))
            ->setRole(PersonType::ROLE_CAPTAIN)
            ->setCode($entry->componentAttr('code'))
            ->setMacro($entry->componentAttr('macro'))
            ->setCover($entry->componentAttr('cover'));

        $seed = $this->getFirstChildByName($entry->componentNode, 'npcseed');
        if($seed !== null) {
            $pilot->setSeed($seed->getAttribute('seed'));
        }

        $ship->setPilot($pilot);
    }

    private function parseShipPerson(ShipType $ship, ?DOMElement $personNode) : void
    {
        if($personNode === null) {
            return;
        }

        $person = $this->collections->people()->createPerson($ship);

        $ship->addPerson($person);

        $person->setMacro($personNode->getAttribute('macro'));
        $person->setRole($personNode->getAttribute('role'));

        foreach($personNode->childNodes as $childNode)
        {
            $element = $this->checkIsElement($childNode);

            if($element === null) {
                continue;
            }

            if($element->nodeName === 'npcseed') {
                $person->setSeed($element->getAttribute('seed'));
                continue;
            }

            if($element->nodeName === 'skill') {
                $person->setSkillLevel(
                    $element->getAttribute('type'),
                    (int)$element->getAttribute('value')
                );
            }
        }
    }

    // endregion

    private function parseClusterRegion(RegionType $region, DOMElement $component) : void
    {
        $connections = $this->getConnectionNodes($component);

        foreach($connections as $connectionNode) {
            $this->parseAdjacentSector($region, $connectionNode);
        }
    }

    private function parseAdjacentSector(RegionType $region, DOMElement $connection) : void
    {
        $connect = $this->getFirstChildByName($connection, 'connected');
        if($connect === null) {
            return;
        }

        $connectionID = $connection->getAttribute('id');
        $targetConnectionID = $connect->getAttribute('connection');

        $region->addSectorConnection($connectionID, $targetConnectionID);
    }

    private function parseClusterCelestialBody(CelestialBodyType $celestialBody, DOMElement $component) : void
    {
        // TODO: Add celestial body details
    }

    // region: Player

    /**
     * @param ConnectionComponent $entry
     * @param BaseComponentType|null $parent Can be a ship (in one of the ship's rooms, like the cockpit) or a station.
     * @return void
     */
    private function parsePlayer(ConnectionComponent $entry, ?BaseComponentType $parent) : void
    {
        // TODO: Parse player data

        // TODO: Find out all the locations a player can be stored
        // - In space (spacesuit)
        // - In a ship room (brig, etc)
        // - In a special ship room (court or curbs)
        // - In a station room (HQ laboratory, etc)
        // - ...?
    }

    // endregion
}

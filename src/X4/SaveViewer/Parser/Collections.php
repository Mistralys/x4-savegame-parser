<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser;

use AppUtils\FileHelper\FolderInfo;
use Mistralys\X4\SaveViewer\Parser\Collections\BaseCollection;
use Mistralys\X4\SaveViewer\Parser\Collections\CelestialsCollection;
use Mistralys\X4\SaveViewer\Parser\Collections\ClustersCollection;
use Mistralys\X4\SaveViewer\Parser\Collections\EventLogCollection;
use Mistralys\X4\SaveViewer\Parser\Collections\PeopleCollection;
use Mistralys\X4\SaveViewer\Parser\Collections\PlayerCollection;
use Mistralys\X4\SaveViewer\Parser\Collections\RegionsCollection;
use Mistralys\X4\SaveViewer\Parser\Collections\SectorsCollection;
use Mistralys\X4\SaveViewer\Parser\Collections\ShipsCollection;
use Mistralys\X4\SaveViewer\Parser\Collections\StationsCollection;
use Mistralys\X4\SaveViewer\Parser\Collections\ZonesCollection;
use Mistralys\X4\SaveViewer\Parser\Types\BaseComponentType;
use Mistralys\X4\SaveViewer\SaveViewerException;

class Collections
{
    public const int ERROR_INVALID_UNIQUE_ID = 135001;
    public const int ERROR_NO_COMPONENT_FOUND_BY_ID = 135002;

    private FolderInfo $outputFolder;
    private CelestialsCollection $celestials;
    private ClustersCollection $clusters;
    private RegionsCollection $regions;
    private SectorsCollection $sectors;
    private ShipsCollection $ships;
    private StationsCollection $stations;
    private ZonesCollection $zones;
    private PeopleCollection $people;
    private PlayerCollection $player;
    private EventLogCollection $eventLog;

    /**
     * @var BaseCollection[]
     */
    private array $collections = array();

    /**
     * @param FolderInfo $outputFolder The savegame's data storage folder.
     */
    public function __construct(FolderInfo $outputFolder)
    {
        $this->outputFolder = $outputFolder;

        $this->celestials = new CelestialsCollection($this);
        $this->clusters = new ClustersCollection($this);
        $this->regions = new RegionsCollection($this);
        $this->sectors = new SectorsCollection($this);
        $this->ships = new ShipsCollection($this);
        $this->stations = new StationsCollection($this);
        $this->zones = new ZonesCollection($this);
        $this->people = new PeopleCollection($this);
        $this->player = new PlayerCollection($this);
        $this->eventLog = new EventLogCollection($this);

        $this
            ->add($this->celestials)
            ->add($this->clusters)
            ->add($this->regions)
            ->add($this->sectors)
            ->add($this->ships)
            ->add($this->stations)
            ->add($this->zones)
            ->add($this->people)
            ->add($this->player)
            ->add($this->eventLog);
    }

    public function getOutputFolder() : FolderInfo
    {
        return $this->outputFolder;
    }

    private function add(BaseCollection $collection) : self
    {
        $this->collections[] = $collection;
        return $this;
    }

    public function save() : self
    {
        foreach($this->collections as $collection) {
            $collection->save();
        }

        return $this;
    }

    public function eventLog() : EventLogCollection
    {
        return $this->eventLog;
    }

    public function celestials() : CelestialsCollection
    {
        return $this->celestials;
    }

    public function clusters() : ClustersCollection
    {
        return $this->clusters;
    }

    public function regions() : RegionsCollection
    {
        return $this->regions;
    }

    public function sectors() : SectorsCollection
    {
        return $this->sectors;
    }

    public function ships() : ShipsCollection
    {
        return $this->ships;
    }

    public function stations() : StationsCollection
    {
        return $this->stations;
    }

    public function zones() : ZonesCollection
    {
        return $this->zones;
    }

    public function people() : PeopleCollection
    {
        return $this->people;
    }

    public function player() : PlayerCollection
    {
        return $this->player;
    }

    /**
     * @param string $uniqueID
     * @return BaseComponentType|null
     * @throws SaveViewerException {@see self::ERROR_INVALID_UNIQUE_ID}
     */
    public function getByUniqueID(string $uniqueID) : ?BaseComponentType
    {
        $parts = explode(':', $uniqueID);

        if(count($parts) !== 2)
        {
            throw new SaveViewerException(
                ' Invalid component unique ID.',
                sprintf(
                    'Could not parse unique ID [%s].',
                    $uniqueID
                ),
                self::ERROR_INVALID_UNIQUE_ID
            );
        }

        $typeID = $parts[0];
        $componentID = $parts[1];

        foreach($this->collections as $collection)
        {
            if($collection->hasComponentType($typeID))
            {
                return $collection->getComponentByID($typeID, $componentID);
            }
        }

        return null;
    }

    /**
     * @param string $uniqueID
     * @return BaseComponentType
     * @throws SaveViewerException {@see self::ERROR_INVALID_UNIQUE_ID} or {@see self::ERROR_NO_COMPONENT_FOUND_BY_ID}
     */
    public function requireByUniqueID(string $uniqueID) : BaseComponentType
    {
        $component = $this->getByUniqueID($uniqueID);

        if($component !== null) {
            return $component;
        }

        throw new SaveViewerException(
            'Component not found by unique ID.',
            sprintf(
                'Could not find any component matching the ID [%s].',
                $uniqueID
            ),
            self::ERROR_NO_COMPONENT_FOUND_BY_ID
        );
    }
}

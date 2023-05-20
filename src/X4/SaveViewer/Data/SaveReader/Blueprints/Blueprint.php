<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader\Blueprints;

use Mistralys\X4\Database\Races\RaceDef;
use Mistralys\X4\Database\Races\RaceDefs;

class Blueprint
{
    private string $name;

    private BlueprintCategory $category;
    private ?RaceDef $race = null;

    public function __construct(BlueprintCategory $category, string $name)
    {
        $this->category = $category;
        $this->name = $name;

        $this->detectRace();
    }

    public function getRace() : ?RaceDef
    {
        return $this->race;
    }

    public function getRaceID() : string
    {
        if(isset($this->race)) {
            return $this->race->getID();
        }

        return RaceDefs::RACE_GENERIC;
    }

    public function detectRace() : void
    {
        $collection = RaceDefs::getInstance();
        $raceIDs = $collection->getIDs();
        $parts = explode('_', $this->getName());

        foreach($raceIDs as $raceID)
        {
            if(in_array($raceID, $parts, true)) {
                $this->race = $collection->getByID($raceID);
                return;
            }
        }
    }

    /**
     * @return BlueprintCategory
     */
    public function getCategory(): BlueprintCategory
    {
        return $this->category;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}

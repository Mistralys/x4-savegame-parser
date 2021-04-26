<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\Data\SaveReader\Blueprints;

use Mistralys\X4Saves\Data\SaveReader\Blueprints;

class BlueprintCategory
{
    private string $id;
    
    private static array $labels = array(
        Blueprints::CATEGORY_MODULES => 'Station modules',
        Blueprints::CATEGORY_SHIELDS => 'Shields',
        Blueprints::CATEGORY_WEAPONS => 'Weapons',
        Blueprints::CATEGORY_TURRETS => 'Turrets',
        Blueprints::CATEGORY_ENGINES => 'Engines',
        Blueprints::CATEGORY_SHIPS => 'Ships',
        Blueprints::CATEGORY_THRUSTERS => 'Thrusters',
        Blueprints::CATEGORY_DEPLOYABLES => 'Deployables',
        Blueprints::CATEGORY_MODIFICATIONS => 'Modifications',
        Blueprints::CATEGORY_SKINS => 'Skins',
        Blueprints::CATEGORY_COUNTERMEASURES => 'Countermeasures',
        Blueprints::CATEGORY_MISSILES => 'Missiles',
        Blueprints::CATEGORY_UNKNOWN => 'Unknown'
    );

    /**
     * @var Blueprint[]
     */
    private array $blueprints = array();

    private bool $sorted = false;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getID(): string
    {
        return $this->id;
    }

    public function addBlueprint(string $name) : void
    {
        $this->blueprints[] = new Blueprint($this, $name);
    }

    public function getBlueprints() : array
    {
        if(!$this->sorted) {
            usort($this->blueprints, function (Blueprint $a, Blueprint $b) {
                return strnatcasecmp($a->getName(), $b->getName());
            });
            $this->sorted = true;
        }

        return $this->blueprints;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        if(isset(self::$labels[$this->id])) {
            return self::$labels[$this->id];
        }

        return self::$labels[Blueprints::CATEGORY_UNKNOWN];
    }
}

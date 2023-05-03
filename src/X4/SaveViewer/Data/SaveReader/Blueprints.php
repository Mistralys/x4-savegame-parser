<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader;

use Mistralys\X4\SaveViewer\Data\SaveReader\Blueprints\BlueprintCategory;
use Mistralys\X4\SaveViewer\Parser\Tags\Tag\PlayerComponentTag;

class Blueprints extends Info
{
    const CATEGORY_MODULES = 'modules';
    const CATEGORY_SHIELDS = 'shields';
    const CATEGORY_WEAPONS = 'weapons';
    const CATEGORY_TURRETS = 'turrets';
    const CATEGORY_ENGINES = 'engines';
    const CATEGORY_SHIPS = 'ships';
    const CATEGORY_THRUSTERS = 'thruster';
    const CATEGORY_DEPLOYABLES = 'deployables';
    const CATEGORY_MODIFICATIONS = 'modifications';
    const CATEGORY_SKINS = 'skins';
    const CATEGORY_COUNTERMEASURES = 'countermeasures';
    const CATEGORY_MISSILES = 'missiles';
    const CATEGORY_UNKNOWN = 'unknown';

    /**
     * @var array<string,string>
     */
    protected array $partDefs = array(
        'turret' => Blueprints::CATEGORY_TURRETS,
        'ship' => Blueprints::CATEGORY_SHIPS,
        'shield' => Blueprints::CATEGORY_SHIELDS,
        'module' => Blueprints::CATEGORY_MODULES,
        'engine' => Blueprints::CATEGORY_ENGINES,
        'mod' => Blueprints::CATEGORY_MODIFICATIONS,
        'weapon' => Blueprints::CATEGORY_WEAPONS,
        'satellite' => Blueprints::CATEGORY_DEPLOYABLES,
        'resourceprobe' => Blueprints::CATEGORY_DEPLOYABLES,
        'waypointmarker' => Blueprints::CATEGORY_DEPLOYABLES,
        'survey' => Blueprints::CATEGORY_DEPLOYABLES,
        'paintmod' => Blueprints::CATEGORY_SKINS,
        'clothingmod' => Blueprints::CATEGORY_SKINS,
        'countermeasure' => Blueprints::CATEGORY_COUNTERMEASURES,
        'missile' => Blueprints::CATEGORY_MISSILES,
        'thruster' => Blueprints::CATEGORY_THRUSTERS
    );

    /**
     * @var BlueprintCategory[]
     */
    private array $categories = array();


    protected function getAutoDataName(): string
    {
        return PlayerComponentTag::SAVE_NAME;
    }

    protected function init() : void
    {
        $blueprintIDs = $this->data[PlayerComponentTag::KEY_BLUEPRINTS];

        foreach($blueprintIDs as $blueprintID)
        {
            $this->addBlueprint($blueprintID);
        }

        usort($this->categories, function (BlueprintCategory $a, BlueprintCategory $b) {
            return strnatcasecmp($a->getLabel(), $b->getLabel());
        });
    }

    private function getCategory(string $categoryID) : BlueprintCategory
    {
        if(!isset($this->categories[$categoryID])) {
            $this->categories[$categoryID] = new BlueprintCategory($categoryID);
        }

        return $this->categories[$categoryID];
    }

    /**
     * @return BlueprintCategory[]
     */
    public function getCategories() : array
    {
        return $this->categories;
    }

    private function addBlueprint(string $blueprintID) : void
    {
        $parts = explode('_', $blueprintID);
        $type = array_shift($parts);
        $categoryID = self::CATEGORY_UNKNOWN;

        if(isset($this->partDefs[$type])) {
            $categoryID = $this->partDefs[$type];
        }

        $this->getCategory($categoryID)->addBlueprint($blueprintID);
    }
}
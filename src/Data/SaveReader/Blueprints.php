<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\Data\SaveReader;

use Mistralys\X4Saves\Data\SaveReader\Blueprints\BlueprintCategory;

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
     * @var BlueprintCategory[]
     */
    private array $categories = array();

    protected function getAutoDataName(): string
    {
        return 'blueprints';
    }

    protected function init() : void
    {
        foreach ($this->data as $categoryID => $items)
        {
            $category = new BlueprintCategory($categoryID);

            foreach ($items as $item) {
                $category->addBlueprint($item);
            }

            $this->categories[] = $category;
        }

        usort($this->categories, function (BlueprintCategory $a, BlueprintCategory $b) {
            return strnatcasecmp($a->getLabel(), $b->getLabel());
        });
    }

    public function getCategories() : array
    {
        return $this->categories;
    }
}

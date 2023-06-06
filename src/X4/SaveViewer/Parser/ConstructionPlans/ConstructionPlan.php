<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\ConstructionPlans;

use DOMElement;
use Mistralys\X4\Database\Blueprints\Categories\ModuleCategory;
use Mistralys\X4\Database\Modules\ModuleCategories;
use Mistralys\X4\Database\Modules\ModuleException;

class ConstructionPlan
{
    private DOMElement $element;

    /**
     * @var PlanModule[]|null
     */
    private ?array $modules = null;

    public function __construct(DOMElement $element)
    {
        $this->element = $element;
    }

    public function getLabel() : string
    {
        return $this->element->getAttribute('name');
    }

    public function countElements() : int
    {
        return count($this->detectModules());
    }

    public function countProductions() : int
    {
        return count($this->getProductions());
    }

    public function getHabitats() : array
    {
        return $this->getByCategories(array(
            ModuleCategories::CATEGORY_HABITATS
        ));
    }

    /**
     * @return PlanModule[]
     * @throws ModuleException
     */
    public function getProductions() : array
    {
        return $this->getByCategories(array(
            ModuleCategories::CATEGORY_PRODUCTION,
            ModuleCategories::CATEGORY_PROCESSING
        ));
    }

    /**
     * @return PlanModule[]
     * @throws ModuleException
     */
    public function getByCategories(array $categoryIDs) : array
    {
        $modules = $this->getModules();
        $result = array();

        foreach($modules as $module)
        {
            $categoryID = $module->getModule()->getCategory()->getID();

            if(in_array($categoryID, $categoryIDs, true)) {
                $result[] = $module;
            }
        }

        return $result;
    }

    /**
     * @return PlanModule[]
     */
    public function getModules() : array
    {
        return $this->detectModules();
    }

    /**
     * @return PlanModule[]
     */
    private function detectModules() : array
    {
        if(isset($this->modules)) {
            return $this->modules;
        }

        $this->modules = array();
        $nodes = $this->element->getElementsByTagName('entry');

        foreach($nodes as $node) {
            if($node instanceof DOMElement) {
                $module = new PlanModule($node);
                if($module->detectModule() !== null) {
                    $this->modules[] = $module;
                }
            }
        }

        return $this->modules;
    }
}

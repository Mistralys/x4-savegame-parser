<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\ConstructionPlans;

use DOMElement;
use Mistralys\X4\Database\Modules\ModuleCategories;
use Mistralys\X4\Database\Modules\ModuleException;
use Mistralys\X4\Database\Races\RaceDef;
use Mistralys\X4\SaveViewer\Parser\ConstructionPlansParser;
use Mistralys\X4\SaveViewer\UI\Pages\ConstructionPlans\PlanSettingsPage;
use Mistralys\X4\SaveViewer\UI\Pages\ViewPlanPage;
use Mistralys\X4\UI\Page\BasePage;

class ConstructionPlan
{
    private DOMElement $element;

    /**
     * @var PlanModule[]|null
     */
    private ?array $modules = null;
    private ConstructionPlansParser $parser;

    public function __construct(ConstructionPlansParser $parser, DOMElement $element)
    {
        $this->parser = $parser;
        $this->element = $element;
    }

    public function getID() : string
    {
        return $this->element->getAttribute('id');
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

    public function getURL(array $params=array()) : string
    {
        $params[BasePage::REQUEST_PARAM_PAGE] = ViewPlanPage::URL_NAME;
        $params[ViewPlanPage::REQUEST_PARAM_PLAN_ID] = $this->getID();

        return '?'.http_build_query($params);
    }


    /**
     * @return RaceDef[]
     */
    public function getRaces() : array
    {
        $result = array();
        $modules = $this->getModules();

        foreach($modules as $module)
        {
            $race = $module->getModule()->getRace();

            if($race->isGeneric()) {
                continue;
            }

            $raceID = $race->getID();

            if(!isset($result[$raceID])) {
                $result[$raceID] = $race;
            }
        }

        return array_values($result);
    }

    public function getURLSettings(array $params=array()) : string
    {
        $params[BasePage::REQUEST_PARAM_VIEW] = PlanSettingsPage::URL_NAME;

        return $this->getURL($params);
    }

    public function setLabel(string $label) : self
    {
        $this->element->setAttribute('name', $label);
        return $this;
    }

    public function save() : self
    {
        $this->parser->save();
        return $this;
    }
}

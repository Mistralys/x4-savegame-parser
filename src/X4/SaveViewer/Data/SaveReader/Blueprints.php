<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader;

use Mistralys\X4\Database\Blueprints\BlueprintCategory;
use Mistralys\X4\Database\Blueprints\BlueprintDef;
use Mistralys\X4\Database\Blueprints\BlueprintDefs;
use Mistralys\X4\Database\Blueprints\BlueprintSelection;
use Mistralys\X4\SaveViewer\Parser\Types\PlayerType;
use Mistralys\X4\SaveViewer\UI\Pages\ViewSave\BlueprintsPage;

class Blueprints extends Info
{
    /**
     * @var array<string,BlueprintDef>
     */
    private array $blueprints = array();

    /**
     * @var array<string,BlueprintCategory>
     */
    private array $categories = array();
    private BlueprintDefs $collection;
    private ?BlueprintSelection $owned = null;
    private ?BlueprintSelection $unowned = null;

    protected function init() : void
    {
        $data = $this->collections->player()->loadData();
        $this->collection = BlueprintDefs::getInstance();

        $blueprintIDs = $data[PlayerType::KEY_BLUEPRINTS];

        foreach($blueprintIDs as $blueprintID)
        {
            $this->addBlueprint($blueprintID);
        }
    }

    /**
     * Fetches a selection of all blueprints owned by the player.
     * @return BlueprintSelection
     */
    public function getOwned() : BlueprintSelection
    {
        if(!isset($this->owned)) {
            $this->owned = BlueprintSelection::create(array_values($this->blueprints));
        }

        return $this->owned;
    }

    /**
     * Fetches a selection of all blueprints not owned by the player.
     * @return BlueprintSelection
     */
    public function getUnowned() : BlueprintSelection
    {
        if(isset($this->unowned)) {
            return $this->unowned;
        }

        $all = BlueprintDefs::getInstance()->getBlueprints();
        $unowned = BlueprintSelection::create();

        foreach($all as $blueprint) {
            if(!$this->isOwned($blueprint)) {
                $unowned->addBlueprint($blueprint);
            }
        }

        $this->unowned = $unowned;

        return $unowned;
    }

    /**
     * @var array<string,bool>
     */
    private array $ownedCache = array();

    public function isOwned(BlueprintDef $blueprint) : bool
    {
        $id = $blueprint->getID();

        if(!isset($this->ownedCache[$id]))
        {
            $this->ownedCache[$id] = in_array($blueprint->getID(), $this->getOwned()->getBlueprintIDs());
        }

        return $this->ownedCache[$id];
    }

    private function addBlueprint(string $blueprintID) : void
    {
        $blueprint = $this->collection->getBlueprintByID($blueprintID);
        $this->blueprints[$blueprintID] = $blueprint;

        $category = $blueprint->getCategory();
        $categoryID = $category->getID();

        if(!isset($this->categories[$categoryID])) {
            $this->categories[$categoryID] = $category;
        }
    }

    public function getURLView(array $params=array()) : string
    {
        $params['view'] = BlueprintsPage::URL_NAME;

        return $this->reader->getSaveFile()->getURLView($params);
    }

    public function getURLShowUnowned() : string
    {
        return $this->getURLView(array(
            BlueprintsPage::REQUEST_PARAM_SHOW_TYPE => BlueprintsPage::SHOW_TYPE_UNOWNED
        ));
    }

    public function getURLShowOwned() : string
    {
        return $this->getURLView(array(
            BlueprintsPage::REQUEST_PARAM_SHOW_TYPE => BlueprintsPage::SHOW_TYPE_OWNED
        ));
    }

    public function getURLGenerateXML(string $type=BlueprintsPage::SHOW_TYPE_ALL) : string
    {
        return $this->getURLView(array(
            BlueprintsPage::REQUEST_PARAM_GENERATE_XML => 'yes',
            BlueprintsPage::REQUEST_PARAM_SHOW_TYPE => $type
        ));
    }

    public function getURLGenerateMarkdown(string $type=BlueprintsPage::SHOW_TYPE_ALL) : string
    {
        return $this->getURLView(array(
            BlueprintsPage::REQUEST_PARAM_GENERATE_MARKDOWN => 'yes',
            BlueprintsPage::REQUEST_PARAM_SHOW_TYPE => $type
        ));
    }
}

<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\UI\Pages;

use Mistralys\X4Saves\Data\SaveFile;
use Mistralys\X4Saves\Data\SaveReader;
use Mistralys\X4Saves\UI\Pages\ViewSave\Blueprints;
use Mistralys\X4Saves\UI\Pages\ViewSave\Factions;
use Mistralys\X4Saves\UI\Pages\ViewSave\Home;
use Mistralys\X4Saves\UI\Pages\ViewSave\Inventory;
use Mistralys\X4Saves\UI\Pages\ViewSave\Losses;
use Mistralys\X4Saves\UI\Pages\ViewSave\Statistics;
use Mistralys\X4Saves\UI\PageWithNav;

class ViewSave extends PageWithNav
{
    protected SaveFile $save;
    protected SaveReader $reader;

    protected function init(): void
    {
        $this->save = $this->requireSave();
        $this->reader = $this->save->getReader();
    }

    public function getDefaultSubPageID() : string
    {
        return Home::URL_PARAM;
    }

    protected function initSubPages() : void
    {
        $this->subPages = array(
            new Home($this),
            new Blueprints($this),
            new Losses($this),
            new Factions($this),
            new Inventory($this),
            new Statistics($this)
        );
    }

    protected function getURLParams() : array
    {
        return array(
            'saveName' => $this->save->getName()
        );
    }

    /**
     * @return SaveFile
     */
    public function getSave() : SaveFile
    {
        return $this->save;
    }

    /**
     * @return SaveReader
     */
    public function getReader() : SaveReader
    {
        return $this->reader;
    }

    public function getTitle(): string
    {
        return 'Savegame: '.$this->save->getName();
    }
}
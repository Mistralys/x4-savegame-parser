<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages;

use Mistralys\X4\SaveViewer\Data\BaseSaveFile;
use Mistralys\X4\SaveViewer\Data\SaveReader;
use Mistralys\X4\SaveViewer\UI\Pages\ViewSave\KhaakOverviewPage;
use Mistralys\X4\SaveViewer\UI\Pages\ViewSave\Blueprints;
use Mistralys\X4\SaveViewer\UI\Pages\ViewSave\Home;
use Mistralys\X4\SaveViewer\UI\PageWithNav;
use function AppLocalize\t;
use function AppUtils\sb;

class ViewSave extends PageWithNav
{
    public const URL_NAME = 'ViewSave';

    protected BaseSaveFile $save;
    protected SaveReader $reader;

    protected function init(): void
    {

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
            new KhaakOverviewPage($this)
            //new Losses($this),
            //new Factions($this),
            //new Inventory($this),
            //new Statistics($this),
            //new Backup($this)
        );
    }

    protected function getURLParams() : array
    {
        return array(
            BaseSaveFile::PARAM_SAVE_ID => $this->save->getSaveID()
        );
    }

    /**
     * @return BaseSaveFile
     */
    public function getSave() : BaseSaveFile
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
        return (string)sb()->t('Savegame:')->add($this->save->getSaveName());
    }

    public function getNavTitle() : string
    {
        return t('View');
    }

    protected function preRender() : void
    {
        $this->save = $this->requireSave();
        $this->reader = $this->save->getDataReader();
    }
}
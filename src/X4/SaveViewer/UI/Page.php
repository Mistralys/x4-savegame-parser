<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI;

use Mistralys\X4\SaveViewer\Data\BaseSaveFile;
use Mistralys\X4\SaveViewer\Data\SaveManager;
use Mistralys\X4\SaveViewer\Parser\SaveSelector;
use Mistralys\X4\UI\Page\BasePage;

abstract class Page extends BasePage
{
    protected SaveManager $manager;

    protected function init() : void
    {
        $this->manager = new SaveManager(SaveSelector::create(
            X4_SAVES_FOLDER,
            X4_STORAGE_FOLDER
        ));
    }

    protected function requireSave() : BaseSaveFile
    {
        $saveName = $this->request->getParam('saveName');

        if(!$this->manager->nameExists($saveName)) {
            $this->redirect('?');
        }

        return $this->manager->getByName($saveName);
    }
}

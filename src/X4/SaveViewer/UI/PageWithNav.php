<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI;

use Mistralys\X4\SaveViewer\Data\BaseSaveFile;
use Mistralys\X4\SaveViewer\Data\SaveManager;
use Mistralys\X4\SaveViewer\SaveViewer;
use Mistralys\X4\UI\Page\BasePageWithNav;
use Mistralys\X4\UI\UserInterface;

/**
 * @method SaveViewer getApplication()
 */
abstract class PageWithNav extends BasePageWithNav
{
    protected SaveManager $saveManager;

    public function __construct(UserInterface $ui)
    {
        parent::__construct($ui);

        $this->saveManager = $this->getApplication()->getSaveManager();
    }

    protected function requireSave() : BaseSaveFile
    {
        $id = $this->request->getParam(BaseSaveFile::PARAM_SAVE_ID);

        if(!$this->saveManager->idExists($id)) {
            $this->redirect('/');
        }

        return $this->saveManager->getByID($id);
    }
}

<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages\ViewSave;

use Mistralys\X4\SaveViewer\UI\MainPage;
use Mistralys\X4\SaveViewer\UI\SavesGridRenderer;
use function AppLocalize\t;

class ArchivedSavesPage extends MainPage
{
    public const URL_NAME = 'ArchivedSaves';

    public function getURLName() : string
    {
        return self::URL_NAME;
    }

    public function getTitle() : string
    {
        return t('Saves archive');
    }

    public function getNavTitle() : string
    {
        return t('Archive');
    }

    protected function _render() : void
    {
        $grid = new SavesGridRenderer($this->ui, $this->getApplication()->getSaveManager()->getArchivedSaves());
        $grid->display();
    }
}

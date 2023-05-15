<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages;

use AppUtils\ConvertHelper;
use Mistralys\X4\SaveViewer\Data\BaseSaveFile;
use Mistralys\X4\SaveViewer\UI\MainPage;
use Mistralys\X4\SaveViewer\UI\Page;
use Mistralys\X4\SaveViewer\UI\Pages\ViewSave\ArchivedSavesPage;
use Mistralys\X4\SaveViewer\UI\SavesGridRenderer;
use Mistralys\X4\UI\Button;
use Mistralys\X4\UI\Icon;
use Mistralys\X4\UI\Page\NavItem;
use Mistralys\X4\UserInterface\DataGrid\DataGrid;
use function AppLocalize\pt;use function AppLocalize\t;
use function AppUtils\sb;

class SavesList extends MainPage
{
    public const URL_NAME = 'SavesList';

    public function getTitle(): string
    {
        return 'Savegames';
    }

    public function getSubtitle() : string
    {
        return '';
    }

    public function getAbstract() : string
    {
        return '';
    }

    protected function _render(): void
    {
        $grid = new SavesGridRenderer($this->ui, $this->manager->getSaves());
        $grid->setColumnEnabled(SavesGridRenderer::COL_SAVE_TYPE, false);
        $grid->display();
    }

    public function getNavTitle() : string
    {
        return t('Overview');
    }
}

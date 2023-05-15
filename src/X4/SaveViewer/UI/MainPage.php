<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI;

use Mistralys\X4\SaveViewer\UI\Pages\SavesList;
use Mistralys\X4\SaveViewer\UI\Pages\ViewSave\ArchivedSavesPage;
use Mistralys\X4\UI\Page\NavItem;
use function AppLocalize\t;

abstract class MainPage extends Page
{
    public function getNavItems(): array
    {
        return array(
            new NavItem(
                t('Main saves'),
                '?page='.SavesList::URL_NAME
            ),
            new NavItem(
                t('Saves archive'),
                '?page='.ArchivedSavesPage::URL_NAME
            )
        );
    }

    protected function preRender() : void
    {
    }

    protected function getURLParams() : array
    {
        return array();
    }
}
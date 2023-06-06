<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI;

use Mistralys\X4\UI\Page\NavItem;
use function AppLocalize\t;

abstract class MainPage extends Page
{
    public function getNavItems(): array
    {
        return array(
            new NavItem(
                t('Main saves'),
                $this->manager->getURLSavesList()
            ),
            new NavItem(
                t('Saves archive'),
                $this->manager->getURLSavesArchive()
            ),
            new NavItem(
                t('Construction plans'),
                $this->manager->getURLConstructionPlans()
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

<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages\ViewSave;

use AppUtils\ConvertHelper;
use AppUtils\FileHelper;
use function AppLocalize\t;

class KhaakOverviewPage extends SubPage
{
    public const URL_PARAM = 'KhaakOverview';

    public function getURLName() : string
    {
        return self::URL_PARAM;
    }

    public function isInSubnav() : bool
    {
        return true;
    }

    public function getTitle() : string
    {
        return t('Khaa\'k Overview');
    }

    public function renderContent() : void
    {
        $sectors = $this->getReader()->getKhaakStations()->getSectors();
        $grid = $this->page->getUI()->createDataGrid();

        $cName = $grid->addColumn('name', t('Name'));

        foreach($sectors as $sector)
        {
            $row = $grid->createRow();

            $row->setValue($cName, $sector->getName());

            $grid->addRow($row);
        }

        echo $grid->render();
    }
}

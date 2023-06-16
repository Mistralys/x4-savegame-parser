<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages\ViewSave;

use AppUtils\ConvertHelper;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log\LogEntry;
use function AppLocalize\pt;use function AppLocalize\t;

class Losses extends BaseViewSaveSubPage
{
    public const URL_NAME = 'Losses';

    public function getURLName() : string
    {
        return self::URL_NAME;
    }

    public function isInSubnav() : bool
    {
        return true;
    }

    public function getTitle() : string
    {
        return t('Ship losses');
    }

    public function getSubtitle() : string
    {
        return '';
    }

    public function getAbstract() : string
    {
        return '';
    }

    public function renderContent() : void
    {
        $losses = $this->getReader()->getShipLosses()->getLosses();

        $grid = $this->page->getUI()->createDataGrid();
        $colTime = $grid->addColumn('time', t('How long ago?'));
        $colName = $grid->addColumn('name', t('Name'));
        $colCode = $grid->addColumn('code', t('Code'));
        $colCommander = $grid->addColumn('commander', t('Commander'));
        $colLocation = $grid->addColumn('location', t('Location'));
        $colWho = $grid->addColumn('who', t('Destroyed by'));

        ?>
        <p><?php pt('Ordered by most recent first.') ?></p>
        <?php

        foreach ($losses as $entry)
        {
            $gridEntry = $grid->createRow()
                ->setValue($colTime, $entry->getTime()->getIntervalStr())
                ->setValue($colName, $entry->getShipName())
                ->setValue($colCode, $entry->getShipCode())
                ->setValue($colCommander, $entry->getCommander())
                ->setValue($colLocation, $entry->getLocation())
                ->setValue($colWho, $entry->getDestroyedBy());

            $grid->addRow($gridEntry);
        }

        $grid->display();
    }
}

<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages;

use AppUtils\ConvertHelper;
use Mistralys\X4\SaveViewer\Data\BaseSaveFile;
use Mistralys\X4\SaveViewer\UI\Page;
use Mistralys\X4\UI\Button;
use Mistralys\X4\UI\Icon;
use Mistralys\X4\UserInterface\DataGrid\DataGrid;
use function AppLocalize\pt;use function AppLocalize\t;
use function AppUtils\sb;

class SavesList extends Page
{
    public const URL_NAME = 'SavesList';

    public function getTitle(): string
    {
        return 'Savegames';
    }

    public function getNavItems(): array
    {
        return array();
    }

    protected function getURLParams() : array
    {
        return array();
    }

    protected function _render(): void
    {
        $saves = $this->manager->getSaves();

        $grid = $this->ui->createDataGrid();

        $cName = $grid->addColumn('name', t('Name'));
        $cChar = $grid->addColumn('character', t('Character'));
        $cMoney = $grid->addColumn('money', t('Money'))
            ->alignRight();
        $cModified = $grid->addColumn('modified', t('Modified'));
        $cBackup = $grid->addColumn('backup', t('Backup?'))
            ->alignCenter();
        $cActions = $grid->addColumn('actions', t('Actions'))
            ->alignRight();

        foreach($saves as $save)
        {
            $row = $grid->createRow();
            $grid->addRow($row);

            if($save->isUnpacked())
            {
                $reader = $save->getDataReader();
                $saveInfo = $reader->getSaveInfo();
                $date = $saveInfo->getSaveDate();

                $row->setValue($cName, sb()->link($saveInfo->getSaveName(), $save->getURLView()));
                $row->setValue($cChar, $saveInfo->getPlayerName());
                $row->setValue($cMoney, $saveInfo->getMoneyPretty());
            }
            else
            {
                $date = $save->getDateModified();

                $row->setValue($cName, $save->getSaveName());
                $row->setValue($cChar, '-');
                $row->setValue($cMoney, '-');
                $row->setValue($cActions,
                    Button::create(t('Unpack'))
                        ->setIcon(Icon::unpack())
                        ->colorPrimary()
                        ->sizeExtraSmall()
                        ->link($save->getURLUnpack())
                );
            }

            /*
            if(!$save->hasBackup())
            {
                echo Button::create(t('Backup'))
                    ->setIcon(Icon::backup())
                    ->sizeSmall()
                    ->link($save->getURLBackup());
            }
            */

            $row->setDate($cModified, $date, true, true);
            $row->setBool($cBackup, $save->hasBackup());
        }

        echo $grid->render();
    }

    public function getNavTitle() : string
    {
        return t('Overview');
    }

    protected function preRender() : void
    {
    }
}

<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages\ViewSave;

use AppUtils\ConvertHelper;
use AppUtils\FileHelper;
use Mistralys\X4\SaveViewer\Data\SaveReader\KhaakStations\KhaakSector;
use Mistralys\X4\UI\Text;
use function AppLocalize\t;
use function AppLocalize\tex;
use function AppUtils\sb;

class KhaakOverviewPage extends ViewSaveSubPage
{
    public const URL_NAME = 'KhaakOverview';

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
        return t('Khaa\'k Overview');
    }

    public function getSubtitle() : string
    {
        return '';
    }

    public function getAbstract() : string
    {
        return (string)sb()
            ->t('This shows all sectors in which Khaa\'k hives or nests can be found.')
            ->t('The amount of player ships and/or stations in those sectors can help decide at a glance which assets are the most at risk.');
    }

    public function renderContent() : void
    {
        $sectors = $this->getReader()->getKhaakStations()->getSectors();
        $grid = $this->page->getUI()->createDataGrid();

        $cName = $grid->addColumn('name', t('Name'));
        $cHive = $grid->addColumn('hives', t('Hives'))->alignCenter();
        $cNest = $grid->addColumn('nests', t('Nests'))->alignCenter();
        $cShips = $grid->addColumn('ships', t('Player ships'))->alignCenter();
        $cStations = $grid->addColumn('stations', t('Player stations'))->alignCenter();

        foreach($sectors as $sector)
        {
            $row = $grid->createRow();

            $row->setValue($cName, $sector->getName());
            $row->setValue($cHive, $this->formatDangerCount($sector->countHives()));
            $row->setValue($cNest, $this->formatDangerCount($sector->countNests()));
            $row->setValue($cShips, $this->formatNeutralCount($sector->countPlayerShips()));
            $row->setValue($cStations, $this->formatNeutralCount($sector->countPlayerStations()));

            $grid->addRow($row);

            $merged = $grid->createMergedRow();
            $merged->setContent($this->renderDetails($sector));

            $grid->addRow($merged);
        }

        echo $grid->render();
    }

    private function formatDangerCount(int $amount) : string
    {
        if($amount === 0) {
            return (string)Text::create('-')->colorMuted();
        }

        return (string)Text::create($amount)->colorDanger();
    }

    private function formatNeutralCount(int $amount) : string
    {
        if($amount === 0) {
            return (string)Text::create('-')->colorMuted();
        }

        return (string)$amount;
    }

    private function renderDetails(KhaakSector $sector) : string
    {
        $ships = $sector->getRawShips();

        if(empty($ships))
        {
            $content = t('No player ships present.');
        }
        else
        {
            $items = array();

            foreach ($ships as $ship)
            {
                $items[] = $ship['name'];
            }

            $content = '<p>' . t('Player ships present:') . '</p><ul><li>' . implode('</li><li>', $items) . '</li></ul>';
        }

        return '<div style="padding-left:20px;font-size: 80%">'.$content.'</div>';
    }
}

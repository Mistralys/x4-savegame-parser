<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages;

use Mistralys\X4\SaveViewer\Parser\ConstructionPlansParser;
use Mistralys\X4\SaveViewer\UI\MainPage;
use Mistralys\X4\UserInterface\DataGrid\DataGrid;
use Mistralys\X4\UserInterface\DataGrid\GridColumn;
use function AppLocalize\t;

class ConstructionPlansPage extends MainPage
{
    public const URL_NAME = 'ConstructionPlans';

    private ConstructionPlansParser $parser;
    private DataGrid $grid;
    private GridColumn $cLabel;
    private GridColumn $cElements;
    private GridColumn $cProductions;
    private GridColumn $cHabitats;

    public function getURLName() : string
    {
        return self::URL_NAME;
    }

    public function getTitle() : string
    {
        return t('Construction plans');
    }

    public function getSubtitle() : string
    {
        return '';
    }

    public function getAbstract() : string
    {
        return t('Shows all construction plans stored for the player.');
    }

    public function getNavTitle() : string
    {
        return t('Plans');
    }

    protected function preRender() : void
    {
        $this->parser = ConstructionPlansParser::createFromConfig();

        $this->createDataGrid();
    }

    protected function _render() : void
    {
        $plans = $this->parser->getPlans();

        foreach($plans as $plan)
        {
            $row = $this->grid->createRow();
            $this->grid->addRow($row);

            $row->setValue($this->cLabel, $plan->getLabel());
            $row->setValue($this->cElements, $this->renderAmount($plan->countElements()));
            $row->setValue($this->cProductions, $this->renderAmount($plan->countProductions()));
            $row->setValue($this->cHabitats, $this->renderAmount(count($plan->getHabitats())));
        }

        $this->grid->display();
    }

    private function renderAmount(int $amount) : string
    {
        if($amount === 0) {
            return '<span class="text-secondary">-</span>';
        }

        return (string)$amount;
    }

    private function createDataGrid() : void
    {
        $grid = $this->ui->createDataGrid();

        $this->cLabel = $grid->addColumn('label', t('Label'));

        $this->cHabitats = $grid->addColumn('habitats', t('Habitats'))
            ->alignRight();

        $this->cProductions = $grid->addColumn('productions', t('Productions'))
            ->alignRight();

        $this->cElements = $grid->addColumn('elements', t('Total elements'))
            ->alignRight();

        $this->grid = $grid;
    }
}

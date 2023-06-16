<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages\ConstructionPlans;

use Mistralys\X4\Database\Modules\ModuleDef;
use Mistralys\X4\SaveViewer\UI\Pages\ViewPlanPage;
use Mistralys\X4\SaveViewer\UI\Pages\ViewSave\BasePlanSubPage;
use function AppLocalize\t;
use function AppUtils\sb;

/**
 * @property ViewPlanPage $page
 */
class PlanOverviewPage extends BasePlanSubPage
{
    public const URL_NAME = 'PlanOverview';

    public function isInSubnav() : bool
    {
        return true;
    }

    public function getURLName() : string
    {
        return self::URL_NAME;
    }

    public function renderContent() : void
    {
        $categories = $this->getCategories();

        $grid = $this->page->getUI()->createDataGrid();
        $cLabel = $grid->addColumn('label', t('Label'));
        $cCount = $grid->addColumn('count', t('Count'));
        $cRace = $grid->addColumn('race', t('Race'));

        ?>
        <style>
            .datagrid-row-merged.heading > .datagrid-merged-cell{
                padding-top: 1rem;
                padding-bottom: 1rem;
            }
        </style>
        <?php

        foreach($categories as $category => $modules)
        {
            $grid->addRow($grid
                ->createMergedRow()
                ->addClass('heading')
                ->setContent(sb()->bold($category)->spanned('- '.$modules['count'], 'text-secondary'))
            );

            foreach($modules['modules'] as $entry)
            {
                $module = $entry['def'];
                $row = $grid->createRow();
                $row->setValue($cLabel, $module->getLabel());
                $row->setValue($cCount, $entry['count']);
                $row->setValue($cRace, $module->getRace()->getLabel());

                $grid->addRow($row);
            }
        }

        $grid->display();
    }

    public function getTitle() : string
    {
        return t('Modules overview');
    }

    public function getSubtitle() : string
    {
        return '';
    }

    public function getAbstract() : string
    {
        return '';
    }

    /**
     * @return array<string,array{count:int,modules:array<string,array{count:int,def:ModuleDef}}>
     */
    private function getCategories() : array
    {
        $result = array();
        $modules = $this->getPlan()->getModules();

        foreach($modules as $module)
        {
            $def = $module->getModule();
            $category = $def->getCategory()->getLabel();
            $id = $def->getID();

            if(!isset($result[$category])) {
                $result[$category] = array(
                    'count' => 0,
                    'modules' => array()
                );
            }

            if(!isset($result[$category]['modules'][$id])) {
                $result[$category]['modules'][$id] = array(
                    'count' => 0,
                    'def' => $def
                );
            }

            $result[$category]['count']++;
            $result[$category]['modules'][$id]['count']++;
        }

        ksort($result);

        return $result;
    }
}

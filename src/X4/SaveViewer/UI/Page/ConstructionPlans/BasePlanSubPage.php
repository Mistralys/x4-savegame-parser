<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages\ViewSave;

use Mistralys\X4\SaveViewer\Parser\ConstructionPlans\ConstructionPlan;
use Mistralys\X4\SaveViewer\UI\Pages\ViewPlanPage;
use Mistralys\X4\SaveViewer\UI\ViewerSubPage;

/**
 * @property ViewPlanPage $page
 */
abstract class BasePlanSubPage extends ViewerSubPage
{
    public function getPlan() : ConstructionPlan
    {
        return $this->page->getPlan();
    }

    protected function getURLParams() : array
    {
        return array(
            ViewPlanPage::REQUEST_PARAM_PLAN_ID => $this->getPlan()->getID()
        );
    }
}

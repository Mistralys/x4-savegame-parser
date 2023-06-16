<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages;

use Mistralys\X4\SaveViewer\Parser\ConstructionPlans\ConstructionPlan;
use Mistralys\X4\SaveViewer\UI\Pages\ConstructionPlans\PlanOverviewPage;
use Mistralys\X4\SaveViewer\UI\Pages\ConstructionPlans\PlanSettingsPage;
use Mistralys\X4\SaveViewer\UI\PageWithNav;
use Mistralys\X4\SaveViewer\UI\RedirectException;
use function AppLocalize\t;

class ViewPlanPage extends PageWithNav
{
    public const URL_NAME = 'ViewPlan';
    public const REQUEST_PARAM_PLAN_ID = 'planID';

    public function getURLName() : string
    {
        return self::URL_NAME;
    }

    public function getTitle() : string
    {
        return $this->getPlan()->getLabel();
    }

    public function getSubtitle() : string
    {
        return '';
    }

    public function getAbstract() : string
    {
        return '';
    }

    public function getNavTitle() : string
    {
        return t('View plan');
    }

    private ConstructionPlan $plan;

    protected function preRender() : void
    {

    }

    public function getPlan() : ConstructionPlan
    {
        if(isset($this->plan))
        {
            return $this->plan;
        }

        $collection = $this->getApplication()->getConstructionPlans();

        $plan = $collection->getByRequest();

        if($plan === null) {
            throw new RedirectException($collection->getURLList());
        }

        $this->plan = $plan;

        return $plan;
    }

    protected function getURLParams() : array
    {
        return array();
    }

    public function getDefaultSubPageID() : string
    {
        return PlanOverviewPage::URL_NAME;
    }

    protected function initSubPages() : void
    {
        $this->subPages = array(
            new PlanOverviewPage($this),
            new PlanSettingsPage($this)
        );
    }
}

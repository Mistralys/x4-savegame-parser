<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages\ConstructionPlans;

use Mistralys\X4\SaveViewer\UI\Pages\ViewPlanPage;
use Mistralys\X4\SaveViewer\UI\Pages\ViewSave\BasePlanSubPage;
use Mistralys\X4\UI\Button;
use Mistralys\X4\UI\Icon;
use Mistralys\X4\UI\Page\BasePage;
use function AppLocalize\pt;
use function AppLocalize\t;

/**
 * @property ViewPlanPage $page
 */
class PlanSettingsPage extends BasePlanSubPage
{
    public const URL_NAME = 'PlanSettings';

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
        $plan = $this->getPlan();
        $name = $plan->getLabel();
        $nameMessage = '';
        $nameClass = '';

        if($this->request->getBool('save'))
        {
            $valid = true;
            $submitted = (string)$this->request
                ->registerParam('name')
                ->setLabel()
                ->get();

            if(empty($submitted)) {
                $nameClass = 'is-invalid';
                $valid = false;
                $nameMessage = t('Not a valid name.');
                $name = $_REQUEST['name'];
            } else {
                $nameClass = 'is-valid';
                $name = $submitted;
            }

            if($valid)
            {
                $plan->setLabel($name);
                $plan->save();
                $this->sendRedirect($plan->getURLSettings());
            }
        }

        ?>
            <form method="post">
                <input type="hidden" name="<?php echo BasePage::REQUEST_PARAM_PAGE ?>" value="<?php echo $this->page->getURLName() ?>">
                <input type="hidden" name="<?php echo BasePage::REQUEST_PARAM_VIEW ?>" value="<?php echo $this->getURLName() ?>">
                <input type="hidden" name="<?php echo ViewPlanPage::REQUEST_PARAM_PLAN_ID ?>" value="<?php echo $plan->getID() ?>">
                <div class="mb-3">
                    <label for="labelID" class="form-label"><?php pt('Name'); ?></label>
                    <input type="text" name="name" class="form-control <?php echo $nameClass ?>" id="labelID" aria-describedby="labelHelp <?php if(!empty($nameMessage)) { echo 'labelErrorMessage'; } ?>" value="<?php echo htmlspecialchars($name) ?>" required>
                    <?php
                    if(!empty($nameMessage)) {
                        ?>
                        <div id="labelErrorMessage" class="invalid-feedback">
                            <?php echo $nameMessage ?>
                        </div>
                        <?php
                    }
                    ?>
                    <div id="labelHelp" class="form-text">
                        <?php pt('The name of the plan, as shown ingame.') ?>
                    </div>
                </div>
                <?php
                Button::create(t('Save now'))
                    ->setIcon(Icon::save())
                    ->colorPrimary()
                    ->makeSubmit('save', 'yes')
                    ->display();
                ?>
            </form>
        <?php
    }

    public function getTitle() : string
    {
        return t('Construction plan settings');
    }

    public function getSubtitle() : string
    {
        return '';
    }

    public function getAbstract() : string
    {
        return '';
    }
}

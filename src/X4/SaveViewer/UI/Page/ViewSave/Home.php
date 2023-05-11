<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages\ViewSave;

use AppUtils\ConvertHelper;
use function AppLocalize\pt;
use function AppLocalize\t;

class Home extends SubPage
{
    public const URL_PARAM = 'Home';

    public function getURLName() : string
    {
        return self::URL_PARAM;
    }

    public function isInSubnav() : bool
    {
        return false;
    }

    public function getTitle() : string
    {
        return t('Overview');
    }

    public function renderContent() : void
    {
        $saveInfo = $this->getReader()->getSaveInfo();
        $save = $this->getSave();

        ?>
        <table class="table table-horizontal">
            <tbody>
            <tr>
                <th><?php pt('Save type') ?></th>
                <td><?php echo $save->getTypeLabel()  ?></td>
            </tr>
            <tr>
                <th><?php pt('Player name') ?></th>
                <td><?php echo $saveInfo->getPlayerName()  ?></td>
            </tr>
            <tr>
                <th><?php pt('Money') ?></th>
                <td><?php echo $saveInfo->getMoneyPretty() ?></td>
            </tr>
            <tr>
                <th><?php pt('Save name') ?></th>
                <td><?php echo $saveInfo->getSaveName() ?></td>
            </tr>
            <tr>
                <th><?php pt('Date created') ?></th>
                <td><?php echo ConvertHelper::date2listLabel($saveInfo->getSaveDate(), true, true) ?></td>
            </tr>
            </tbody>
        </table>
        <?php
    }
}

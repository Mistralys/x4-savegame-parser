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
        return t('Properties');
    }

    public function getSubtitle() : string
    {
        return '';
    }

    public function getAbstract() : string
    {
        return t('All relevant savegame properties at a glance.');
    }

    public function renderContent() : void
    {
        $reader = $this->getReader();
        $saveInfo = $reader->getSaveInfo();
        $save = $this->getSave();

        ?>
        <table class="table table-horizontal">
            <tbody>
            <tr>
                <th style="width:1%;white-space: nowrap;"><?php pt('Save type') ?></th>
                <td><?php echo $save->getTypeLabel()  ?></td>
            </tr>
            <tr>
                <th style="width:1%;white-space: nowrap;"><?php pt('Player name') ?></th>
                <td><?php echo $saveInfo->getPlayerName()  ?></td>
            </tr>
            <tr>
                <th style="width:1%;white-space: nowrap;"><?php pt('Money') ?></th>
                <td><?php echo $saveInfo->getMoneyPretty() ?></td>
            </tr>
            <tr>
                <th style="width:1%;white-space: nowrap;"><?php pt('Date created') ?></th>
                <td><?php echo ConvertHelper::date2listLabel($saveInfo->getSaveDate(), true, true) ?></td>
            </tr>
            <tr>
                <th style="width:1%;white-space: nowrap;"><?php pt('Khaa\'k stations') ?></th>
                <td><?php echo $reader->getKhaakStations()->countStations() ?></td>
            </tr>
            </tbody>
        </table>
        <?php
    }
}

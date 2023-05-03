<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages\ViewSave;

use AppUtils\ConvertHelper;

class Home extends SubPage
{
    const URL_PARAM = 'Home';

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
        return '';
    }

    public function renderContent() : void
    {
        $player = $this->reader->getPlayer();

        ?>
        <table class="table table-horizontal">
            <tbody>
            <tr>
                <th>Player name</th>
                <td><?php echo $player->getPlayerName()  ?></td>
            </tr>
            <tr>
                <th>Money</th>
                <td><?php echo number_format($player->getMoney(), 0, '.', ' ') ?></td>
            </tr>
            <tr>
                <th>Savegame name</th>
                <td><?php echo $player->getSaveName()  ?></td>
            </tr>
            <tr>
                <th>Savegame date</th>
                <td><?php echo ConvertHelper::date2listLabel($player->getSaveDate(), true, true) ?></td>
            </tr>
            </tbody>
        </table>
        <?php
    }
}

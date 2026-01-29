<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages\ViewSave;

use AppUtils\ConvertHelper;
use Mistralys\X4\SaveViewer\UI\Pages\SavesList;
use Mistralys\X4\SaveViewer\UI\SavesGridRenderer;
use function AppLocalize\pt;
use function AppLocalize\t;
use function AppUtils\sb;

class Home extends BaseViewSaveSubPage
{
    public const string URL_PARAM = 'Home';

    public function getURLName() : string
    {
        return self::URL_PARAM;
    }

    public function isInSubnav() : bool
    {
        return true;
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
        $losses = $reader->getShipLosses();

        $props = array(
            array(
                'label' => t('Save type'),
                'value' => $save->getTypeLabel()
            ),
            array(
                'label' => t('Player name'),
                'value' => $saveInfo->getPlayerName()
            ),
            array(
                'label' => t('Money'),
                'value' => $saveInfo->getMoneyPretty()
            ),
            array(
                'label' => t('Date created'),
                'value' => t('Unknown')
            ),
            array(
                'label' => t('Khaa\'k stations'),
                'value' => $reader->getKhaakStations()->countStations()
            ),
            array(
                'label' => t('Ship losses'),
                'value' => sb()
                    ->add($losses->countLosses())
                    ->add('('.t('%1$s in the last %2$s hours', $losses->countLosses(SavesGridRenderer::LAST_X_HOURS), SavesGridRenderer::LAST_X_HOURS).')')
            ),
            array(
                'label' => t('Archive folder'),
                'value' => $save->getStorageFolder()->getPath()
            )
        );

        $date = $saveInfo->getSaveDate();
        if($date !== null)
        {
            $props[3]['value'] = ConvertHelper::date2listLabel($date, true, true);
        }

        ?>
        <table class="table table-horizontal">
            <tbody>
            <?php
            foreach($props as $prop)
            {
                ?>
                <tr>
                    <th style="width:1%;white-space: nowrap;"><?php echo $prop['label'] ?></th>
                    <td><?php echo $prop['value']  ?></td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
        <?php
    }
}

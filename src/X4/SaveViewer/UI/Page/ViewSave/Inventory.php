<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages\ViewSave;

class Inventory extends BaseViewSaveSubPage
{
    public const string URL_PARAM = 'Inventory';

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
        return 'Player inventory';
    }

    public function getSubtitle() : string
    {
        return '';
    }

    public function getAbstract() : string
    {
        return '';
    }

    public function renderContent() : void
    {
        $inventory = $this->reader->getInventory();

        ?>
        <table class="table">
            <thead>
                <tr>
                    <th class="align-right">Amount</th>
                    <th>Name</th>
                </tr>
            </thead>
            <tbody>
            <?php

            $wares = $inventory->getWares();
            foreach ($wares as $ware) {
                ?>
                <tr>
                    <td class="align-right"><?php echo $ware->getAmount() ?></td>
                    <td><?php echo $ware->getName() ?></td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
        <?php
    }
}

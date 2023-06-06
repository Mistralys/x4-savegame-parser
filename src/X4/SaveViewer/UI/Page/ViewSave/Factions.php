<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages\ViewSave;

class Factions extends ViewSaveSubPage
{
    const URL_PARAM = 'Factions';

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
        return 'Factions';
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
        $factions = $this->reader->getFactions();

        ?>
        <table class="table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Discount</th>
                <th class="align-center">Active?</th>
                <th class="align-center">Relations locked?</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $items = $factions->getAll();
            foreach($items as $faction)
            {
                ?>
                <tr>
                    <td><a href="<?php echo $faction->getURLDetails($this->save) ?>"><?php echo $faction->getName() ?></a></td>
                    <td><?php echo number_format($faction->getPlayerDiscount() * 100, 0) ?>%</td>
                    <td class="align-center"><?php echo $this->renderBool($faction->isActive()) ?></td>
                    <td class="align-center"><?php echo $this->renderBool($faction->areRelationsLocked()) ?></td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
        <?php
    }
}

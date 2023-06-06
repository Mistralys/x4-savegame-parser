<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages\ViewSave;

class FactionRelations extends ViewSaveSubPage
{
    const URL_PARAM = 'FactionRelations';

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
        return 'Faction relations';
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

        $factionName = $this->request->getParam('faction');
        if(!$factions->nameExists($factionName)) {
            $this->redirect($factions->getURLList($this->save));
        }

        $faction = $factions->getByName($factionName);

        $relations = $faction->getRelations();
        $booster = false;
        $player = $factions->getPlayerFaction();

        if($faction->hasRelationWith($player)) {
            $booster = $faction->getRelationWith($player)->hasBooster();
        }

        ?>
        <h4>Faction: <?php echo $faction->getLabel() ?></h4>
        <table class="table table-horizontal">
            <tbody>
            <tr>
                <th>Name</th>
                <td><?php echo $faction->getName() ?></td>
            </tr>
            <tr>
                <th>Active?</th>
                <td>
                    <?php echo $this->renderBool($faction->isActive()) ?>
                    <?php
                    if(!$faction->isActive()) {
                        ?>
                        <span class="text-muted"> - They have no stations and no economy.</span>
                        <?php
                    }

                    ?>
                </td>
            </tr>
            <tr>
                <th>Discount</th>
                <td>
                    <?php echo number_format($faction->getPlayerDiscount(), 0) ?>%
                    <span class="text-muted"> - Applied to all ship and blueprint offers.</span>
                </td>
            </tr>
            </tbody>
        </table>
        <h4>Relations</h4>
        <table class="table table-horizontal">
            <tbody>
            <tr>
                <th>Relations locked</th>
                <td>
                    <?php echo $this->renderBool($faction->areRelationsLocked()) ?>
                    <?php if($faction->areRelationsLocked()) {
                        ?>
                        <span class="text-muted"> - The relations will stay at their defined level. Time limited boosters are still possible.</span>
                        <?php
                    } ?>
                </td>
            </tr>
            <tr>
                <th>Booster enabled?</th>
                <td>
                    <?php echo $this->renderBool($booster) ?>
                    <?php
                    if($booster) {
                        ?>
                        <span class="text-muted"> - The player relation takes the active booster into account.</span>
                        <?php
                    }

                    ?>
                </td>
            </tr>
            </tbody>
        </table>
        <table class="table">
            <thead>
            <tr>
                <th>Name</th>
                <th>State</th>
                <th class="align-right">Current value</th>
                <th class="align-right">Base value</th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($relations as $relation)
            {
                $targetFaction = $relation->getTargetFaction();

                if(!$targetFaction->isMajor()) {
                    continue;
                }

                ?>
                <tr>
                    <td><a href="<?php echo $targetFaction->getURLDetails($this->save) ?>"><?php echo $targetFaction->getName() ?></a></td>
                    <td><?php echo $relation->getState(true) ?></td>
                    <td class="align-right"><?php echo number_format($relation->getBoosterValue(), 4, '.', ' ') ?></td>
                    <td class="align-right"><?php echo number_format($relation->getValue(), 4, '.', ' ') ?></td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
        <?php
    }
}

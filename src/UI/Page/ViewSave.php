<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\UI\Pages;

use AppUtils\ConvertHelper;
use Mistralys\X4Saves\Data\SaveFile;
use Mistralys\X4Saves\Data\SaveReader;
use Mistralys\X4Saves\UI\Page;

class ViewSave extends Page
{
    private SaveFile $save;
    private SaveReader $reader;

    protected function init(): void
    {
        $this->save = $this->requireSave();
        $this->reader = $this->save->getReader();
    }

    public function getTitle(): string
    {
        return 'Savegame: '.$this->save->getName();
    }

    public function getNavItems(): array
    {
        return array(
            array(
                'label' => 'Blueprints',
                'url' => '?page='.$this->getID().'&amp;saveName='.$this->save->getName().'&amp;view=blueprints'
            ),
            array(
                'label' => 'Losses',
                'url' => '?page='.$this->getID().'&amp;saveName='.$this->save->getName().'&amp;view=losses'
            ),
            array(
                'label' => 'Factions',
                'url' => '?page='.$this->getID().'&amp;saveName='.$this->save->getName().'&amp;view=factions'
            )
        );
    }

    protected function _render(): void
    {
        $view = $this->request->getParam('view');

        switch($view)
        {
            case 'losses': $this->renderLosses(); break;
            case 'blueprints': $this->renderBlueprints(); break;
            case 'factions': $this->renderFactions(); break;
            case 'faction-relations': $this->renderFactionRelations(); break;
            default: $this->renderHome(); break;
        }
    }

    private function renderFactions() : void
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

    private function renderFactionRelations() : void
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

    private function renderLosses() : void
    {
        $log = $this->reader->getLog()->getDestroyed();

        $entries = $log->getEntries();

        usort($entries, function (SaveReader\Log\LogEntry $a, SaveReader\Log\LogEntry $b) {
            return $b->getTime() - $a->getTime();
        });

        ?>
            <h2>Ship and station losses</h2>
            <p>Ordered by most recent first.</p>
            <ul>
                <?php
                    foreach ($entries as $entry)
                    {
                          ?>
                            <li>
                                <b title="<?php echo ConvertHelper::interval2string($entry->getInterval()) ?>" style="cursor: help">
                                    Hour <?php echo $entry->getHours() ?>
                                </b>
                                <?php echo $entry->getTitle().' '.$entry->getText() ?>
                            </li>
                        <?php
                    }
                ?>
            </ul>
        <?php
    }

    private function renderBlueprints() : void
    {
        $blueprints = $this->reader->getBlueprints();
    }

    private function renderHome() : void
    {
        $player = $this->reader->getPlayer();

        ?>
            <h2><?php echo $this->save->getName() ?></h2>
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
                </tbody>
            </table>
        <?php
    }
}
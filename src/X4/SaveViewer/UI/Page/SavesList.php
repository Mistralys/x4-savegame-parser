<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages;

use AppUtils\ConvertHelper;
use Mistralys\X4\SaveViewer\Data\BaseSaveFile;
use Mistralys\X4\SaveViewer\UI\Page;

class SavesList extends Page
{
    public const URL_NAME = 'SavesList';

    public function getTitle(): string
    {
        return 'Savegames';
    }

    public function getNavItems(): array
    {
        return array();
    }

    protected function getURLParams() : array
    {
        return array();
    }

    private function createGrid() : void
    {
        $grid = $this->ui->createDataGrid();

        $grid->addColumn('name', 'Name')
            ->useObjectValues()->fetchByMethod(array(BaseSaveFile::class, 'getName'))
            ->decorateWith()->linkByMethod(array(BaseSaveFile::class, 'getURLView'));

        $grid->addColumn('char', 'Character')
            ->useObjectValues()->fetchByMethod(array(BaseSaveFile::class, 'getPlayerName'));
    }

    protected function _render(): void
    {
        $saves = $this->manager->getSaves();

        $this->createGrid();

        ?>
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Character</th>
                    <th class="align-right">Money</th>
                    <th class="align-right">Losses</th>
                    <th>Modified</th>
                    <th class="align-right">Size</th>
                    <th>Backup?</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                    <?php
                        foreach($saves as $save)
                        {
                            ?>
                                <tr>
                                    <?php
                                        if($save->isDataValid())
                                        {
                                            $reader = $save->getReader();
                                            ?>
                                            <td><a href="<?php echo $save->getURLView() ?>"><?php echo $save->getLabel() ?></a></td>
                                            <td><?php echo $reader->getPlayer()->getPlayerName() ?></td>
                                            <td class="align-right"><?php echo $reader->getPlayer()->getMoneyPretty() ?></td>
                                            <td class="align-right"><?php echo $reader->countLosses() ?></td>
                                            <?php
                                        } else {
                                            ?>
                                            <td><?php echo $save->getName() ?></td>
                                            <td>-</td>
                                            <td class="align-right">-</td>
                                            <td class="align-right">-</td>
                                            <?php
                                        }
                                    ?>
                                    <td><?php echo ConvertHelper::date2listLabel($save->getDateModified(), true, true) ?></td>
                                    <td class="align-right"><?php echo ConvertHelper::bytes2readable($save->getFileSize()) ?></td>
                                    <td>
                                        <?php
                                            if($save->hasBackup())
                                            {
                                                echo 'YES';
                                            }
                                            else
                                            {
                                                echo 'NO';
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                            if(!$save->isDataValid()) {
                                                ?>
                                                <a href="<?php echo $save->getURLUnpack() ?>" class="btn btn-primary btn-sm">
                                                    Unpack
                                                </a>
                                                <?php
                                            }
                                            else if(!$save->hasBackup())
                                            {
                                                ?>
                                                <a href="<?php echo $save->getURLBackup() ?>" class="btn btn-secondary btn-sm">
                                                    Backup
                                                </a>
                                                <?php
                                            }
                                        ?>
                                    </td>
                                </tr>
                            <?php
                        }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}

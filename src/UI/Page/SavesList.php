<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\UI\Pages;

use AppUtils\ConvertHelper;
use Mistralys\X4Saves\UI\Page;

class SavesList extends Page
{
    public function getTitle(): string
    {
        return 'Savegames';
    }

    public function getNavItems(): array
    {
        return array();
    }

    protected function _render(): void
    {
        $saves = $this->manager->getSaves();

        ?>
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Unpacked?</th>
                    <th>Modified</th>
                    <th>Size</th>
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
                                        if($save->isDataValid()) {
                                            ?>
                                            <td><a href="?page=ViewSave&amp;saveName=<?php echo $save->getName() ?>"><?php echo $save->getName() ?></a></td>
                                            <td>YES</td>
                                            <?php
                                        } else {
                                            ?>
                                            <td><?php echo $save->getName() ?></td>
                                            <td>NO</td>
                                            <?php
                                        }
                                    ?>
                                    <td><?php echo $save->getDateModified()->format('d.m.Y H:i:s') ?></td>
                                    <td><?php echo ConvertHelper::bytes2readable($save->getFileSize()) ?></td>
                                    <td>
                                        <?php
                                            if(!$save->isDataValid()) {
                                                ?>
                                                    <a href="?page=UnpackSave&amp;saveName=<?php echo $save->getName() ?>" class="btn btn-primary btn-small">
                                                        Unpack
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
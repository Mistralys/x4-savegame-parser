<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages\ViewSave;

use AppUtils\ConvertHelper;
use AppUtils\FileHelper;

class Backup extends ViewSaveSubPage
{
    const URL_PARAM = 'Backup';

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
        return 'Backup';
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
        $saveDate = $this->save->getDateModified();
        $backup = $this->save->createBackup();
        $backupDate = $backup->getDate();

        ?>
        <p>
            Savegame file was updated on <?php echo ConvertHelper::date2listLabel($saveDate, true, true) ?>
            (<?php echo ConvertHelper::duration2string($saveDate) ?>).
        </p>
        <?php

        if($backupDate !== null)
        {
            ?>
                <p>
                    <strong>Backup found.</strong><br>
                    Created on <?php echo ConvertHelper::date2listLabel($backupDate, true, true) ?>
                    (<?php echo ConvertHelper::duration2string($backupDate) ?>).
                </p>
                <p>
                    Location on disk:<br>
                    <code><?php echo FileHelper::relativizePath($backup->getBackupPath(), X4_SAVES_FOLDER) ?></code>
                </p>
            <?php
        }
        else
        {
            ?>
                <p>
                    <strong>No backup found.</strong>
                </p>
                <p>
                    <a href="<?php echo $this->save->getURLBackup() ?>" class="btn btn-primary">
                        Create backup
                    </a>
                </p>
            <?php
        }

        ?>
            <p>

            </p>
        <?php
    }
}

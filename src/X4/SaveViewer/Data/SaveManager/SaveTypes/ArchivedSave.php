<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data;

use AppUtils\FileHelper;
use function AppLocalize\t;

class ArchivedSave extends BaseSaveFile
{
    public function getTypeLabel() : string
    {
        return t('Archived savegame');
    }

    public function deleteArchive() : void
    {
        FileHelper::deleteTree($this->getStorageFolder());
    }
}

<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\SaveManager\SaveTypes;

use Mistralys\X4\SaveViewer\Data\BaseSaveFile;
use Mistralys\X4\SaveViewer\Data\SaveManager;
use Mistralys\X4\SaveViewer\Parser\SaveSelector\SaveGameFile;

class MainSave extends BaseSaveFile
{
    private SaveGameFile $saveFile;

    public function __construct(SaveManager $manager, SaveGameFile $saveFile)
    {
        $this->saveFile = $saveFile;

        parent::__construct($manager, $saveFile->getAnalysis());
    }

    public function getSaveFile() : SaveGameFile
    {
        return $this->saveFile;
    }

    public function isTempSave() : bool
    {
        return $this->saveFile->isTempFile();
    }

    public function hasBackup() : bool
    {
        return $this->saveFile->getBackupFile()->exists();
    }
}

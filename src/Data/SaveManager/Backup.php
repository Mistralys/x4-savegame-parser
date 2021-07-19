<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\Data;

use AppUtils\FileHelper;

class SaveBackup
{
    private SaveFile $file;
    private string $targetFolder;
    private string $targetFile;

    public function __construct(SaveFile $file)
    {
        $this->file = $file;
        $this->targetFolder = sprintf(
            '%s/backups/%s',
            X4_SAVES_FOLDER,
            $file->getID()
        );

        $this->targetFile = $this->targetFolder.'/backup.xml';
    }

    public function write() : void
    {
        if($this->exists())
        {
            return;
        }

        FileHelper::createFolder($this->targetFolder);

        FileHelper::copyFile(
            $this->file->getPath(),
            $this->targetFile
        );

        $dataFolder = $this->file->getDataFolder();

        FileHelper::copyTree(
            $dataFolder,
            $this->targetFolder.'/data'
        );
    }

    public function exists() : bool
    {
        return file_exists($this->targetFile);
    }
}

<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data;

use AppUtils\BaseException;
use AppUtils\FileHelper\FileInfo;
use AppUtils\FileHelper\FolderInfo;
use AppUtils\FileHelper_Exception;
use DirectoryIterator;
use Mistralys\X4\SaveViewer\Parser\SaveSelector;
use Mistralys\X4\SaveViewer\Parser\SaveSelector\SaveGameFile;
use Mistralys\X4\SaveViewer\SaveManager\SaveTypes\MainSave;
use Mistralys\X4\SaveViewer\SaveViewerException;

class SaveManager
{
    public const ERROR_CANNOT_FIND_BY_NAME = 12125;

    /**
     * @var BaseSaveFile[]
     */
    private array $saves = array();
    private SaveSelector $selector;

    /**
     * @param SaveSelector $selector
     * @throws FileHelper_Exception
     * @throws SaveViewerException
     */
    public function __construct(SaveSelector $selector)
    {
        $this->selector = $selector;

        $this->loadSaves();
    }

    public function getSavesFolder() : FolderInfo
    {
        return $this->selector->getSavesFolder();
    }

    public function getStorageFolder(): FolderInfo
    {
        return $this->selector->getStorageFolder();
    }

    /**
     * @return BaseSaveFile[]
     */
    public function getSaves() : array
    {
        return $this->saves;
    }

    /**
     * @return void
     * @throws FileHelper_Exception
     * @throws SaveViewerException
     */
    private function loadSaves() : void
    {
        $this->loadMainSaves();
        $this->loadArchivedSaves();

        usort($this->saves, static function (BaseSaveFile $a, BaseSaveFile $b) : int
        {
            return $b->getDateModified()->getTimestamp() - $a->getDateModified()->getTimestamp();
        });
    }

    private function loadMainSaves() : void
    {
        $mainSaves = $this->selector->getSaveGames();

        foreach($mainSaves as $mainSave)
        {
            $this->saves[] = new MainSave($this, $mainSave);
        }
    }

    private function loadArchivedSaves() : void
    {
        $storageFolder = $this->selector->getStorageFolder();

        if(!$storageFolder->exists())
        {
            return;
        }

        $d = new DirectoryIterator($storageFolder->getPath());

        foreach($d as $item)
        {
            if(!$item->isDir() || $item->isDot()) {
                continue;
            }

            $zipPath = $item->getPathname().'/'.SaveGameFile::BACKUP_ARCHIVE_FILE_NAME;

            if(!file_exists($zipPath))
            {
                continue;
            }

            $this->saves[] = new ArchivedSave(
                $this,
                SaveGameFile::create(
                    $storageFolder,
                    FileInfo::factory($zipPath)
                )
            );
        }
    }

    public function getCurrentSave() : ?BaseSaveFile
    {
        if(!empty($this->saves)) {
            return $this->saves[0];
        }

        return null;
    }

    public function countSaves() : int
    {
        return count($this->saves);
    }

    public function nameExists(string $saveName) : bool
    {
        foreach ($this->saves as $save) {
            if($save->getName() === $saveName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $saveName
     * @return BaseSaveFile
     * @throws BaseException
     */
    public function getByName(string $saveName) : BaseSaveFile
    {
        foreach ($this->saves as $save) {
            if($save->getName() === $saveName) {
                return $save;
            }
        }

        throw new BaseException(
            sprintf('Cannot find savegame [%s].', $saveName),
            '',
            self::ERROR_CANNOT_FIND_BY_NAME
        );
    }
}

<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data;

use AppUtils\BaseException;
use AppUtils\FileHelper\FolderInfo;
use DirectoryIterator;
use Mistralys\X4\SaveViewer\Parser\FileAnalysis;
use Mistralys\X4\SaveViewer\Parser\SaveSelector;
use Mistralys\X4\SaveViewer\SaveManager\SaveTypes\MainSave;
use Mistralys\X4\SaveViewer\SaveViewerException;

class SaveManager
{
    public const ERROR_CANNOT_FIND_BY_NAME = 12125;

    /**
     * @var MainSave[]
     */
    private array $saves = array();
    /**
     * @var ArchivedSave[]
     */
    private array $archivedSaves = array();
    private SaveSelector $selector;

    /**
     * @param SaveSelector $selector
     */
    public function __construct(SaveSelector $selector)
    {
        $this->selector = $selector;
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
     * Fetches all savegame files from the X4 savegame folder.
     *
     * NOTE: Fetches a fresh list from disk every time the
     * method is called.
     *
     * @return BaseSaveFile[]
     */
    public function getSaves() : array
    {
        $this->loadMainSaves();

        return $this->saves;
    }

    public function getArchivedSaves() : array
    {
        $this->loadArchivedSaves();

        return $this->archivedSaves;
    }

    protected function sortSaves(BaseSaveFile $a, BaseSaveFile $b) : int
    {
        return $b->getDateModified()->getTimestamp() - $a->getDateModified()->getTimestamp();
    }

    /**
     * @return void
     * @throws SaveViewerException
     */
    private function loadMainSaves() : void
    {
        $mainSaves = $this->selector
            ->clearCache()
            ->getSaveGames();

        $this->saves = array();

        foreach($mainSaves as $mainSave)
        {
            $save = new MainSave($this, $mainSave);

            if($save->isTempSave()) {
                continue;
            }

            $this->saves[] = $save;
        }

        usort($this->saves, array($this, 'sortSaves'));
    }

    private function loadArchivedSaves() : void
    {
        $storageFolder = $this->selector->getStorageFolder();
        $this->archivedSaves = array();

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

            $analysisPath = $item->getPathname().'/'.FileAnalysis::ANALYSIS_FILE_NAME;

            if(!file_exists($analysisPath))
            {
                continue;
            }

            $this->archivedSaves[] = new ArchivedSave(
                $this,
                FileAnalysis::createFromDataFile($analysisPath)
            );
        }

        usort($this->archivedSaves, array($this, 'sortSaves'));
    }

    public function getCurrentSave() : ?MainSave
    {
        $saves = $this->getSaves();

        if(!empty($saves)) {
            return array_shift($saves);
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
            if($save->getSaveName() === $saveName) {
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
            if($save->getSaveName() === $saveName) {
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

<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data;

use AppUtils\BaseException;
use AppUtils\FileHelper;
use AppUtils\FileHelper\FolderInfo;
use AppUtils\FileHelper_Exception;
use DirectoryIterator;
use Mistralys\X4\SaveViewer\Parser\FileAnalysis;
use Mistralys\X4\SaveViewer\Parser\SaveSelector;
use Mistralys\X4\SaveViewer\SaveManager\SaveTypes\MainSave;
use Mistralys\X4\SaveViewer\SaveViewerException;
use Mistralys\X4\SaveViewer\UI\Pages\ConstructionPlansPage;
use Mistralys\X4\SaveViewer\UI\Pages\SavesList;
use Mistralys\X4\SaveViewer\UI\Pages\ViewSave\ArchivedSavesPage;
use Mistralys\X4\UI\Page\BasePage;

class SaveManager
{
    public const int ERROR_CANNOT_FIND_BY_NAME = 12125;
    public const int ERROR_CANNOT_FIND_BY_ID = 12126;

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

    /**
     * @param string|FolderInfo $savesFolder
     * @param string|FolderInfo $storageFolder
     * @return SaveManager
     * @throws FileHelper_Exception
     */
    public static function create($savesFolder, $storageFolder) : SaveManager
    {
        return new self(SaveSelector::create($savesFolder, $storageFolder));
    }

    /**
     * Creates an instance of the save manager using
     * the paths configured in the <code>config.php</code>
     * file.
     *
     * @return SaveManager
     */
    public static function createFromConfig() : SaveManager
    {
        return new self(SaveSelector::createFromConfig());
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

    public function nameExists(string $name) : bool
    {
        return in_array($name, $this->getSaveNames(), true);
    }

    public function getSaveByName($name) : MainSave
    {
        $saves = $this->getSaves();

        foreach($saves as $save) {
            if($save->getSaveName() === $name) {
                return $save;
            }
        }

        throw new SaveViewerException(
            'Savegame not found.',
            sprintf(
                'Savegame with name [%s] was not found.',
                $name
            ),
            self::ERROR_CANNOT_FIND_BY_NAME
        );
    }

    /**
     * @return string[]
     */
    public function getSaveNames() : array
    {
        $saves = $this->getSaves();
        $result = array();

        foreach($saves as $save) {
            $result[] = $save->getSaveName();
        }

        return $result;
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

        $saves = $this->getSaves();
        $mainAnalysisFiles = array();
        foreach($saves as $save) {
            $path = FileHelper::normalizePath($save->getAnalysis()->getStorageFile()->getPath());
            $mainAnalysisFiles[$path] = $save;
        }

        $d = new DirectoryIterator($storageFolder->getPath());

        foreach($d as $item)
        {
            if(!$item->isDir() || $item->isDot()) {
                continue;
            }

            $analysisPath = FileHelper::normalizePath($item->getPathname().'/'.FileAnalysis::ANALYSIS_FILE_NAME);

            if(!file_exists($analysisPath))
            {
                continue;
            }

            if(isset($mainAnalysisFiles[$analysisPath]))
            {
                $this->archivedSaves[] = $mainAnalysisFiles[$analysisPath];
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
        return count($this->getSaves());
    }

    public function idExists(string $saveID) : bool
    {
        $saves = $this->getSaves();

        foreach ($saves as $save) {
            if($save->getSaveID() === $saveID) {
                return true;
            }
        }

        $archivedSaves = $this->getArchivedSaves();

        foreach($archivedSaves as $archivedSave) {
            if($archivedSave->getSaveID() === $saveID) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $saveID
     * @return BaseSaveFile
     * @throws BaseException
     */
    public function getByID(string $saveID) : BaseSaveFile
    {
        $saves = $this->getSaves();

        foreach ($saves as $save) {
            if($save->getSaveID() === $saveID) {
                return $save;
            }
        }

        $archivedSaves = $this->getArchivedSaves();

        foreach($archivedSaves as $archivedSave) {
            if($archivedSave->getSaveID() === $saveID) {
                return $archivedSave;
            }
        }

        throw new BaseException(
            sprintf('Cannot find savegame by ID [%s].', $saveID),
            '',
            self::ERROR_CANNOT_FIND_BY_ID
        );
    }

    public function getURL(array $params=array()) : string
    {
        return '?'.http_build_query($params);
    }

    public function getURLSavesArchive(array $params=array()) : string
    {
        $params[BasePage::REQUEST_PARAM_PAGE] = ArchivedSavesPage::URL_NAME;

        return $this->getURL($params);
    }

    public function getURLConstructionPlans(array $params=array()) : string
    {
        $params[BasePage::REQUEST_PARAM_PAGE] = ConstructionPlansPage::URL_NAME;

        return $this->getURL($params);
    }

    public function getURLSavesList(array $params=array()) : string
    {
        $params[BasePage::REQUEST_PARAM_PAGE] = SavesList::URL_NAME;

        return $this->getURL($params);
    }
}

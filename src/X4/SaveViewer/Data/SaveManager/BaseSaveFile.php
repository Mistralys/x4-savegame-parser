<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data;

use AppUtils\FileHelper;
use AppUtils\FileHelper\FileInfo;
use AppUtils\FileHelper\FolderInfo;
use DateTime;
use Mistralys\X4\SaveViewer\Parser\FileAnalysis;
use Mistralys\X4\SaveViewer\Parser\SaveSelector\SaveGameFile;
use Mistralys\X4\SaveViewer\SaveParser;
use Mistralys\X4\SaveViewer\UI\Pages\CreateBackup;
use Mistralys\X4\SaveViewer\UI\Pages\UnpackSave;
use Mistralys\X4\SaveViewer\UI\Pages\ViewSave;
use Mistralys\X4\SaveViewer\SaveViewerException;
use Mistralys\X4\SaveViewer\UI\Pages\ViewSave\DeleteArchivePage;
use Mistralys\X4\UI\Page\BasePage;

abstract class BaseSaveFile
{
    public const ERROR_BACKUP_INVALID_DATA = 89601;

    public const PARAM_SAVE_ID = 'save';

    private SaveManager $manager;

    private FileAnalysis $analysis;

    public function __construct(SaveManager $manager, FileAnalysis $analysis)
    {
        $this->manager = $manager;
        $this->analysis = $analysis;
    }

    public function getStorageFolder() : FolderInfo
    {
        return $this->analysis->getStorageFolder();
    }

    public function getAnalysis() : FileAnalysis
    {
        return $this->analysis;
    }

    public function getSaveID() : string
    {
        return $this->analysis->getSaveID();
    }

    public function getSaveName() : string
    {
        return $this->analysis->getSaveName();
    }

    public function getDateModified() : DateTime
    {
        return $this->analysis->getDateModified();
    }

    private ?SaveReader $reader = null;

    public function getDataReader() : SaveReader
    {
        if(!isset($this->reader))
        {
            $this->reader = new SaveReader($this);
        }

        return $this->reader;
    }

    public function hasData() : bool
    {
        return $this->analysis->exists();
    }

    public function isUnpacked() : bool
    {
        return $this->analysis->exists() && $this->analysis->hasSaveID();
    }

    abstract public function getTypeLabel() : string;

    public function hasBackup() : bool
    {
        return $this->analysis->getBackupFile()->exists();
    }

    public function getDataFolder() : FolderInfo
    {
        return $this->analysis->getStorageFolder();
    }

    public function getJSONPath() : FolderInfo
    {
        return FolderInfo::factory($this->getDataFolder()->getPath().'/JSON');
    }

    public function getLabel() : string
    {
        return $this->getSaveName();
    }

    public function getURLView(array $params=array()) : string
    {
        return $this->getURL(ViewSave::URL_NAME, $params);
    }

    public function getURLUnpack() : string
    {
        return $this->getURL(UnpackSave::URL_NAME);
    }

    public function getURLDelete(array $params=array()) : string
    {
        $params[BasePage::REQUEST_PARAM_VIEW] = DeleteArchivePage::URL_NAME;

        return $this->getURLView($params);
    }

    public function getURLBackup() : string
    {
        return $this->getURL(CreateBackup::URL_NAME);
    }

    protected function getURL(string $page, array $params=array()) : string
    {
        $params['page'] = $page;
        $params[self::PARAM_SAVE_ID] = $this->getSaveID();

        return '?'.http_build_query($params);
    }

    public function createBackup() : ArchivedSave
    {
        return new ArchivedSave($this);
    }

    public function writeBackup() : void
    {
        if(!$this->isUnpacked())
        {
            throw new SaveViewerException(
                'Cannot create backup, data not valid',
                'The analysis and data must have been created to make a backup.',
                self::ERROR_BACKUP_INVALID_DATA
            );
        }

        $this->createBackup()->write();
    }
}

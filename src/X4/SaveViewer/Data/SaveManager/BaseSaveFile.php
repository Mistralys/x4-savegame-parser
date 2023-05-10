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

abstract class BaseSaveFile
{
    public const ERROR_BACKUP_INVALID_DATA = 89601;

    public const PARAM_SAVE_NAME = 'saveName';

    private SaveManager $manager;

    private string $id = '';
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

    public function getDataReader() : SaveReader
    {
        return new SaveReader($this);
    }

    public function hasData() : bool
    {
        return $this->analysis->exists();
    }

    public function isUnpacked() : bool
    {
        return $this->analysis->exists() && $this->analysis->hasSaveID();
    }

    public function getDataFolder() : FolderInfo
    {
        return $this->analysis->getStorageFolder();
    }

    public function getJSONPath() : string
    {
        return $this->getDataFolder();
    }

    public function getLabel() : string
    {
        return $this->getSaveName();
    }

    public function getURLView() : string
    {
        return '?'.http_build_query(array(
            'page' => ViewSave::URL_NAME,
            self::PARAM_SAVE_NAME => $this->getSaveName()
        ));
    }

    public function getPlayerName() : string
    {
        return $this->getDataReader()->getPlayer()->getPlayerName();
    }

    public function getURLUnpack() : string
    {
        return '?'.http_build_query(array(
            'page' => UnpackSave::URL_NAME,
            self::PARAM_SAVE_NAME => $this->getSaveName()
        ));
    }

    public function getURLBackup() : string
    {
        return '?'.http_build_query(array(
                'page' => CreateBackup::URL_NAME,
                self::PARAM_SAVE_NAME => $this->getSaveName()
            ));
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

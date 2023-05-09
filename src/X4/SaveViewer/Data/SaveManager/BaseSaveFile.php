<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data;

use AppUtils\FileHelper;
use AppUtils\FileHelper\FileInfo;
use AppUtils\FileHelper\FolderInfo;
use DateTime;
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

    private string $saveID;
    private string $id = '';
    private SaveGameFile $saveFile;

    public function __construct(SaveManager $manager, SaveGameFile $saveFile)
    {
        $this->manager = $manager;
        $this->saveFile = $saveFile;
    }

    public function getID() : string
    {
        if(empty($this->id))
        {
            $this->id = 'S'.$this->saveFile->getTimestamp();
        }

        return $this->id;
    }

    public function getPath() : string
    {
        return $this->saveFile->getReferenceFile()->getPath();
    }

    public function getFileName() : string
    {
        return $this->saveFile->getReferenceFile()->getName();
    }

    public function getFileSize() : int
    {
        return filesize($this->saveFile->getReferenceFile()->getPath());
    }

    public function getDateModified() : DateTime
    {
        return $this->saveFile->getReferenceFile()->getModifiedDate();
    }

    public function getName(): string
    {
        return $this->saveID;
    }

    public function getReader() : SaveReader
    {
        return new SaveReader($this);
    }

    public function hasData() : bool
    {
        return file_exists($this->getDataFolder().'/analysis.json');
    }

    public function isDataValid() : bool
    {
        if(!$this->hasData()) {
            return false;
        }

        $analysis = $this->getAnalysis();

        return $analysis['date'] === filemtime($this->getPath());
    }

    /**
     * @return void
     * @deprecated
     */
    public function unpackAndConvert() : void
    {
    }

    public function getDataFolder() : FolderInfo
    {
        return $this->saveFile->getStorageFolder();
    }

    public function getJSONPath() : string
    {
        return $this->getDataFolder();
    }

    public function getAnalysis() : array
    {
        return FileHelper::parseJSONFile($this->getDataFolder().'/analysis.json');
    }

    public function getLabel() : string
    {
        if($this->isDataValid()) {
            return $this->getFileName().' - '.$this->getReader()->getPlayer()->getSaveName();
        }

        return $this->getFileName();
    }

    public function getURLView() : string
    {
        return '?'.http_build_query(array(
            'page' => ViewSave::URL_NAME,
            self::PARAM_SAVE_NAME => $this->getName()
        ));
    }

    public function getPlayerName() : string
    {
        return $this->getReader()->getPlayer()->getPlayerName();
    }

    public function getURLUnpack() : string
    {
        return '?'.http_build_query(array(
            'page' => UnpackSave::URL_NAME,
            self::PARAM_SAVE_NAME => $this->getName()
        ));
    }

    public function getURLBackup() : string
    {
        return '?'.http_build_query(array(
                'page' => CreateBackup::URL_NAME,
                self::PARAM_SAVE_NAME => $this->getName()
            ));
    }

    public function hasBackup() : bool
    {
        return $this->createBackup()->exists();
    }

    public function createBackup() : ArchivedSave
    {
        return new ArchivedSave($this);
    }

    public function writeBackup() : void
    {
        if(!$this->isDataValid())
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

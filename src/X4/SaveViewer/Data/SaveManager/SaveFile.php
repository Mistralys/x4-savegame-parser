<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data;

use AppUtils\FileHelper;
use DateTime;
use Mistralys\X4\SaveViewer\SaveParser;
use Mistralys\X4\SaveViewer\UI\Pages\CreateBackup;
use Mistralys\X4\SaveViewer\UI\Pages\UnpackSave;
use Mistralys\X4\SaveViewer\UI\Pages\ViewSave;
use Mistralys\X4\SaveViewer\SaveViewerException;

class SaveFile
{
    const ERROR_BACKUP_INVALID_DATA = 89601;

    const PARAM_SAVE_NAME = 'saveName';

    private SaveManager $manager;

    private string $saveName;

    private string $id = '';

    public function __construct(SaveManager $manager, string $saveName)
    {
        $this->manager = $manager;
        $this->saveName = $saveName;
    }

    public function getID() : string
    {
        if(empty($this->id))
        {
            $player = $this->getReader()->getPlayer();

            $this->id = md5(sprintf(
                '%s-%s',
                $player->getGameGUID(),
                $player->getGameCode()
            ));
        }

        return $this->id;
    }

    public function getPath() : string
    {
        return $this->manager->getSourceFolder().'/'.$this->getFileName();
    }

    public function getFileName() : string
    {
        return $this->saveName.'.xml';
    }

    public function getFileSize() : int
    {
        return filesize($this->getPath());
    }

    public function getDateModified() : DateTime
    {
        return FileHelper::getModifiedDate($this->getPath());
    }

    public function getName(): string
    {
        return $this->saveName;
    }

    public function getManager(): SaveManager
    {
        return $this->manager;
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

    public function unpackAndConvert() : void
    {
        $parser = new SaveParser($this->getName());
        $parser->unpack();
    }

    public function getDataFolder() : string
    {
        return $this->manager->getSourceFolder().'/unpack_'.$this->saveName;
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

    public function createBackup() : SaveBackup
    {
        return new SaveBackup($this);
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

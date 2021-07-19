<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\Data;

use AppUtils\FileHelper;
use DateTime;
use Mistralys\X4Saves\SaveParser;
use Mistralys\X4Saves\UI\Pages\UnpackSave;
use Mistralys\X4Saves\UI\Pages\ViewSave;

class SaveFile
{
    const PARAM_SAVE_NAME = 'saveName';

    private SaveManager $manager;

    private string $saveName;

    public function __construct(SaveManager $manager, string $saveName)
    {
        $this->manager = $manager;
        $this->saveName = $saveName;
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

    public function getURLUnpack() : string
    {
        return '?'.http_build_query(array(
            'page' => UnpackSave::URL_NAME,
            self::PARAM_SAVE_NAME => $this->getName()
        ));
    }
}

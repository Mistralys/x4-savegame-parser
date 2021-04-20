<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\Data;

use AppUtils\BaseException;
use AppUtils\FileHelper;

class SaveManager
{
    const ERROR_CANNOT_FIND_BY_NAME = 12125;

    private string $sourceFolder;

    /**
     * @var SaveFile[]
     */
    private array $saves = array();

    public function __construct()
    {
        $this->sourceFolder = X4_SAVES_FOLDER;
        $this->loadSaves();
    }

    public function getSourceFolder(): string
    {
        return $this->sourceFolder;
    }

    /**
     * @return SaveFile[]
     */
    public function getSaves() : array
    {
        return $this->saves;
    }

    private function loadSaves() : void
    {
        $saves = FileHelper::createFileFinder($this->sourceFolder)
            ->includeExtension('xml')
            ->setPathmodeStrip()
            ->stripExtensions()
            ->getAll();

        $result = array();

        foreach($saves as $save) {
            $result[] = new SaveFile($this, $save);
        }

        usort($result, function (SaveFile $a, SaveFile $b) {
            return $a->getDateModified() < $b->getDateModified();
        });

        $this->saves = $result;
    }

    public function getCurrentSave() : ?SaveFile
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

    public function getByName(string $saveName) : SaveFile
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

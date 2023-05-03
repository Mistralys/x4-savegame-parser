<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data;

use AppUtils\BaseException;
use AppUtils\FileHelper;
use AppUtils\FileHelper_Exception;

class SaveManager
{
    public const ERROR_CANNOT_FIND_BY_NAME = 12125;

    private string $sourceFolder;

    /**
     * @var SaveFile[]
     */
    private array $saves = array();

    /**
     * @throws FileHelper_Exception
     */
    public function __construct()
    {
        $this->sourceFolder = X4_SAVES_FOLDER;

        if(!file_exists($this->sourceFolder))
        {
            die(
                '<p>Saves folder not found at location:</p>'.
                '<pre>'.X4_SAVES_FOLDER.'</pre>'
            );
        }

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

    /**
     * @return void
     * @throws FileHelper_Exception
     */
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

        usort($result, static function (SaveFile $a, SaveFile $b)
        {
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

    /**
     * @param string $saveName
     * @return SaveFile
     * @throws BaseException
     */
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

<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\Data;

use AppUtils\FileHelper;
use Mistralys\X4Saves\Data\SaveReader\Factions;
use Mistralys\X4Saves\Data\SaveReader\Log;
use Mistralys\X4Saves\Data\SaveReader\PlayerInfo;

class SaveReader
{
    private SaveFile $saveFile;

    public function __construct(SaveFile $saveFile)
    {
        $this->saveFile = $saveFile;
    }

    public function getPlayer() : PlayerInfo
    {
        return new PlayerInfo($this);
    }

    public function getLog() : Log
    {
        return new Log($this);
    }

    public function getFactions() : Factions
    {
        return new Factions($this);
    }

    public function getRawData(string $dataID) : array
    {
        $path = $this->saveFile->getJSONPath().'/'.$dataID.'.json';

        return FileHelper::parseJSONFile($path);
    }
}

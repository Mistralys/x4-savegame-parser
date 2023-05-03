<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader;

use AppUtils\Microtime;
use Mistralys\X4\SaveViewer\Parser\Tags\Tag\SaveInfoTag;

class PlayerInfo extends Info
{
    protected function getAutoDataName(): string
    {
        return SaveInfoTag::SAVE_NAME;
    }

    public function getSaveName() : string
    {
        return $this->getStringKey(SaveInfoTag::KEY_SAVE_NAME);
    }

    public function getSaveDate() : Microtime
    {
        $date = new Microtime();
        $date->setTimestamp($this->getIntKey(SaveInfoTag::KEY_SAVE_DATE));
        return $date;
    }

    public function getPlayerName() : string
    {
        return $this->getStringKey(SaveInfoTag::KEY_PLAYER_NAME);
    }

    public function getMoney() : int
    {
        return $this->getIntKey(SaveInfoTag::KEY_PLAYER_MONEY);
    }

    public function getMoneyPretty() : string
    {
        return number_format($this->getMoney(), 0, '.', ' ');
    }

    public function getGameGUID() : string
    {
        return $this->getStringKey(SaveInfoTag::KEY_GAME_GUID);
    }

    public function getGameCode() : int
    {
        return $this->getIntKey(SaveInfoTag::KEY_GAME_CODE);
    }
}

<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\Data\SaveReader;

use Mistralys\X4Saves\SaveParser\Tags\Tag\SaveInfoTag;

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

    public function getSaveDate() : int
    {
        return $this->getIntKey(SaveInfoTag::KEY_SAVE_DATE);
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
}

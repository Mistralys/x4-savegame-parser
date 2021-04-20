<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\Data\SaveReader;

class PlayerInfo extends Info
{
    protected function getAutoDataName(): string
    {
        return 'player';
    }

    public function getPlayerName() : string
    {
        return $this->getStringKey('playerName');
    }

    public function getMoney() : int
    {
        return $this->getIntKey('money');
    }
}

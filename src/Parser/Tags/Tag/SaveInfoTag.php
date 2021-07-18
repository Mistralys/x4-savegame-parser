<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\SaveParser\Tags\Tag;

use Mistralys\X4Saves\SaveParser\Tags\Tag;

class SaveInfoTag extends Tag
{
    const SAVE_NAME = 'savegame-info';

    const KEY_PLAYER_NAME = 'name';
    const KEY_PLAYER_MONEY = 'money';
    const KEY_SAVE_NAME = 'saveName';
    const KEY_SAVE_DATE = 'saveDate';

    private $info = array(
        self::KEY_PLAYER_NAME => '',
        self::KEY_PLAYER_MONEY => '',
        self::KEY_SAVE_NAME => '',
        self::KEY_SAVE_DATE => ''
    );

    public function getTagPath() : string
    {
        return 'info';
    }

    public function getSaveName() : string
    {
        return self::SAVE_NAME;
    }

    protected function open(string $line, int $number) : void
    {
    }

    protected function close(int $number) : void
    {
    }

    protected function open_save(string $line, int $number) : void
    {
        $atts = $this->getAttributes($line);
        $this->info[self::KEY_SAVE_NAME] = $atts['name'];
        $this->info[self::KEY_SAVE_DATE] = $atts['date'];
    }

    protected function open_player(string $line, int $number) : void
    {
        $atts = $this->getAttributes($line);
        $this->info[self::KEY_PLAYER_NAME] = $atts['name'];
        $this->info[self::KEY_PLAYER_MONEY] = $atts['money'];
    }

    protected function getSaveData() : array
    {
        return $this->info;
    }

    protected function clear() : void
    {
        $this->info = array();
    }
}

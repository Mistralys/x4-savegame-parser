<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Tags\Tag;

use Mistralys\X4\SaveViewer\Parser\Tags\Tag;

class SaveInfoTag extends Tag
{
    const SAVE_NAME = 'savegame-info';

    const KEY_PLAYER_NAME = 'name';
    const KEY_PLAYER_MONEY = 'money';
    const KEY_SAVE_NAME = 'saveName';
    const KEY_SAVE_DATE = 'saveDate';
    const KEY_GAME_GUID = 'guid';
    const KEY_GAME_CODE = 'code';

    private array $info = array(
        self::KEY_PLAYER_NAME => '',
        self::KEY_PLAYER_MONEY => '',
        self::KEY_SAVE_NAME => '',
        self::KEY_SAVE_DATE => '',
        self::KEY_GAME_CODE => '',
        self::KEY_GAME_GUID => ''
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

    protected function open_game(string $line, int $number) : void
    {
        $atts = $this->getAttributes($line);
        $this->info[self::KEY_GAME_GUID] = $atts['guid'];
        $this->info[self::KEY_GAME_CODE] = $atts['code'];
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

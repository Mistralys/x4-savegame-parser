<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\SaveParser\Tags\Tag;

use Mistralys\X4Saves\SaveParser\Tags\Tag;

class LogTag extends Tag
{
    const SAVE_NAME = 'log';

    const KEY_TIME = 'time';
    const KEY_CATEGORY = 'category';
    const KEY_TITLE = 'title';
    const KEY_TEXT = 'text';
    const KEY_ENTITY = 'entity';
    const KEY_FACTION = 'faction';
    const KEY_MONEY = 'money';

    private array $log = array();

    private static array $keyMap = array(
        'title' => self::KEY_TITLE,
        'category' => self::KEY_CATEGORY,
        'text' => self::KEY_TEXT,
        'time' => self::KEY_TIME,
        'entity' => self::KEY_ENTITY,
        'faction' => self::KEY_FACTION,
        'money' => self::KEY_MONEY
    );

    public function getTagPath() : string
    {
        return 'log';
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

    protected function open_entry(string $line, int $number) : void
    {
        $attributes = $this->getAttributes($line);

        $entry = array();
        foreach(self::$keyMap as $sourceKey => $targetKey)
        {
            $value = '';
            if(isset($attributes[$sourceKey])) {
                $value = $attributes[$sourceKey];
            }

            $entry[$targetKey] = $value;
        }

        $this->log[] = $entry;
    }

    protected function getSaveData() : array
    {
        return $this->log;
    }

    protected function clear() : void
    {
        $this->log = array();
    }
}

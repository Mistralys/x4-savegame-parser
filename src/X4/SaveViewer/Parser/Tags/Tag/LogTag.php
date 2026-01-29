<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Tags\Tag;

use Mistralys\X4\SaveViewer\Parser\Tags\Tag;

class LogTag extends Tag
{
    public const string SAVE_NAME = 'log';

    public const string KEY_TIME = 'time';
    public const string KEY_CATEGORY = 'category';
    public const string KEY_TITLE = 'title';
    public const string KEY_TEXT = 'text';
    public const string KEY_ENTITY = 'entity';
    public const string KEY_FACTION = 'faction';
    public const string KEY_MONEY = 'money';

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

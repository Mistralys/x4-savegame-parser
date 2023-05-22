<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Types;

use Mistralys\X4\SaveViewer\Parser\Collections;

class LogEntryType extends BaseComponentType
{
    public const TYPE_ID = 'log-entry';
    public const KEY_FACTION = 'faction';
    public const KEY_MONEY = 'money';
    public const KEY_TIME = 'time';
    public const KEY_TEXT = 'text';
    public const KEY_CATEGORY = 'category';
    public const KEY_TARGET_COMPONENT_ID = 'componentID';
    public const KEY_TITLE = 'title';


    public function __construct(Collections $collections, array $data)
    {
        parent::__construct($collections, 'logconnection', 'logentry');

        $this->setKeys($data);
    }

    protected function getDefaultData() : array
    {
        return array();
    }

    public function getTypeID() : string
    {
        return self::TYPE_ID;
    }
}
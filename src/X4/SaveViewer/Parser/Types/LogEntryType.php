<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Types;

use Mistralys\X4\SaveViewer\Parser\Collections;

class LogEntryType extends BaseComponentType
{
    public const string TYPE_ID = 'log-entry';
    public const string KEY_FACTION = 'faction';
    public const string KEY_MONEY = 'money';
    public const string KEY_TIME = 'time';
    public const string KEY_TEXT = 'text';
    public const string KEY_CATEGORY = 'category';
    public const string KEY_TARGET_COMPONENT_ID = 'componentID';
    public const string KEY_TITLE = 'title';


    public function __construct(Collections $collections, array $data)
    {
        parent::__construct($collections, 'logconnection', 'logentry');

        $this->setKeys($data);
    }

    public function getText() : string
    {
        return $this->getString(self::KEY_TEXT);
    }

    public function getTitle() : string
    {
        return $this->getString(self::KEY_TITLE);
    }

    public function getTime() : string
    {
        return $this->getString(self::KEY_TIME);
    }

    public function getCategory() : string
    {
        return $this->getString(self::KEY_CATEGORY);
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
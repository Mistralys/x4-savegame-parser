<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader\Log;

use AppUtils\ArrayDataCollection;
use Mistralys\X4\SaveViewer\Data\SaveReader\GameTime;
use Mistralys\X4\SaveViewer\Parser\Types\LogEntryType;

class LogEntry
{
    private ArrayDataCollection $data;
    private GameTime $time;
    private LogCategory $category;

    public function __construct(array $data, float $startTime)
    {
        $this->data = ArrayDataCollection::create($data);
        $this->time = GameTime::create($this->data->getString(LogEntryType::KEY_TIME), $startTime);
    }

    public function getCategoryName() : string
    {
        return $this->data->getString(LogEntryType::KEY_CATEGORY);
    }

    public function getCategoryID() : string
    {
        return $this->category->getCategoryID();
    }

    public function getCategory() : LogCategory
    {
        return $this->category;
    }

    /**
     * @return string
     */
    public function getFactionID(): string
    {
        return $this->data->getString(LogEntryType::KEY_FACTION);
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->data->getString(LogEntryType::KEY_TEXT);
    }

    /**
     * @return GameTime
     */
    public function getTime(): GameTime
    {
        return $this->time;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->data->getString(LogEntryType::KEY_TITLE);
    }

    public function _registerCategory(LogCategory $category) : void
    {
        $this->category = $category;
    }
}

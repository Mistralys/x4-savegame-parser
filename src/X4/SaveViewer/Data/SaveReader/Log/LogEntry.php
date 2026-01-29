<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader\Log;

use AppUtils\ArrayDataCollection;
use Mistralys\X4\SaveViewer\Data\SaveReader\GameTime;
use Mistralys\X4\SaveViewer\Parser\Types\LogEntryType;

class LogEntry
{
    public const string SERIALIZED_TIME = 'time';
    public const string SERIALIZED_TITLE = 'title';
    public const string SERIALIZED_TEXT = 'text';
    public const string SERIALIZED_FACTION_ID = 'factionID';
    public const string SERIALIZED_MONEY = 'money';
    private ArrayDataCollection $data;
    private GameTime $time;
    private LogCategory $category;

    public function __construct(array $data, float $startTime)
    {
        $this->data = ArrayDataCollection::create($data);
        $this->time = GameTime::create($this->data->getString(LogEntryType::KEY_TIME), $startTime);
    }

    public function getRawData() : ArrayDataCollection
    {
        return $this->data;
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

    public function getMoney() : int
    {
        return $this->data->getInt(LogEntryType::KEY_MONEY);
    }

    public function getMoneyPretty() : string
    {
        $money = $this->getMoney();

        if($money > 0) {
            return number_format($money / 100, 2, '.', ' ').' Cr';
        }

        return '-';
    }

    public static function createFromCollectionArray(array $entryData, float $startTime) : LogEntry
    {
        $data = ArrayDataCollection::create($entryData);

        return new LogEntry(
            array(
                LogEntryType::KEY_TEXT => $data->getString(self::SERIALIZED_TEXT),
                LogEntryType::KEY_MONEY => $data->getInt(self::SERIALIZED_MONEY),
                LogEntryType::KEY_TITLE => $data->getString(self::SERIALIZED_TITLE),
                LogEntryType::KEY_FACTION => $data->getString(self::SERIALIZED_FACTION_ID),
                LogEntryType::KEY_TIME => $data->getFloat(self::SERIALIZED_TIME)
            ),
            $startTime
        );
    }

    public static function createFromCollection(array $rawData, float $startTime) : LogEntry
    {
        return new LogEntry($rawData, $startTime);
    }

    public function toArray() : array
    {
        return array(
            self::SERIALIZED_TIME => $this->getTime()->getValue(),
            self::SERIALIZED_TITLE => $this->getTitle(),
            self::SERIALIZED_TEXT => $this->getText(),
            self::SERIALIZED_FACTION_ID => $this->getFactionID(),
            self::SERIALIZED_MONEY => $this->getMoney(),
        );
    }
}

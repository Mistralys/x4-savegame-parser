<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Collections;

use Mistralys\X4\SaveViewer\Parser\Types\LogEntryType;

class EventLogCollection extends BaseCollection
{
    public const COLLECTION_ID = 'event-log';

    public function getCollectionID() : string
    {
        return self::COLLECTION_ID;
    }

    public function createLogEntry(string $time, string $category, string $title, string $text, string $faction, string $componentID, string $money) : LogEntryType
    {
        $logEntry = new LogEntryType(
            $this->collections,
            array(
                LogEntryType::KEY_TIME => $time,
                LogEntryType::KEY_CATEGORY => $category,
                LogEntryType::KEY_TITLE => $title,
                LogEntryType::KEY_TEXT => $text,
                LogEntryType::KEY_FACTION => $faction,
                LogEntryType::KEY_TARGET_COMPONENT_ID => $componentID,
                LogEntryType::KEY_MONEY => (int)$money
            )
        );

        $this->addComponent($logEntry);

        return $logEntry;
    }

    /**
     * @return LogEntryType[]
     */
    public function getEntries() : array
    {
        return $this->getComponentsByType(LogEntryType::TYPE_ID);
    }
}

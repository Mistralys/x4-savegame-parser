<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader\Log;

class LogCategory
{
    public const SERIALIZED_CATEGORY_ID = 'categoryID';
    public const SERIALIZED_LABEL = 'label';
    public const SERIALIZED_ENTRIES = 'entries';
    public const SERIALIZED_START_TIME = 'startTime';

    /**
     * @var LogEntry[]
     */
    protected array $entries = array();
    private string $id;
    private string $label;
    private float $startTime;
    private bool $areEntriesSorted = false;

    public function __construct(string $id, string $label, float $startTime)
    {
        $this->id = $id;
        $this->label = $label;
        $this->startTime = $startTime;
    }

    public function getCategoryID() : string
    {
        return $this->id;
    }

    public function getLabel() : string
    {
        return $this->label;
    }

    /**
     * @return LogEntry[]
     */
    public function getEntries() : array
    {
        if(!$this->areEntriesSorted)
        {
            $this->areEntriesSorted = true;

            usort($this->entries, static function(LogEntry $a, LogEntry $b) : float {
                return $a->getTime()->getDuration() - $b->getTime()->getDuration();
            });
        }

        return $this->entries;
    }

    public function countEntries() : int
    {
        return count($this->entries);
    }

    public function _registerEntry(LogEntry $entry) : void
    {
        $this->entries[] = $entry;
        $entry->_registerCategory($this);
    }

    public function toArray() : array
    {
        $data = array(
            self::SERIALIZED_CATEGORY_ID => $this->getCategoryID(),
            self::SERIALIZED_LABEL => $this->getLabel(),
            self::SERIALIZED_START_TIME => $this->startTime,
            self::SERIALIZED_ENTRIES => array()
        );

        foreach($this->entries as $entry)
        {
            $data[self::SERIALIZED_ENTRIES][] = $entry->toArray();
        }

        return $data;
    }
}

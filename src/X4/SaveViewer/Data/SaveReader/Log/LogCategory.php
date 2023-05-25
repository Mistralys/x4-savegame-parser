<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader\Log;

class LogCategory
{
    /**
     * @var LogEntry[]
     */
    private array $entries = array();
    private string $id;
    private string $label;
    /**
     * @var callable
     */
    private $detectCallback;

    public function __construct(string $id, string $label, callable $detectCallback)
    {
        $this->id = $id;
        $this->label = $label;
        $this->detectCallback = $detectCallback;
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
        return $this->entries;
    }

    public function countEntries() : int
    {
        return count($this->entries);
    }

    /**
     * Checks whether the entry should be assigned to
     * this category.
     *
     * @param LogEntry $entry
     * @return bool
     */
    public function matchesEntry(LogEntry $entry) : bool
    {
        return call_user_func($this->detectCallback, $entry) === true;
    }

    public function _registerEntry(LogEntry $entry) : void
    {
        $this->entries[] = $entry;
        $entry->_registerCategory($this);
    }
}

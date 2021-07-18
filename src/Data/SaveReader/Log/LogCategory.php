<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\Data\SaveReader\Log;

use Mistralys\X4Saves\Data\SaveReader\Log;

abstract class LogCategory
{
    private Log $log;

    /**
     * @var LogEntry[]
     */
    private array $entries = array();

    private bool $entriesLoaded = false;

    public function __construct(Log $log)
    {
        $this->log = $log;
    }

    abstract public function getCategoryID() : string;

    /**
     * @return LogEntry[]
     */
    public function getEntries() : array
    {
        $this->loadEntries();

        return $this->entries;
    }

    public function loadEntries() : void
    {
        if($this->entriesLoaded) {
            return;
        }

        $this->entriesLoaded = true;

        $this->entries = $this->log->getByCategory(LogEntry::CATEGORY_DESTROYED);
    }

    public function countEntries() : int
    {
        $this->loadEntries();

        return count($this->entries);
    }
}

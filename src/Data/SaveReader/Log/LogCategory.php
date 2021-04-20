<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\Data\SaveReader\Log;

use Mistralys\X4Saves\Data\SaveReader\Info;

abstract class LogCategory extends Info
{
    /**
     * @var LogEntry[]
     */
    private array $entries = array();

    private bool $entriesLoaded = false;

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

        foreach ($this->data as $data) {
            $this->entries[] = new LogEntry(
                (string)$data['category'],
                (float)$data['time'],
                (string)$data['title'],
                (string)$data['text'],
                (string)$data['faction']
            );
        }
    }

    public function countEntries() : int
    {
        $this->loadEntries();

        return count($this->entries);
    }
}

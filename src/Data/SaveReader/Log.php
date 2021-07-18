<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\Data\SaveReader;

use Mistralys\X4Saves\Data\SaveReader\Log\Destroyed;
use Mistralys\X4Saves\Data\SaveReader\Log\LogEntry;
use Mistralys\X4Saves\SaveParser\Tags\Tag\LogTag;

class Log extends Info
{
    /**
     * @var LogEntry[]
     */
    private array $entries = array();

    protected function getAutoDataName(): string
    {
        return LogTag::SAVE_NAME;
    }

    public function getDestroyed() : Destroyed
    {
        return new Destroyed($this);
    }

    public function getEntries() : array
    {
        return $this->entries;
    }

    public function getByCategory(string $category) : array
    {
        $result = array();

        foreach($this->entries as $entry)
        {
            if($entry->getCategory() === $category) {
                $result[] = $entry;
            }
        }

        return $result;
    }

    private function load() : void
    {
        if(!empty($this->entries))
        {
            return;
        }

        foreach ($this->data as $entry)
        {
            $entry = new LogEntry($entry);

            if($entry->getCategory() !== LogEntry::CATEGORY_IGNORE) {
                $this->entries[] = $entry;
            }
        }
    }
}

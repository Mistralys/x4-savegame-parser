<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader;

use Mistralys\X4\SaveViewer\Data\SaveReader\Log\LogCategories;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log\LogCategory;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log\LogEntry;
use Mistralys\X4\SaveViewer\Parser\Collections\EventLogCollection;
use Mistralys\X4\SaveViewer\Parser\Types\LogEntryType;

class Log extends Info
{
    /**
     * @var array<string,LogEntry>
     */
    private array $entries = array();
    private LogCategories $categories;
    private bool $categoriesDetected = false;

    protected function init() : void
    {
        $this->categories = new LogCategories($this);

        $fileID = 'collection-'.EventLogCollection::COLLECTION_ID;

        if(!$this->reader->dataExists($fileID)) {
            return;
        }

        $list = $this->reader->getRawData($fileID);

        if(!isset($list[LogEntryType::TYPE_ID])) {
            return;
        }

        $startTime = $this->reader->getSaveInfo()->getGameStartTime();

        foreach($list[LogEntryType::TYPE_ID] as $entry)
        {
            $this->entries[] = new LogEntry($entry, $startTime);
        }
    }

    public function getCategories() : LogCategories
    {
        $this->detectCategories();

        return $this->categories;
    }

    public function getEntries() : array
    {
        $this->detectCategories();

        return $this->entries;
    }

    private function detectCategories() : void
    {
        if($this->categoriesDetected) {
            return;
        }

        $this->categoriesDetected = true;

        $categories = $this->categories->getAll();

        foreach($categories as $category)
        {
            $this->detectCategoryEntries($category);
        }
    }

    private function detectCategoryEntries(LogCategory $category) : void
    {
        $misc = $this->categories->getByID(LogCategories::CATEGORY_MISCELLANEOUS);

        foreach($this->entries as $entry)
        {
            if($category->matchesEntry($entry))
            {
                $category->_registerEntry($entry);
                return;
            }

            $misc->_registerEntry($entry);
        }
    }
}

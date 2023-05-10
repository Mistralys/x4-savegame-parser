<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader;

use AppUtils\Microtime;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log\Destroyed;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log\LogEntry;
use Mistralys\X4\SaveViewer\Parser\Tags\Tag\LogTag;

class Log extends Info
{
    /**
     * @var array<string,LogEntry[]>
     */
    private array $entries = array();

    public function getDestroyed() : Destroyed
    {
        return new Destroyed($this);
    }

    public function getByCategory(string $category) : array
    {
        $this->unpack();

        if(!$this->reader->dataExists('log/'.$category)) {
            return array();
        }

        $result = array();
        $data = $this->reader->getRawData('log/'.$category);

        foreach($data as $entryData)
        {
            $result[] = new LogEntry($entryData);
        }

        return $result;
    }

    private function unpack() : void
    {
        if($this->reader->dataExists('log-unpacked')) {
            return;
        }

        $this->reader->saveData('log-unpacked', array(
            'date' => (new Microtime())->getMySQLDate()
        ));

        $this->createEntries();
        $this->writeDataFiles();
    }

    private function writeDataFiles() : void
    {
        foreach ($this->entries as $categoryID => $entries)
        {
            $this->reader->saveData('log/'.$categoryID, $entries);
        }
    }

    private function createEntries() : void
    {
        if(!empty($this->entries))
        {
            return;
        }

        foreach ($this->data as $entryData)
        {
            $entry = new LogEntry($entryData);
            $categoryID = $entry->getCategory();

            if($categoryID === LogEntry::CATEGORY_IGNORE) {
                continue;
            }

            if(!isset($this->entries[$categoryID])) {
                $this->entries[$categoryID] = array();
            }

            $this->entries[$categoryID][] = $entryData;
        }
    }
}

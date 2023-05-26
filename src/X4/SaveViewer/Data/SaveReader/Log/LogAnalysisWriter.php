<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader\Log;

use AppUtils\FileHelper\FolderInfo;
use AppUtils\FileHelper\JSONFile;
use AppUtils\FileHelper_Exception;
use AppUtils\Microtime;
use AppUtils\Microtime_Exception;
use Mistralys\X4\SaveViewer\Data\SaveReader;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log\Categories\DetectionCategories;
use Mistralys\X4\SaveViewer\Parser\FileAnalysis;

class LogAnalysisWriter
{
    private FolderInfo $path;
    private FileAnalysis $analysis;

    public function __construct(SaveReader $reader)
    {
        $save = $reader->getSaveFile();

        $this->path = FolderInfo::factory($save->getJSONPath()->getFolderPath() . '/event-log');
        $this->analysis = $save->getAnalysis();
    }

    public function getPath() : FolderInfo
    {
        return $this->path;
    }

    /**
     * @throws FileHelper_Exception
     * @throws Microtime_Exception
     */
    public function writeFiles(DetectionCategories $collection) : void
    {
        $categories = $collection->getAll();

        foreach ($categories as $category)
        {
            JSONFile::factory(
                sprintf(
                    '%s/%s.json',
                    $this->path->getFolderPath(),
                    $category->getCategoryID()
                )
            )
                ->putData($category->toArray(), true);
        }

        $this->analysis->setKey(LogAnalysisCache::KEY_CACHE_WRITTEN, Microtime::createNow()->getISODate());
        $this->analysis->setKey(LogAnalysisCache::KEY_CATEGORY_IDS, $collection->getIDs());
        $this->analysis->save();
    }
}

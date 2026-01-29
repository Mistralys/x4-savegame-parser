<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader\Log;

use AppUtils\Microtime;
use Mistralys\X4\SaveViewer\Data\SaveReader;
use Mistralys\X4\SaveViewer\Parser\FileAnalysis;

class LogAnalysisCache
{
    public const string KEY_CACHE_WRITTEN = 'log-cache-written';
    public const string KEY_CATEGORY_IDS = 'log-category-ids';

    private FileAnalysis $analysis;
    private LogAnalysisWriter $writer;

    public function __construct(SaveReader $reader)
    {
        $saveFile = $reader->getSaveFile();

        $this->analysis = $saveFile->getAnalysis();
        $this->writer = new LogAnalysisWriter($reader);
    }

    public function getWriter() : LogAnalysisWriter
    {
        return $this->writer;
    }

    public function isCacheValid() : bool
    {
        return $this->getCacheDate() !== null && $this->writer->getPath()->exists();
    }

    /**
     * @return string[]
     */
    public function getCategoryIDs() : array
    {
        return $this->analysis->getArray(self::KEY_CATEGORY_IDS);
    }

    public function getCacheDate() : ?Microtime
    {
        $date = $this->analysis->getString(self::KEY_CACHE_WRITTEN);
        if(!empty($date)) {
            return new Microtime($date);
        }

        return null;
    }
}
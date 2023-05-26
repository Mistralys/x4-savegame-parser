<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader;

use AppUtils\FileHelper\JSONFile;
use AppUtils\FileHelper_Exception;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log\Categories\CachedCategories;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log\Categories\CachedCategory;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log\Categories\DetectionCategories;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log\Categories\DetectionCategory;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log\LogAnalysisCache;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log\LogCategories;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log\LogCategory;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log\LogEntry;
use Mistralys\X4\SaveViewer\Parser\Collections\EventLogCollection;
use Mistralys\X4\SaveViewer\Parser\Types\LogEntryType;
use Mistralys\X4\SaveViewer\SaveViewerException;
use Mistralys\X4\SaveViewer\UI\Pages\ViewSave\EventLogPage;
use Mistralys\X4\UI\Page\BasePage;

class Log extends Info
{
    public const ERROR_CANNOT_LOAD_ANALYSIS_MISSING = 137201;

    /**
     * @var array<string,LogEntry>
     */
    private array $entries = array();
    private LogCategories $categories;
    private LogAnalysisCache $analysisCache;

    protected function init() : void
    {
        $this->analysisCache = new LogAnalysisCache($this->reader);
    }

    public function getURL(array $params=array()) : string
    {
        $params[BasePage::REQUEST_PARAM_VIEW] = EventLogPage::URL_NAME;

        return $this->reader->getSaveFile()->getURLView($params);
    }

    public function isCacheValid() : bool
    {
        return $this->analysisCache->isCacheValid();
    }

    public function getCacheInfo() : LogAnalysisCache
    {
        return $this->analysisCache;
    }

    // region: Loading analysis files

    /**
     * @return CachedCategories
     * @throws SaveViewerException {@see self::ERROR_CANNOT_LOAD_ANALYSIS_MISSING}
     * @throws FileHelper_Exception
     * @throws \JsonException
     */
    public function loadAnalysisCache() : CachedCategories
    {
        if(!$this->isCacheValid()) {
            throw new SaveViewerException(
                'Cannot load log analysis, cache is not present.',
                '',
                self::ERROR_CANNOT_LOAD_ANALYSIS_MISSING
            );
        }

        $categories = new CachedCategories($this->reader->getGameStartTime());

        $ids = $this->analysisCache->getCategoryIDs();
        $path = $this->analysisCache->getWriter()->getPath();

        foreach($ids as $id)
        {
            $data = JSONFile::factory($path->getFolderPath().'/'.$id.'.json')->parse();

            $category = CachedCategory::createFromArray($data);

            $categories->registerCategory($category);
        }

        return $categories;
    }

    // endregion

    // region: Generate the analysis files

    public function generateAnalysisCache() : DetectionCategories
    {
        $categories = $this->loadEntriesFromCollection();

        $this->analysisCache->getWriter()
            ->writeFiles($categories);

        return $categories;
    }

    public function loadEntriesFromCollection() : DetectionCategories
    {
        $categories = new DetectionCategories($this->reader->getGameStartTime());
        $fileID = 'collection-'.EventLogCollection::COLLECTION_ID;

        if(!$this->reader->dataExists($fileID)) {
            return $categories;
        }

        $list = $this->reader->getRawData($fileID);

        if(!isset($list[LogEntryType::TYPE_ID])) {
            return $categories;
        }

        $startTime = $this->reader->getSaveInfo()->getGameStartTime();
        $entries = array();

        foreach($list[LogEntryType::TYPE_ID] as $entry)
        {
            $entries[] = LogEntry::createFromCollection($entry, $startTime);
        }

        $this->detectCategories($categories, $entries);

        return $categories;
    }

    /**
     * @param DetectionCategories $collection
     * @param LogEntry[] $entries
     * @return void
     * @throws SaveViewerException
     */
    private function detectCategories(DetectionCategories $collection, array $entries) : void
    {
        $categories = $collection->getAll();
        $misc = $collection->getByID(LogCategories::CATEGORY_MISCELLANEOUS);

        foreach($entries as $entry)
        {
            $found = false;

            // We don't stop at the first category found,
            // because some categories are allowed to
            // override others.
            foreach ($categories as $category)
            {
                if($category instanceof DetectionCategory && $category->matchesEntry($entry))
                {
                    $found = true;
                    $category->_registerEntry($entry);
                }
            }

            if(!$found)
            {
                $misc->_registerEntry($entry);
            }
        }
    }

    // endregion
}

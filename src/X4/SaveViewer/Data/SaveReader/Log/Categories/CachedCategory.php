<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader\Log\Categories;

use AppUtils\ArrayDataCollection;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log\LogCategory;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log\LogEntry;

class CachedCategory extends LogCategory
{
    public static function createFromArray(array $rawData) : CachedCategory
    {
        $data = ArrayDataCollection::create($rawData);
        $startTime = $data->getFloat(self::SERIALIZED_START_TIME);

        $category = new CachedCategory(
            $data->getString(self::SERIALIZED_CATEGORY_ID),
            $data->getString(self::SERIALIZED_LABEL),
            $startTime
        );

        $entries = $data->getArray(self::SERIALIZED_ENTRIES);

        foreach ($entries as $entryData)
        {
            $entry = LogEntry::createFromCollectionArray($entryData, $startTime);

            $category->_registerEntry($entry);
        }

        return $category;
    }
}

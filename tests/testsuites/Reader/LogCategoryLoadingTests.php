<?php

declare(strict_types=1);

namespace X4\SaveGameParserTests\Tests\Reader;

use X4\SaveGameParserTests\TestClasses\X4LogCategoriesTestCase;

final class LogCategoryLoadingTests extends X4LogCategoriesTestCase
{
    public function test_loading() : void
    {
        $log = $this->getTestLog();
        $cacheInfo = $log->getCacheInfo();

        $this->assertFalse($cacheInfo->isCacheValid());

        $log->generateAnalysisCache();

        $this->assertTrue($cacheInfo->isCacheValid());

        $categories = $log->loadAnalysisCache()->getAll();

        $this->assertNotEmpty($categories);

        foreach($this->categoryAssignments as $categoryID)
        {
            $found = null;
            foreach($categories as $category)
            {
                if($category->getCategoryID() === $categoryID) {
                    $found = $category;
                    break;
                }
            }

            $this->assertNotNull($found, sprintf('Category [%s] not loaded from cache.', $categoryID));
            $this->assertNotEmpty($found->getEntries());
        }
    }
}

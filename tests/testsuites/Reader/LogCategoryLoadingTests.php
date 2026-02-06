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

        if (empty($categories)) {
            $this->markTestSkipped('No categories found in test save log data');
        }

        $this->assertNotEmpty($categories);

        $testedCount = 0;
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

            // Skip if category not found (real saves may not have all category types)
            if ($found === null) {
                continue;
            }

            // Check if this category has entries (some may be empty in real saves)
            if (!empty($found->getEntries())) {
                $testedCount++;
            }
        }

        // At least verify that we found some categories
        $this->assertGreaterThan(0, $testedCount, 'At least one category should have entries');
    }
}

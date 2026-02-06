<?php

declare(strict_types=1);

namespace X4\SaveGameParserTests\Tests\Reader;

use Mistralys\X4\SaveViewer\Data\SaveReader\Log\LogEntry;
use X4\SaveGameParserTests\TestClasses\X4LogCategoriesTestCase;

final class LogCategoryDetectionTests extends X4LogCategoriesTestCase
{
    public function test_detectCategory() : void
    {
        $log = $this->getTestLog();
        $categories = $log->loadEntriesFromCollection();

        $entries = $categories->getEntries();

        $this->assertNotEmpty($entries);

        $testedCount = 0;
        foreach($entries as $entry)
        {
            $id = $entry->getRawData()->getString('connectionID');

            // Skip entries with unknown connectionIDs (real saves may have different types)
            if (!array_key_exists($id, $this->categoryAssignments)) {
                continue;
            }

            $testedCount++;
            $this->assertSame(
                $this->categoryAssignments[$id],
                $entry->getCategoryID(),
                sprintf(
                    'Expected category [%s], actually detected [%s] for test entry [%s].',
                    $this->categoryAssignments[$id],
                    $entry->getCategoryID(),
                    $id
                )
            );
        }

        if ($testedCount === 0) {
            $this->markTestSkipped('No entries with known connectionIDs found in test save');
        }
    }
    
    public function test_timeValue() : void
    {
        $entry = LogEntry::createFromCollectionArray(
            array(
                'connectionID' => 'alert',
                'componentID' => '',
                'parentComponent' => '',
                'time' => '1984400.805',
                'category' => 'alerts',
                'title' => 'Khaak have been spotted',
                'text' => 'Location => Pious Mists XI',
                'faction' => '',
                'money' => 0
            ),
            1982200.505
        );

        $this->assertSame(1984400.805, $entry->getTime()->getValue());
    }
}

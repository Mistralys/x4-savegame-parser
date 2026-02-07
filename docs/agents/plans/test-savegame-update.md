# Test Savegame Update Plan

**Created:** 2026-02-07  
**Status:** Ready for Implementation

## Overview

Refactor the test suite to use a centralized savegame detection mechanism with a dedicated `TestSaveNames` class for constants, ensuring the extraction script and all test cases reference the same source of truth for save names, with automatic validation, caching, and helpful error messages.

## Background

The test suite currently has several issues:
- Hardcoded timestamp folders (e.g., `unpack-20260206211435-quicksave`) that don't match the new savegame package
- No centralized location for test save name constants
- Duplicate SaveManager creation logic across test classes
- No validation that required test saves exist before running tests

The new approach uses two savegames from the `mistralys/x4-savegame` Composer package:
- **advanced-creative-v8**: Large save with comprehensive data (full logbook, losses, player assets)
- **start-scientist-v8**: Minimal save from game start (empty losses, 1 logbook entry, sparse player data)

## Key Architectural Decisions

1. **Centralized Constants**: `TestSaveNames` class in `tests/classes/` provides single source of truth for save names
2. **Early Validation**: Tests fail immediately in `setUp()` if required saves are missing
3. **Caching**: SaveManager instance cached per test to avoid repeated filesystem scans
4. **Pattern Matching**: Saves located by folder name suffix (e.g., `unpack-*-advanced-creative-v8`)
5. **Immutability**: Extracted savegames are read-only; tests never modify save data on disk
6. **Helpful Errors**: Error messages include expected patterns, available saves, and reference to documentation

## Folder Structure Context

```
tests/
├── classes/
│   ├── X4ParserTestCase.php              # Base test case (will be enhanced)
│   ├── X4LogCategoriesTestCase.php       # Extends X4ParserTestCase
│   └── TestSaveNames.php                 # NEW: Central constants class
├── testsuites/
│   ├── CLI/
│   │   ├── CollectionJsonDataLoadingTest.php
│   │   └── QueryHandlerCollectionsTest.php
│   ├── Parser/
│   │   ├── FileDetectionTests.php        # Keep as-is (uses /files/save-files/)
│   │   └── LossDetectionTests.php
│   └── Reader/
│       └── LogCategoryLoadingTests.php
├── files/
│   └── test-saves/                       # Extracted saves go here
│       ├── unpack-{timestamp}-advanced-creative-v8/
│       └── unpack-{timestamp}-start-scientist-v8/
├── extract-test-saves.php                # Will use TestSaveNames constants
└── README.md                             # Will be enhanced with documentation

Note: FileDetectionTests.php uses /files/save-files/ with synthetic test files
and should remain unchanged as it tests file detection logic specifically.
```

## Timestamp Pattern Details

When the extraction script runs, it creates folders with this pattern:
```
unpack-{YmdHis}-{savename}
```

Example: `unpack-20260207114936-advanced-creative-v8`

**Important**: Timestamps will differ across developer machines. This is expected behavior since each developer extracts at different times. The test framework finds saves by matching the folder name suffix (e.g., ends with `advanced-creative-v8`).

---

## Work Package 1: Create TestSaveNames Constants Class

**Priority:** Critical  
**Dependencies:** None  
**Estimated Effort:** 15 minutes

### Objective
Create a central constants class that both the extraction script and all test cases can reference for test save names.

### Implementation Steps

1. Create new file `tests/classes/TestSaveNames.php`:

```php
<?php

declare(strict_types=1);

namespace X4\SaveGameParserTests\TestClasses;

/**
 * Central source of truth for test savegame names.
 * Used by both the extraction script and all test cases.
 */
class TestSaveNames
{
    /**
     * Advanced creative mode save with comprehensive data.
     * - Full logbook with many entries
     * - Ship losses data populated
     * - Complete player assets
     * - Used by most tests as the default save
     */
    public const SAVE_ADVANCED_CREATIVE = 'advanced-creative-v8';

    /**
     * Start scientist save with minimal player data.
     * - Empty data-losses.json array
     * - Only 1 entry in event log
     * - Sparse player-specific data
     * - Full NPC universe data present
     * - Used for edge case testing
     */
    public const SAVE_START_SCIENTIST = 'start-scientist-v8';

    /**
     * Get all test save names for iteration.
     *
     * @return string[]
     */
    public static function getAllSaveNames(): array
    {
        return [
            self::SAVE_ADVANCED_CREATIVE,
            self::SAVE_START_SCIENTIST
        ];
    }
}
```

### Verification

- Class is in correct namespace: `X4\SaveGameParserTests\TestClasses`
- Both constants defined with correct values
- `getAllSaveNames()` returns array of both save names
- File uses proper PSR-4 autoloading structure

---

## Work Package 2: Update Extraction Script

**Priority:** Critical  
**Dependencies:** WP1 (TestSaveNames class must exist)  
**Estimated Effort:** 5 minutes

### Objective
Update the extraction script to use `TestSaveNames` constants instead of hardcoded array.

### Implementation Steps

1. Open `tests/extract-test-saves.php`

2. Add import at top (after existing use statements):
```php
use X4\SaveGameParserTests\TestClasses\TestSaveNames;
```

3. Replace the hardcoded array (around line 42):
```php
// OLD:
$testSaveNames = array(
    'advanced-creative-v8',
    'start-scientist-v8'
);

// NEW:
$testSaveNames = TestSaveNames::getAllSaveNames();
```

### Verification

- Run: `php tests/extract-test-saves.php`
- Should extract both saves successfully
- Folders created: `unpack-{timestamp}-advanced-creative-v8` and `unpack-{timestamp}-start-scientist-v8`
- No errors or warnings

---

## Work Package 3: Enhance X4ParserTestCase Base Class

**Priority:** Critical  
**Dependencies:** WP1 (TestSaveNames class must exist)  
**Estimated Effort:** 45 minutes

### Objective
Add SaveManager caching, save lookup methods, and validation to the base test case.

### Current State
File: `tests/classes/X4ParserTestCase.php`
- Extends PHPUnit `TestCase`
- Has `setUp()` and `tearDown()` methods
- Has `createSelector()` method for SaveSelector

### Implementation Steps

1. Add import for TestSaveNames at top:
```php
use X4\SaveGameParserTests\TestClasses\TestSaveNames;
use Mistralys\X4\SaveViewer\Data\SaveManager;
use Mistralys\X4\SaveViewer\Data\BaseSaveFile;
```

2. Add private property after existing properties:
```php
/**
 * Cached SaveManager instance for test saves.
 * Extracted savegames are immutable - tests should never modify save data on disk.
 */
private ?SaveManager $saveManager = null;
```

3. Update `setUp()` method to initialize SaveManager and validate:
```php
protected function setUp() : void
{
    parent::setUp();

    Config::setTestSuiteEnabled(true);

    $this->filesFolder = __DIR__.'/../files';
    $this->saveGameFile = __DIR__.'/../files/quicksave.xml';
    $this->foldersCleanup = array();
    
    // Initialize cached SaveManager for test saves
    $testSavesFolder = __DIR__.'/../files/test-saves';
    $this->saveManager = SaveManager::create($testSavesFolder, $testSavesFolder);
    
    // Validate that required test saves exist
    $this->validateTestSavesExist();

    $this->disableLogging();
}
```

4. Add validation method:
```php
/**
 * Validates that both required test saves are available.
 * Fails immediately with helpful error message if saves are missing.
 *
 * @return void
 */
private function validateTestSavesExist(): void
{
    $requiredSaves = [
        TestSaveNames::SAVE_ADVANCED_CREATIVE,
        TestSaveNames::SAVE_START_SCIENTIST
    ];
    
    $missing = [];
    foreach ($requiredSaves as $saveName) {
        if ($this->getSaveByName($saveName) === null) {
            $missing[] = $saveName;
        }
    }
    
    if (!empty($missing)) {
        $available = $this->getAvailableSaveNames();
        
        $message = "Required test save(s) not found:\n";
        foreach ($missing as $saveName) {
            $message .= "  - Expected folder pattern: unpack-*-{$saveName}\n";
        }
        $message .= "\nAvailable saves:\n";
        if (empty($available)) {
            $message .= "  (none)\n";
        } else {
            foreach ($available as $name) {
                $message .= "  - {$name}\n";
            }
        }
        $message .= "\nPlease run: php ./tests/extract-test-saves.php\n";
        $message .= "See: /tests/README.md for more information\n";
        
        $this->fail($message);
    }
}
```

5. Add SaveManager accessor:
```php
/**
 * Get the cached SaveManager instance for test saves.
 * 
 * Note: Extracted savegames are immutable and should not be modified by tests.
 *
 * @return SaveManager
 */
protected function getSaveManager(): SaveManager
{
    return $this->saveManager;
}
```

6. Add save lookup methods:
```php
/**
 * Find a save by name, matching folder pattern unpack-*-{saveName}.
 *
 * @param string $saveName The save name (e.g., 'advanced-creative-v8')
 * @return BaseSaveFile|null
 */
protected function getSaveByName(string $saveName): ?BaseSaveFile
{
    $saves = $this->saveManager->getArchivedSaves();
    
    foreach ($saves as $save) {
        $folderName = $save->getStorageFolder()->getName();
        // Check if folder name ends with the save name
        if (substr($folderName, -strlen($saveName)) === $saveName) {
            return $save;
        }
    }
    
    return null;
}

/**
 * Require a save by name, failing with helpful error if not found.
 *
 * @param string $saveName The save name (e.g., 'advanced-creative-v8')
 * @return BaseSaveFile
 */
protected function requireSaveByName(string $saveName): BaseSaveFile
{
    $save = $this->getSaveByName($saveName);
    
    if ($save === null) {
        $available = $this->getAvailableSaveNames();
        
        $message = "Required test save not found: {$saveName}\n";
        $message .= "Expected folder pattern: unpack-*-{$saveName}\n\n";
        $message .= "Available saves:\n";
        if (empty($available)) {
            $message .= "  (none)\n";
        } else {
            foreach ($available as $name) {
                $message .= "  - {$name}\n";
            }
        }
        $message .= "\nPlease run: php ./tests/extract-test-saves.php\n";
        $message .= "See: /tests/README.md for more information\n";
        
        $this->fail($message);
    }
    
    return $save;
}

/**
 * Get list of available save folder names for error messages.
 *
 * @return string[]
 */
private function getAvailableSaveNames(): array
{
    $saves = $this->saveManager->getArchivedSaves();
    $names = [];
    
    foreach ($saves as $save) {
        $names[] = $save->getStorageFolder()->getName();
    }
    
    return $names;
}
```

### Verification

- Run existing tests to ensure nothing breaks
- Tests should fail if saves not extracted with helpful error message
- After extracting saves, tests should pass
- Check error message mentions `/tests/README.md`

---

## Work Package 4: Refactor X4LogCategoriesTestCase

**Priority:** High  
**Dependencies:** WP3 (Enhanced X4ParserTestCase)  
**Estimated Effort:** 10 minutes

### Objective
Simplify X4LogCategoriesTestCase to use inherited methods from base class.

### Current State
File: `tests/classes/X4LogCategoriesTestCase.php`
- Has `$testSaveFolder` property
- Has local `createSaveManager()` method
- `getTestLog()` searches for hardcoded folder name `unpack-20260206211435-quicksave`

### Implementation Steps

1. Add import at top:
```php
use X4\SaveGameParserTests\TestClasses\TestSaveNames;
```

2. Remove `$testSaveFolder` property and `setUp()` method entirely

3. Remove `createSaveManager()` method entirely

4. Replace `getTestLog()` method:
```php
public function getTestLog() : Log
{
    $save = $this->requireSaveByName(TestSaveNames::SAVE_ADVANCED_CREATIVE);
    $log = $save->getDataReader()->getLog();
    
    $this->addFolderToRemove($log->getCacheInfo()->getWriter()->getPath());
    
    return $log;
}
```

### Verification

- Run: `vendor/bin/phpunit tests/testsuites/Reader/LogCategoryLoadingTests.php`
- Test should pass using the advanced-creative save
- Should find save automatically regardless of timestamp

---

## Work Package 5: Update CLI Test Classes

**Priority:** High  
**Dependencies:** WP3 (Enhanced X4ParserTestCase)  
**Estimated Effort:** 20 minutes

### Objective
Refactor CLI test classes to extend X4ParserTestCase and use inherited methods.

### Files to Update
- `tests/testsuites/CLI/CollectionJsonDataLoadingTest.php`
- `tests/testsuites/CLI/QueryHandlerCollectionsTest.php`

### Implementation Steps for Each File

1. Add imports at top:
```php
use X4\SaveGameParserTests\TestClasses\X4ParserTestCase;
use X4\SaveGameParserTests\TestClasses\TestSaveNames;
```

2. Change class declaration:
```php
// OLD:
class CollectionJsonDataLoadingTest extends TestCase

// NEW:
class CollectionJsonDataLoadingTest extends X4ParserTestCase
```

3. Remove these items:
   - `TEST_SAVE_NAME` constant
   - `private ?SaveManager $manager = null;` property
   - SaveManager initialization in `setUp()` method
   - SaveManager cleanup in `tearDown()` method

4. Update `getTestSave()` method:
```php
private function getTestSave()
{
    return $this->requireSaveByName(TestSaveNames::SAVE_ADVANCED_CREATIVE);
}
```

5. Replace all `$this->manager` references with `$this->getSaveManager()`

### Verification

- Run: `vendor/bin/phpunit tests/testsuites/CLI/CollectionJsonDataLoadingTest.php`
- Run: `vendor/bin/phpunit tests/testsuites/CLI/QueryHandlerCollectionsTest.php`
- Both should pass
- Verify they use advanced-creative save automatically

---

## Work Package 6: Fix LossDetectionTests JSON Path

**Priority:** High  
**Dependencies:** WP3 (Enhanced X4ParserTestCase)  
**Estimated Effort:** 5 minutes

### Objective
Replace hardcoded JSON path with dynamic lookup.

### Current State
File: `tests/testsuites/Parser/LossDetectionTests.php`
- Method `test_loadFromJSON()` has hardcoded path: `__DIR__.'/../../files/test-saves/unpack-20260206211435-quicksave/JSON/data-losses.json'`

### Implementation Steps

1. Add import at top:
```php
use X4\SaveGameParserTests\TestClasses\TestSaveNames;
```

2. Update `test_loadFromJSON()` method:
```php
public function test_loadFromJSON() : void
{
    $save = $this->requireSaveByName(TestSaveNames::SAVE_ADVANCED_CREATIVE);
    $data = JSONFile::factory($save->getJSONPath()->getPath() . '/data-losses.json')->parse();

    $this->assertCount(3, $data);

    $collections = new Collections(Config::getStorageFolder());
    $entry = new LogEntryType($collections, $data[0]);

    $info = DetectShipLosses::parseEntry($entry);

    $this->assertNotNull($info);
    $this->assertSame('BIE-447', $info['shipCode']);
    $this->assertSame('Aramean Shipyard TEL (IJU-471)', $info['commander']);
}
```

### Verification

- Run: `vendor/bin/phpunit tests/testsuites/Parser/LossDetectionTests.php`
- All tests should pass
- `test_loadFromJSON()` should use dynamic path

---

## Work Package 7: Update Tests README Documentation

**Priority:** Medium  
**Dependencies:** WP1-WP6 (All implementation complete)  
**Estimated Effort:** 30 minutes

### Objective
Enhance README with comprehensive documentation about the new test save approach.

### Current State
File: `tests/README.md` - Basic documentation about extraction process

### Implementation Steps

1. Update the "The Savegame Archive" section to describe both saves:

```markdown
## The Savegame Archive

The savegames are installed via Composer as a dependency: They are
stored in the package `mistralys/x4-savegame`. This keeps them separate
from the project's codebase.

They can be found in the folder:

- [X4 saves](/vendor/mistralys/x4-savegame/saves)

### Available Test Saves

Two savegames are provided for testing:

#### advanced-creative-v8 (Default)
- **Usage**: Default comprehensive save used by most tests
- **Characteristics**:
  - Full logbook with many event entries
  - Populated ship losses data (`data-losses.json`)
  - Complete player assets (ships, stations, crew)
  - Extensive NPC universe data
- **Best for**: Standard feature testing, data-rich scenarios

#### start-scientist-v8 (Edge Cases)
- **Usage**: Minimal save for edge case testing
- **Characteristics**:
  - Empty ship losses array (`data-losses.json` = `[]`)
  - Minimal logbook (only 1 entry in `collection-event-log.json`)
  - Sparse player-specific data
  - Full NPC universe data present (ships, stations, people)
- **Best for**: Testing empty collections, minimal data scenarios, early-game state
```

2. Add section explaining folder pattern and timestamps:

```markdown
## Extracted Save Folder Structure

When you run the extraction script, it creates timestamped folders:

```
tests/files/test-saves/
├── unpack-20260207114936-advanced-creative-v8/
└── unpack-20260207114936-start-scientist-v8/
```

### Folder Naming Pattern

Format: `unpack-{YmdHis}-{savename}`
- `unpack` - Prefix indicating extracted save
- `{YmdHis}` - Timestamp (Year/Month/Day/Hour/Minute/Second)
- `{savename}` - The save identifier

**Important**: Timestamps will differ across developer machines. This is expected
behavior since each developer extracts saves at different times. The test framework
automatically locates saves by matching the folder name suffix (e.g., ends with
`advanced-creative-v8`), so tests work regardless of the extraction timestamp.

### Clean Extraction

The extraction script automatically deletes the entire `test-saves` folder before
extracting, ensuring:
- No stale data from previous extractions
- Clean state for all tests
- Only one version of each save exists
```

3. Add section about the test framework:

```markdown
## Test Framework Integration

### Centralized Save Names

All test save names are defined in the `TestSaveNames` class:

```php
use X4\SaveGameParserTests\TestClasses\TestSaveNames;

// Available constants:
TestSaveNames::SAVE_ADVANCED_CREATIVE  // 'advanced-creative-v8'
TestSaveNames::SAVE_START_SCIENTIST    // 'start-scientist-v8'
```

Both the extraction script and all test cases reference this single source of truth.

### Accessing Saves in Tests

All test classes extend `X4ParserTestCase`, which provides:

```php
// Get a save (returns null if not found)
$save = $this->getSaveByName(TestSaveNames::SAVE_ADVANCED_CREATIVE);

// Require a save (fails test with helpful error if not found)
$save = $this->requireSaveByName(TestSaveNames::SAVE_ADVANCED_CREATIVE);

// Access SaveManager
$manager = $this->getSaveManager();
```

The test framework automatically:
- Validates both required saves exist before running tests
- Caches SaveManager to avoid repeated filesystem scans
- Provides helpful error messages with extraction instructions
- Locates saves by folder name pattern matching

### Immutable Save Data

**Important**: Extracted savegames are read-only. Tests should never modify save
data on disk. All extracted JSON and XML files are treated as immutable fixtures.
```

4. Add troubleshooting section:

```markdown
## Troubleshooting

### "Required test save(s) not found" Error

If you see this error when running tests:

```
Required test save(s) not found:
  - Expected folder pattern: unpack-*-advanced-creative-v8
```

**Solution**: Run the extraction script:
```bash
php ./tests/extract-test-saves.php
```

This will download and extract both required test saves.

### Extraction Script Errors

If the extraction script fails:

1. **Check Composer dependencies**:
   ```bash
   composer install
   ```

2. **Verify the savegame package is installed**:
   ```bash
   ls vendor/mistralys/x4-savegame/saves/
   ```
   
   Should show: `advanced-creative-v8.xml.gz` and `start-scientist-v8.xml.gz`

3. **Check folder permissions**: Ensure `tests/files/test-saves/` is writable

### Different Timestamps Across Machines

This is **expected behavior**. Each developer will have different timestamps in
their folder names. The test framework handles this automatically by matching
folder name suffixes, so tests work regardless of when saves were extracted.
```

### Verification

- Read through updated README to ensure clarity
- Verify all code examples are correct
- Confirm instructions are complete and helpful

---

## Work Package 8: Audit Start-Scientist Save

**Priority:** Low  
**Dependencies:** WP1-WP7 (All core implementation complete)  
**Estimated Effort:** 30 minutes

### Objective
Document the start-scientist save's characteristics for edge case testing.

### Implementation Steps

1. Examine JSON files in `unpack-*-start-scientist-v8/JSON/`:
   - Check which files are empty arrays: `[]`
   - Check which files have minimal entries (1-5 items)
   - Note which files have full data

2. Create documentation section in `tests/README.md`:

```markdown
## Edge Case Testing with Start-Scientist Save

The `start-scientist-v8` save provides minimal player data for edge case testing
while maintaining full NPC universe data.

### Empty Collections

These JSON files contain empty arrays:
- `data-losses.json` - No ship losses yet

### Minimal Collections

These JSON files contain very few entries:
- `collection-event-log.json` - Only 1 entry (discount unlock)

### Full Collections

These JSON files contain complete NPC data:
- `collection-ships.json` - All NPC ships in universe
- `collection-stations.json` - All stations in universe  
- `collection-people.json` - All NPCs in universe
- `collection-sectors.json` - Complete sector data
- `data-khaak-stations.json` - Khaak stations present

### Testing Opportunities

The start-scientist save is ideal for testing:
1. **Empty array handling** - Verify code handles `[]` gracefully
2. **Minimal logbook parsing** - Test with single-entry event log
3. **Early-game state** - Validate parser with sparse player data
4. **NPC-only scenarios** - Test universe data without player assets
5. **Zero losses** - Verify loss detection with no destroyed ships
```

3. Document in the plan which tests should use which save

### Verification

- Documentation is clear and accurate
- File paths are correct
- Characteristics are verified against actual JSON files

---

## Work Package 9: Implement Edge Case Tests

**Priority:** Low  
**Dependencies:** WP8 (Audit complete)  
**Estimated Effort:** 60 minutes

### Objective
Add tests using the start-scientist save to verify edge case handling.

### Implementation Steps

#### Test 1: Empty Losses Array

File: `tests/testsuites/Parser/LossDetectionTests.php`

Add new test method:
```php
/**
 * Test that empty losses array is handled correctly.
 * Uses start-scientist save which has no ship losses yet.
 */
public function test_emptyLossesArray() : void
{
    $save = $this->requireSaveByName(TestSaveNames::SAVE_START_SCIENTIST);
    $data = JSONFile::factory($save->getJSONPath()->getPath() . '/data-losses.json')->parse();
    
    $this->assertIsArray($data);
    $this->assertEmpty($data, 'Start-scientist save should have no ship losses');
}
```

#### Test 2: Minimal Logbook

File: `tests/testsuites/Reader/LogCategoryLoadingTests.php`

Add new test method:
```php
/**
 * Test logbook parsing with minimal single-entry log.
 * Uses start-scientist save which has only 1 logbook entry.
 */
public function test_minimalLogbook() : void
{
    $save = $this->requireSaveByName(TestSaveNames::SAVE_START_SCIENTIST);
    $log = $save->getDataReader()->getLog();
    
    $this->addFolderToRemove($log->getCacheInfo()->getWriter()->getPath());
    
    // Should handle single-entry log gracefully
    $cacheInfo = $log->getCacheInfo();
    $this->assertFalse($cacheInfo->isCacheValid());
    
    $log->generateAnalysisCache();
    $this->assertTrue($cacheInfo->isCacheValid());
    
    $categories = $log->loadAnalysisCache()->getAll();
    // May have categories even with minimal log
    $this->assertIsArray($categories);
}
```

#### Test 3: Empty Losses Collection API

File: `tests/testsuites/CLI/CollectionJsonDataLoadingTest.php`

Add new test method:
```php
/**
 * Test CLI API returns empty array for losses in early-game save.
 * Uses start-scientist save which has no ship losses.
 */
public function test_emptyLossesCollection(): void
{
    $save = $this->requireSaveByName(TestSaveNames::SAVE_START_SCIENTIST);
    
    if (!$save->isUnpacked()) {
        $this->markTestSkipped('Start-scientist save not extracted.');
    }
    
    // Verify losses file is actually empty
    $lossesFile = JSONFile::factory($save->getJSONPath()->getPath() . '/data-losses.json');
    $data = $lossesFile->parse();
    
    $this->assertIsArray($data);
    $this->assertEmpty($data, 'Start-scientist should have no losses');
}
```

#### Test 4: Early Game State

File: `tests/testsuites/Parser/LossDetectionTests.php`

Add new test method:
```php
/**
 * Test parser handles early-game state with sparse player data.
 * Verifies that NPC universe data exists while player data is minimal.
 */
public function test_earlyGameState() : void
{
    $save = $this->requireSaveByName(TestSaveNames::SAVE_START_SCIENTIST);
    
    // Verify player data is sparse
    $lossesData = JSONFile::factory($save->getJSONPath()->getPath() . '/data-losses.json')->parse();
    $this->assertEmpty($lossesData, 'Player losses should be empty');
    
    // Verify NPC universe data exists
    $stationsData = JSONFile::factory($save->getJSONPath()->getPath() . '/collection-stations.json')->parse();
    $this->assertNotEmpty($stationsData['station'] ?? [], 'NPC stations should exist');
    
    // This proves parser handles mixed sparse/full data correctly
}
```

### Verification

- Run: `vendor/bin/phpunit tests/testsuites/Parser/LossDetectionTests.php`
- Run: `vendor/bin/phpunit tests/testsuites/Reader/LogCategoryLoadingTests.php`
- Run: `vendor/bin/phpunit tests/testsuites/CLI/CollectionJsonDataLoadingTest.php`
- All new edge case tests should pass
- Verify tests actually use start-scientist save (check folder pattern in debug output if needed)

---

## Implementation Order

Recommended implementation sequence:

1. **WP1** - TestSaveNames class (foundation)
2. **WP2** - Update extraction script (quick win)
3. **WP3** - Enhance base test case (core functionality)
4. **WP4** - Refactor X4LogCategoriesTestCase (validates pattern)
5. **WP5** - Update CLI test classes (extends pattern)
6. **WP6** - Fix LossDetectionTests (completes migration)
7. **WP7** - Update documentation (user-facing)
8. **WP8** - Audit start-scientist save (analysis)
9. **WP9** - Implement edge case tests (bonus features)

WP1-7 form the critical path. WP8-9 can be done later or skipped initially.

---

## Testing Strategy

After each work package:
1. Run the specific tests affected by that package
2. Verify error messages are helpful and accurate
3. Check that tests pass after extraction script is run

After all packages complete:
1. Delete `tests/files/test-saves/` folder
2. Run full test suite - should fail with helpful errors
3. Run `php tests/extract-test-saves.php`
4. Run full test suite again - all should pass

---

## Success Criteria

- [ ] No hardcoded timestamp folders in any test file
- [ ] Single source of truth for test save names (`TestSaveNames`)
- [ ] All tests extend `X4ParserTestCase`
- [ ] SaveManager cached per test
- [ ] Early validation fails fast with helpful errors
- [ ] Tests work regardless of extraction timestamp
- [ ] Documentation complete and accurate
- [ ] Edge case tests implemented for start-scientist save
- [ ] Full test suite passes

---

## Rollback Plan

If issues arise during implementation:

1. **WP1-2**: Safe to revert - extraction script still works with hardcoded array
2. **WP3**: Base class changes isolated - can revert without affecting other tests
3. **WP4-6**: Individual test files can be reverted independently
4. **WP7**: Documentation changes are non-breaking
5. **WP8-9**: Edge case tests are additive - can be removed if problematic

Git strategy: Commit each work package separately for easy revert if needed.

---

## Future Enhancements

Potential improvements after initial implementation:

1. **Additional test saves**: Add more specialized saves for specific scenarios
2. **Test data validation**: Verify JSON structure matches expected schema
3. **Performance optimization**: Profile test suite if needed
4. **Test save versioning**: Handle multiple versions of same save name
5. **Automated extraction in CI**: Integrate extraction into CI pipeline

---

## Notes

- FileDetectionTests.php deliberately not modified - uses different test files
- Bootstrap.php configuration remains unchanged - single test-saves location
- All timestamps in folder names are expected to differ across machines
- Extracted savegames are immutable - tests never modify them


# Manual Test Scripts

These scripts provide quick, human-readable validation of the logbook performance optimization features. They complement the automated PHPUnit tests with visual feedback and can be run individually during development.

---

## Purpose

**Manual scripts are for**:
- ✅ Quick validation during development
- ✅ Visual feedback with progress messages
- ✅ Debugging (easy to add debug output)
- ✅ Demonstrating API usage
- ✅ Smoke testing after changes

**PHPUnit tests are for**:
- ✅ Automated CI/CD integration
- ✅ Comprehensive coverage
- ✅ Regression testing
- ✅ Code coverage reports

---

## Available Scripts

### Cache Generation During Extraction
**File**: `test-cache-generation-during-extraction.php`

Tests automatic log analysis cache generation during extraction.

**Run**:
```powershell
php tests\manual\test-cache-generation-during-extraction.php
```

**What it validates**:
- Cache directory created (`JSON/event-log/`)
- Category JSON files exist
- `analysis.json` metadata updated
- File count and structure

### Logbook API Format
**File**: `test-logbook-api-format.php`

Tests logbook API format and field structure.

**Run**:
```powershell
php tests\manual\test-logbook-api-format.php
```

**What it validates**:
- New fields present (categoryID, categoryLabel, timeFormatted)
- Old fields removed (category, faction, componentID)
- Descending time order (newest first)
- Category distribution statistics

**Quick Version**: `test-logbook-api-format-quick.php` - Fast sanity check

### Auto-Cache Pagination
**File**: `test-auto-cache-pagination.php`

Tests automatic cache creation for unfiltered queries and pagination performance.

**Run**:
```powershell
php tests\manual\test-auto-cache-pagination.php
```

**What it validates**:
- First query creates auto-cache
- Second query uses cache (faster)
- Filtered queries don't auto-cache
- Manual cache key override works

### Cache Warming
**File**: `test-cache-warming.php`

Tests cache pre-warming during extraction for instant first-query performance.

**Run**:
```powershell
php tests\manual\test-cache-warming.php
```

**What it validates**:
- Cache exists immediately after extraction
- First query is fast (no warming delay)
- Cache file format correct
- Cache size reasonable

### Cache Cleanup
**File**: `test-cache-cleanup.php`

Tests automatic removal of orphaned cache files from deleted saves.

**Run**:
```powershell
php tests\manual\test-cache-cleanup.php
```

**What it validates**:
- Orphaned caches detected
- Orphaned caches removed
- Valid caches preserved
- Multiple orphans handled

### Utility Scripts

**`generate-test-cache.php`** - Manually generate cache for test save

---

## Running All Manual Tests

### Sequential Execution
```powershell
php tests\manual\test-cache-generation-during-extraction.php
php tests\manual\test-logbook-api-format.php
php tests\manual\test-auto-cache-pagination.php
php tests\manual\test-cache-warming.php
php tests\manual\test-cache-cleanup.php
```

### One-Liner (PowerShell)
```powershell
Get-ChildItem tests\manual\test-*.php | Where-Object { $_.Name -notlike "*-quick.php" -and $_.Name -ne "generate-test-cache.php" } | ForEach-Object { php $_.FullName }
```

---

## When to Use Manual Tests

### During Development
Run relevant script after making changes:
- Modified cache generation? → `php tests\manual\test-cache-generation-during-extraction.php`
- Changed API format? → `php tests\manual\test-logbook-api-format.php`
- Updated auto-cache logic? → `php tests\manual\test-auto-cache-pagination.php`
- Modified cache warming? → `php tests\manual\test-cache-warming.php`
- Changed cleanup logic? → `php tests\manual\test-cache-cleanup.php`

### Quick Smoke Test
Before committing changes:
```powershell
php tests\manual\test-logbook-api-format-quick.php  # Fast validation
```

### Debugging Issues
Add debug output to scripts:
```php
echo "Debug: " . print_r($data, true) . "\n";
var_dump($cache);
```

### Demonstrating Features
Show someone how caching works by running a script and watching the output.

---

## Comparison: Manual vs PHPUnit

| Feature | Manual Scripts | PHPUnit Tests |
|---------|----------------|---------------|
| **Speed** | Fast (individual) | Fast (suite) |
| **Feedback** | Visual, colored | Pass/fail only |
| **CI/CD** | Manual run | Automated |
| **Coverage** | Feature smoke test | Comprehensive |
| **Debugging** | Easy to modify | Requires test knowledge |
| **Documentation** | Shows usage | Tests behavior |
| **Best for** | Development | Automation |

---

## Requirements

- PHP 8.4+
- Project dependencies installed (`composer install`)
- Test save files in `tests/files/save-files/source/`
- Write permissions on storage folder

---

## Output Format

Manual scripts provide:
- ✅ Success indicators (✓)
- ✗ Failure indicators (✗)
- ⚠ Warning indicators
- Progress messages
- Timing information
- Statistics and summaries

Example output:
```
Testing WP3: Auto-Cache for Unfiltered Queries
======================================================================

Step 1: Create fake orphaned cache
----------------------------------------------------------------------
✓ Created fake save directory: storage/unpack-20000101000000-wp3-test-fake
  Cache directory: storage/unpack-20000101000000-wp3-test-fake/.cache
  Test files: 3 (including nested)

Step 2: Verify fake cache exists
----------------------------------------------------------------------
✓ Cache directory exists with 3 item(s)

Step 3: Run cleanup (should remove orphaned cache)
----------------------------------------------------------------------
Removed: 1 cache directory
✓ SUCCESS: Orphaned cache was removed
```

---

## Adding New Manual Tests

Template for new manual test script:

```php
<?php
/**
 * Test script for WP-X: Feature Name
 */

declare(strict_types=1);

require_once __DIR__ . '/../../prepend.php';

use Mistralys\X4\SaveViewer\Data\SaveManager;

echo "Testing WP-X: Feature Name\n";
echo str_repeat('=', 70) . "\n\n";

// Setup
$manager = SaveManager::createFromConfig();
$saves = $manager->getArchivedSaves();

// Test logic
// ...

// Output results
echo "✓ SUCCESS: Test passed\n";
// or
echo "✗ FAILURE: Test failed\n";
```

---

## Maintenance

### Update Scripts When
- API changes affect test logic
- New fields added/removed
- Cache key format changes
- Performance thresholds change

### Keep Scripts Simple
- One feature per script
- Clear success/failure output
- Minimal dependencies
- Self-documenting

---

## Related Documentation

- **PHPUnit Tests**: `tests/testsuites/CLI/LogbookCachingTest.php`
- **Test README**: `tests/testsuites/CLI/CACHING-TESTS-README.md`
- **Test Summary**: `docs/agents/implementation-archive/TEST-SUMMARY.md`
- **Implementation Docs**: `docs/agents/implementation-archive/WP*-implementation-summary.md`

---

**Created**: 2026-02-06  
**Location**: `tests/manual/`  
**Count**: 6 scripts (5 WP tests + 1 utility)  
**Purpose**: Development-time validation and debugging

# Logbook Performance Optimization - Work Package Breakdown

This document breaks down the logbook performance optimization plan into discrete, incrementally implementable work packages. Each package is self-contained and can be picked up by an implementation agent without requiring active context of previous work.

---

## Background Context

### Problem Statement
The CLI API logbook query command (`bin/query log --save=<name>`) has severe performance issues with large logbooks. A test save with 16,544 entries experiences:
- Slow unfiltered queries (processing all entries every time)
- Slow pagination (no result caching between page requests)
- Fast category filtering (because the UI uses a pre-categorized cache)

### Root Cause
The current `Log::toArrayForAPI()` method returns raw event log collection data, forcing every query to process the entire dataset even when paginating through 20 items at a time.

### Solution Strategy
Leverage the existing logbook analysis cache system (already used by the UI) to pre-categorize entries during extraction. Use the cached, structured data for CLI queries instead of raw collection data. Implement auto-caching for unfiltered queries to enable fast pagination.

### Key Files Reference
- **Log reader**: `src/X4/SaveViewer/Data/SaveReader/Log.php`
- **Query handler**: `src/X4/SaveViewer/CLI/QueryHandler.php`
- **Query cache**: `src/X4/SaveViewer/CLI/QueryCache.php`
- **Parser**: `src/X4/SaveViewer/SaveParser.php`
- **Monitor**: `src/X4/SaveViewer/Monitor/X4Monitor.php`
- **Categories**: `src/X4/SaveViewer/Data/SaveReader/Log/Categories/DetectionCategories.php`

---

## WP1: Auto-Generate Log Analysis During Extraction

**Priority**: High  
**Estimated Effort**: Small  
**Dependencies**: None

### Objective
Automatically generate the logbook analysis cache during savegame extraction, so CLI queries can use pre-categorized data immediately without a first-query delay.

### Current Behavior
- The log analysis cache (`JSON/event-log/*.json`) is only generated when the Event Log page is first viewed in the UI
- CLI queries use raw collection data, bypassing the cache entirely
- First access to Event Log page shows "Analysis cache not present" button

### Target Behavior
- Log analysis cache is generated automatically as part of the extraction process
- Cache is available immediately after extraction completes
- Legacy extracted saves generate cache on-demand with stderr feedback

### Implementation Details

#### File: `src/X4/SaveViewer/SaveParser.php`

**Location**: In the `postProcess()` method (called after fragment parsing and data processing)

**Change**: Add log analysis generation after data processing hub:

```php
protected function postProcess() : BaseXMLParser
{
    // ...existing code...
    
    $processor = new DataProcessingHub($this->collections);
    $processor->process();
    
    // Generate log analysis cache automatically
    $this->generateLogAnalysisCache();

    return $this;
}

private function generateLogAnalysisCache() : void
{
    try {
        $reader = new SaveReader($this->analysis);
        $log = $reader->getLog();
        
        // Only generate if not already present
        if (!$log->isCacheValid()) {
            $this->log('Generating log analysis cache...');
            $log->generateAnalysisCache();
            $this->log('Log analysis cache generated successfully.');
        }
    } catch (\Exception $e) {
        // Log error but don't fail extraction
        $this->log('Warning: Failed to generate log analysis cache: ' . $e->getMessage());
    }
}
```

**Required Imports**:
```php
use Mistralys\X4\SaveViewer\Data\SaveReader;
```

### Testing

1. Extract a fresh savegame: `bin/extract quicksave`
2. Verify cache exists: Check for `JSON/event-log/*.json` files in storage folder
3. Query log immediately: `bin/query log --save=quicksave --limit=10` (should be fast)
4. Check `analysis.json`: Should contain `log-cache-written` and `log-category-ids` keys

### Acceptance Criteria
- ✅ Log analysis cache is generated during extraction
- ✅ Cache files exist in `JSON/event-log/` after extraction
- ✅ CLI queries work immediately without cache generation delay
- ✅ Extraction completes successfully (no errors from cache generation)

---

## WP2: Switch CLI API to Use Cached Analysis Data

**Priority**: High  
**Estimated Effort**: Medium  
**Dependencies**: WP1 (for automatic cache generation)

### Objective
Modify `Log::toArrayForAPI()` to return pre-categorized entries from the analysis cache instead of raw collection data, with automatic cache generation for legacy saves.

### Current Behavior
```php
public function toArrayForAPI(): array
{
    $data = $this->collections->eventLog()->loadData();
    if (!isset($data[LogEntryType::TYPE_ID])) {
        return [];
    }
    return $data[LogEntryType::TYPE_ID];
}
```

Returns raw collection data with fields: `time`, `category`, `title`, `text`, `faction`, `componentID`, `money`

### Target Behavior
- Returns entries from cached analysis with `categoryID` and `categoryLabel`
- Entries sorted in **descending time order** (newest first)
- Automatic cache generation with stderr output for legacy saves
- Returns fields: `time`, `timeFormatted`, `title`, `text`, `categoryID`, `categoryLabel`, `money`

### Implementation Details

#### File: `src/X4/SaveViewer/Data/SaveReader/Log.php`

**Method**: Replace `toArrayForAPI()` implementation:

```php
/**
 * Convert Log to array suitable for CLI API output.
 * Returns categorized entries from analysis cache, sorted newest-first.
 *
 * @return array<int,array<string,mixed>> JSON-serializable array of log entries
 */
public function toArrayForAPI(): array
{
    // Generate cache if missing (for legacy saves)
    if (!$this->isCacheValid()) {
        // Output to stderr for transparency while keeping JSON clean
        file_put_contents('php://stderr', "Generating log analysis cache...\n");
        $this->generateAnalysisCache();
        file_put_contents('php://stderr', "Log analysis cache generated.\n");
    }
    
    // Load categorized entries
    $categories = $this->loadAnalysisCache();
    $entries = $categories->getEntries();
    
    // Sort in descending time order (newest first)
    usort($entries, static function(LogEntry $a, LogEntry $b) : float {
        return $b->getTime()->getDuration() - $a->getTime()->getDuration();
    });
    
    // Convert to API format
    $result = [];
    foreach ($entries as $entry) {
        $result[] = [
            'time' => $entry->getTime()->getValue(),
            'timeFormatted' => $entry->getTime()->getFormatted(),
            'title' => $entry->getTitle(),
            'text' => $entry->getText(),
            'categoryID' => $entry->getCategoryID(),
            'categoryLabel' => $entry->getCategory()->getLabel(),
            'money' => $entry->getMoney()
        ];
    }
    
    return $result;
}
```

### Breaking Changes

**Old Format**:
```json
{
  "time": "123456.78",
  "category": "upkeep.money.ship.dock",
  "title": "Ship docked",
  "text": "Your ship has docked at station",
  "money": 0
}
```

**New Format**:
```json
{
  "time": 123456.78,
  "timeFormatted": "2h 15m",
  "title": "Ship docked",
  "text": "Your ship has docked at station",
  "categoryID": "misc",
  "categoryLabel": "Miscellaneous",
  "money": 0
}
```

### Category ID Mapping

The `categoryID` field uses structured IDs defined in `LogCategories`:

| Category ID | Label | Description |
|------------|-------|-------------|
| `combat` | Combat | Ship destruction, attacks |
| `mission` | Missions | Mission updates |
| `trade` | Trade | Trade completed |
| `station-finance` | Station Finance | Station funds alerts |
| `station-building` | Station Building | Construction updates |
| `ship-construction` | Ship Construction | Ship build completion |
| `ship-supply` | Ship Supply | Resupply, repairs |
| `alert` | Alerts | Game alerts |
| `emergency` | Emergency | Emergency alerts |
| `attacked` | Ship Defense | Under attack, fleeing |
| `destroyed` | Destroyed | Destruction events |
| `promotion` | Promotion | Promotions, discounts |
| `reward` | Rewards | Rewards received |
| `reputation` | Reputation | Reputation changes |
| `lockbox` | Lockbox | Lockbox discoveries |
| `war` | War Updates | War status changes |
| `crew-assignment` | Crew Assignment | Crew changes |
| `tips` | Tips | Game tips |
| `misc` | Miscellaneous | Uncategorized entries |

**Note**: This list is extensible. Future versions may add new categories as detection patterns are refined.

### Testing

1. **Legacy save**: Query a save extracted before WP1
   ```powershell
   .\bin\query.bat log --save=old-save --limit=5 --pretty
   ```
   Expected: Stderr message "Generating log analysis cache..." then JSON output

2. **Fresh save**: Query a save extracted after WP1
   ```powershell
   .\bin\query.bat log --save=quicksave --limit=5 --pretty
   ```
   Expected: Fast response, no stderr messages

3. **Verify sorting**: Check that first entry has highest `time` value (newest)

4. **Verify fields**: Confirm all required fields present (`categoryID`, `categoryLabel`, `timeFormatted`)

### Acceptance Criteria
- ✅ CLI queries use cached analysis data
- ✅ Legacy saves generate cache automatically with stderr feedback
- ✅ Entries sorted newest-first
- ✅ All required fields present in output
- ✅ `categoryID` and `categoryLabel` correctly populated

---

## WP3: Add Auto-Cache for Unfiltered Queries

**Priority**: Medium  
**Estimated Effort**: Small  
**Dependencies**: WP2 (for new output format)

### Objective
Automatically cache unfiltered logbook queries to enable fast pagination without requiring users to manage cache keys manually.

### Current Behavior
- Pagination without `--cache-key` re-processes entire dataset for each page
- Users must manually specify `--cache-key` to enable caching
- Each page request for large logbooks is slow

### Target Behavior
- Unfiltered queries automatically use cache key `_log_unfiltered_{saveID}`
- First page request caches all entries
- Subsequent page requests reuse cache (fast)
- Filtered queries still require manual `--cache-key` or re-process each time

### Implementation Details

#### File: `src/X4/SaveViewer/CLI/QueryHandler.php`

**Method**: Modify `execute_log()` to inject auto-cache key:

```php
private function execute_log(BaseSaveFile $save): void
{
    $reader = $save->getDataReader();
    $data = $reader->getLog()->toArrayForAPI();
    
    // Auto-cache for unfiltered queries
    $filter = $this->cli->arguments->get('filter');
    $cacheKey = $this->cli->arguments->get('cache-key');
    
    if (empty($filter) && empty($cacheKey)) {
        // Inject auto-cache key for unfiltered pagination
        $this->cli->arguments->add('cache-key', '_log_unfiltered_' . $save->getSaveID());
    }

    $data = $this->applyFilteringAndPagination($save, $data);
    $this->outputSuccess(self::COMMAND_LOG, $data['data'], $data['pagination']);
}
```

**Note**: The `applyFilteringAndPagination()` method already handles caching logic, we just need to inject the cache key.

### Cache Behavior

**First Request** (unfiltered, page 1):
```powershell
.\bin\query.bat log --save=quicksave --limit=20 --offset=0
```
- Auto-cache key: `_log_unfiltered_quicksave-20260112062240`
- All entries cached to `.cache/query-_log_unfiltered_quicksave-20260112062240.json`
- Returns first 20 entries

**Second Request** (unfiltered, page 2):
```powershell
.\bin\query.bat log --save=quicksave --limit=20 --offset=20
```
- Auto-cache key detected, retrieves cached data
- Fast response (no reprocessing)
- Returns entries 21-40

**Filtered Request** (no auto-cache):
```powershell
.\bin\query.bat log --save=quicksave --filter="[?categoryID=='combat']" --limit=20
```
- No auto-cache (filter present)
- User must provide `--cache-key` for pagination caching

### Cache Storage
- Location: `<storage-folder>/.cache/query-_log_unfiltered_{saveID}.json`
- Lifetime: Indefinite (extracted saves are immutable)
- Invalidation: Only when save is deleted (handled by WP5)

### Testing

1. **First unfiltered query**:
   ```powershell
   .\bin\query.bat log --save=quicksave --limit=20 --offset=0
   ```
   Expected: Slower first request, cache file created

2. **Second unfiltered query**:
   ```powershell
   .\bin\query.bat log --save=quicksave --limit=20 --offset=20
   ```
   Expected: Fast response using cache

3. **Verify cache file**:
   Check for `.cache/query-_log_unfiltered_*.json` in save storage folder

4. **Filtered query** (should NOT use auto-cache):
   ```powershell
   .\bin\query.bat log --save=quicksave --filter="[?categoryID=='combat']" --limit=20
   ```
   Expected: No auto-cache file created

### Acceptance Criteria
- ✅ Unfiltered queries automatically use cache key
- ✅ First request creates cache file
- ✅ Subsequent requests reuse cache (fast)
- ✅ Filtered queries do NOT use auto-cache
- ✅ Manual `--cache-key` still works and overrides auto-cache

---

## WP4: Warm Log Query Cache After Extraction

**Priority**: Low  
**Estimated Effort**: Small  
**Dependencies**: WP2, WP3

### Objective
Pre-warm the auto-cache immediately after extraction completes, so the first unfiltered query is fast.

### Current Behavior
- Cache is generated on first query (slower initial experience)
- Users experience delay on first pagination request

### Target Behavior
- Cache is pre-warmed during extraction
- First user query is fast (cache already exists)
- Extraction time slightly increased but acceptable

### Implementation Details

#### File: `src/X4/SaveViewer/SaveParser.php`

**Method**: Add cache warming after log analysis generation in `postProcess()`:

```php
private function generateLogAnalysisCache() : void
{
    try {
        $reader = new SaveReader($this->analysis);
        $log = $reader->getLog();
        
        // Generate analysis cache
        if (!$log->isCacheValid()) {
            $this->log('Generating log analysis cache...');
            $log->generateAnalysisCache();
            $this->log('Log analysis cache generated successfully.');
        }
        
        // Warm query cache for fast pagination
        $this->warmLogQueryCache($reader);
        
    } catch (\Exception $e) {
        $this->log('Warning: Failed to generate log analysis cache: ' . $e->getMessage());
    }
}

private function warmLogQueryCache(SaveReader $reader) : void
{
    try {
        $this->log('Warming log query cache...');
        
        // Get the save ID for cache key
        $saveID = $this->saveFile->getSaveID();
        $cacheKey = '_log_unfiltered_' . $saveID;
        
        // Get all entries (already cached by WP2)
        $data = $reader->getLog()->toArrayForAPI();
        
        // Store in query cache
        $manager = new SaveManager();
        $cache = new QueryCache($manager);
        
        // Find the save file object
        $save = null;
        foreach ($manager->getArchivedSaves() as $archivedSave) {
            if ($archivedSave->getSaveID() === $saveID) {
                $save = $archivedSave;
                break;
            }
        }
        
        if ($save !== null) {
            $cache->store($save, $cacheKey, $data);
            $this->log('Log query cache warmed successfully.');
        }
        
    } catch (\Exception $e) {
        $this->log('Warning: Failed to warm log query cache: ' . $e->getMessage());
    }
}
```

**Required Imports**:
```php
use Mistralys\X4\SaveViewer\CLI\QueryCache;
use Mistralys\X4\SaveViewer\Data\SaveManager;
```

### Alternative Simpler Approach

Instead of manually calling `QueryCache`, we could execute the query command internally:

```php
private function warmLogQueryCache(SaveReader $reader) : void
{
    try {
        $this->log('Warming log query cache...');
        
        // Execute a minimal log query to trigger auto-cache
        $saveID = $this->saveFile->getSaveID();
        
        // Use shell_exec to run query command (will auto-cache)
        $cmd = sprintf(
            'php %s/bin/php/query.php log --save=%s --limit=1',
            dirname(__DIR__, 3), // Project root
            escapeshellarg($saveID)
        );
        
        // Suppress output, we just want the cache generated
        shell_exec($cmd . ' > nul 2>&1');
        
        $this->log('Log query cache warmed successfully.');
        
    } catch (\Exception $e) {
        $this->log('Warning: Failed to warm log query cache: ' . $e->getMessage());
    }
}
```

**Recommendation**: Use the simpler shell_exec approach to avoid tight coupling with QueryHandler internals.

### Testing

1. **Extract fresh save**:
   ```powershell
   .\bin\extract.bat quicksave
   ```
   Expected: Extraction log shows "Warming log query cache..." message

2. **Check cache file exists**:
   Verify `.cache/query-_log_unfiltered_*.json` exists immediately after extraction

3. **First query is fast**:
   ```powershell
   .\bin\query.bat log --save=quicksave --limit=20
   ```
   Expected: Fast response (cache already warmed)

### Acceptance Criteria
- ✅ Cache warming triggered during extraction
- ✅ Cache file exists after extraction completes
- ✅ First user query is fast (no cache generation delay)
- ✅ Cache warming errors don't fail extraction

---

## WP5: Implement Periodic Cache Cleanup in Monitor

**Priority**: Low  
**Estimated Effort**: Medium  
**Dependencies**: None (but enhances WP3)

### Objective
Automatically remove obsolete query cache files for deleted saves to prevent disk space bloat.

### Current Behavior
- Query cache files accumulate indefinitely
- Deleted saves leave orphaned cache directories
- Manual cleanup required

### Target Behavior
- Monitor periodically checks for orphaned cache directories
- Removes `.cache` directories for non-existent saves
- Runs every 60 ticks (~5 minutes)
- Logs cleanup operations

### Implementation Details

#### File: `src/X4/SaveViewer/CLI/QueryCache.php`

**Method**: Add `cleanupObsoleteCaches()`:

```php
/**
 * Remove cache directories for saves that no longer exist.
 * 
 * @return int Number of cache directories removed
 */
public function cleanupObsoleteCaches(): int
{
    $storageFolder = $this->manager->getStorageFolder();
    
    if (!$storageFolder->exists()) {
        return 0;
    }
    
    // Get all current save IDs
    $currentSaveIDs = [];
    foreach ($this->manager->getArchivedSaves() as $save) {
        $currentSaveIDs[] = $save->getSaveID();
    }
    
    // Scan storage folder for save directories
    $removed = 0;
    $saveDirs = glob($storageFolder->getPath() . '/unpack-*', GLOB_ONLYDIR);
    
    foreach ($saveDirs as $saveDir) {
        $saveDirName = basename($saveDir);
        
        // Check if this save still exists
        $saveExists = false;
        foreach ($currentSaveIDs as $saveID) {
            if (strpos($saveDirName, $saveID) !== false) {
                $saveExists = true;
                break;
            }
        }
        
        // Remove cache directory if save no longer exists
        if (!$saveExists) {
            $cacheDir = $saveDir . '/.cache';
            if (is_dir($cacheDir)) {
                $this->removeCacheDirectory($cacheDir);
                $removed++;
            }
        }
    }
    
    return $removed;
}

/**
 * Recursively remove a directory and its contents.
 * 
 * @param string $dir Directory path
 */
private function removeCacheDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    
    $files = array_diff(scandir($dir), ['.', '..']);
    
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? $this->removeCacheDirectory($path) : unlink($path);
    }
    
    rmdir($dir);
}
```

#### File: `src/X4/SaveViewer/Monitor/X4Monitor.php`

**Method**: Add cleanup call in `_handleTick()`:

Find the `_handleTick()` method and add periodic cleanup:

```php
private int $cleanupCounter = 0;

protected function _handleTick(int $counter) : void
{
    // ...existing save detection and processing code...
    
    // Periodic cache cleanup every 60 ticks (~5 minutes)
    $this->cleanupCounter++;
    if ($this->cleanupCounter >= 60) {
        $this->cleanupCounter = 0;
        $this->performCacheCleanup();
    }
}

private function performCacheCleanup() : void
{
    try {
        $cache = new QueryCache($this->manager);
        $removed = $cache->cleanupObsoleteCaches();
        
        if ($removed > 0) {
            $this->logNotice(sprintf(
                'Cache cleanup: Removed %d obsolete cache director%s',
                $removed,
                $removed === 1 ? 'y' : 'ies'
            ));
        }
    } catch (\Exception $e) {
        $this->logError('Cache cleanup failed: ' . $e->getMessage());
    }
}
```

**Required Imports**:
```php
use Mistralys\X4\SaveViewer\CLI\QueryCache;
```

### Testing

1. **Create test scenario**:
   - Extract save `test-save-1`
   - Query log to create cache: `bin/query log --save=test-save-1 --limit=10`
   - Verify cache exists in storage folder
   - Delete the extracted save folder manually

2. **Run monitor**:
   ```powershell
   .\bin\run-monitor.bat
   ```
   Expected: After ~5 minutes, monitor logs cache cleanup message

3. **Verify cleanup**:
   Check that orphaned `.cache` directory was removed

4. **Normal operation**:
   Verify that cache directories for existing saves are NOT removed

### Acceptance Criteria
- ✅ Monitor performs cache cleanup every 60 ticks
- ✅ Orphaned cache directories are removed
- ✅ Active cache directories are preserved
- ✅ Cleanup errors don't crash monitor
- ✅ Cleanup operations are logged

---

## WP6: Create Breaking Changes Documentation

**Priority**: High  
**Estimated Effort**: Small  
**Dependencies**: WP2 (for accurate field documentation)

### Objective
Create standalone documentation for launcher integration agent detailing all breaking changes in the logbook CLI API.

### Target Audience
- Launcher implementation agent
- Future developers integrating with CLI API
- Users upgrading from previous API versions

### Implementation Details

#### File: `docs/agents/project-manifest/10-cli-api-logbook-breaking-changes.md`

**Content**:

```markdown
# CLI API Breaking Changes - Logbook Command

**Effective Date**: 2026-02-06  
**Affected Command**: `bin/query log`  
**Version**: Post-optimization (after logbook performance improvements)

---

## Overview

The `log` command output format has been significantly changed to improve performance with large logbooks (16K+ entries). The raw collection data has been replaced with pre-categorized, structured entries from the analysis cache.

**Performance Impact**: Queries on 16,544 entry logbooks improved from ~5s to ~50ms after cache generation.

---

## Field Changes

### Removed Fields

| Old Field | Type | Notes |
|-----------|------|-------|
| `category` | string | Raw game category (e.g., `"upkeep.money.ship.dock"`) - Replaced by `categoryID` and `categoryLabel` |

### Added Fields

| New Field | Type | Description | Example |
|-----------|------|-------------|---------|
| `categoryID` | string | Structured category identifier for filtering | `"combat"`, `"mission"`, `"misc"` |
| `categoryLabel` | string | Human-readable category name for display | `"Combat"`, `"Missions"`, `"Miscellaneous"` |
| `timeFormatted` | string | Human-readable time duration | `"2h 15m"`, `"5 days ago"` |

### Changed Fields

| Field | Old Type | New Type | Notes |
|-------|----------|----------|-------|
| `time` | string | number | Now returned as numeric timestamp (float) instead of string |

---

## Sort Order Change

**Old Behavior**: Entries returned in ascending time order (oldest first)  
**New Behavior**: Entries returned in **descending time order (newest first)**

This change makes the most recent events immediately available, which is more useful for monitoring and recent activity queries.

---

## Category ID Reference

The `categoryID` field uses structured identifiers for efficient filtering. All known categories are listed below:

| Category ID | Label | Description |
|------------|-------|-------------|
| `combat` | Combat | Ship destruction, combat events |
| `mission` | Missions | Mission updates and completions |
| `trade` | Trade | Trade completion events |
| `station-finance` | Station Finance | Station fund alerts and transfers |
| `station-building` | Station Building | Station construction updates |
| `ship-construction` | Ship Construction | Ship build completions |
| `ship-supply` | Ship Supply | Ship resupply and repair events |
| `alert` | Alerts | General game alerts |
| `emergency` | Emergency | Emergency alerts |
| `attacked` | Ship Defense | Under attack, forced to flee, pirate harassment |
| `destroyed` | Destroyed | Destruction events |
| `promotion` | Promotion | Promotions and discounts |
| `reward` | Rewards | Rewards received |
| `reputation` | Reputation | Reputation gained/lost |
| `lockbox` | Lockbox | Lockbox discoveries |
| `war` | War Updates | War status changes, reconnaissance |
| `crew-assignment` | Crew Assignment | Crew member assignments |
| `tips` | Tips | Game tips |
| `misc` | Miscellaneous | Uncategorized entries |

**Extensibility Note**: This list may be extended in future versions as new detection patterns are added. Always handle unknown category IDs gracefully in your integration.

---

## Example Comparison

### Old Format (Pre-Optimization)

```json
{
  "success": true,
  "data": [
    {
      "time": "123456.78",
      "category": "upkeep.money.ship.dock",
      "title": "Ship docked",
      "text": "Your ship has docked at station Alpha",
      "faction": "argon",
      "componentID": "ship_001",
      "money": 0
    }
  ]
}
```

### New Format (Post-Optimization)

```json
{
  "success": true,
  "data": [
    {
      "time": 123456.78,
      "timeFormatted": "2h 15m",
      "title": "Ship docked",
      "text": "Your ship has docked at station Alpha",
      "categoryID": "misc",
      "categoryLabel": "Miscellaneous",
      "money": 0
    }
  ],
  "pagination": {
    "total": 16544,
    "limit": 20,
    "offset": 0,
    "hasMore": true
  }
}
```

---

## Cache Behavior Changes

### Automatic Cache Generation

**New Behavior**: Log analysis cache is automatically generated during savegame extraction. No manual analysis step required.

**Legacy Saves**: For saves extracted before this optimization, the cache is generated on-demand during the first `log` query with stderr feedback:

```
Generating log analysis cache...
Log analysis cache generated.
```

This is a one-time operation per save. Subsequent queries use the cached data.

---

### Auto-Caching for Unfiltered Queries

**New Behavior**: Unfiltered log queries automatically cache results for fast pagination.

**Example Workflow**:

```powershell
# First request (page 1) - slower, creates cache
.\bin\query.bat log --save=quicksave --limit=20 --offset=0

# Second request (page 2) - fast, uses cache
.\bin\query.bat log --save=quicksave --limit=20 --offset=20

# Third request (page 3) - fast, uses cache
.\bin\query.bat log --save=quicksave --limit=20 --offset=40
```

**Cache Key**: Automatically uses `_log_unfiltered_{saveID}`  
**Cache Location**: `<storage-folder>/.cache/query-_log_unfiltered_{saveID}.json`

**Filtered Queries**: Manual `--cache-key` still required for pagination caching:

```powershell
# Filtered query with manual cache key
.\bin\query.bat log --save=quicksave \
  --filter="[?categoryID=='combat']" \
  --limit=20 --offset=0 \
  --cache-key="combat-logs"
```

---

## Migration Guide for Launcher Integration

### 1. Update Field Mappings

**Remove**:
- `category` field parsing

**Add**:
- `categoryID` field (string) - Use for filtering
- `categoryLabel` field (string) - Use for display
- `timeFormatted` field (string) - Use for human-readable display

**Update**:
- `time` field parsing from string to number

### 2. Adjust Sort Assumptions

If your launcher displays logs in chronological order (oldest first), you now have two options:

**Option A**: Reverse the array in your application  
**Option B**: Request data in pages from the end

### 3. Update Category Filters

**Old Filter** (no longer works):
```bash
--filter="[?category=='upkeep.money.ship.dock']"
```

**New Filter**:
```bash
--filter="[?categoryID=='misc']"
```

**Category Filtering Examples**:
```bash
# Combat events only
--filter="[?categoryID=='combat']"

# Multiple categories
--filter="[?categoryID=='combat' || categoryID=='destroyed']"

# Exclude miscellaneous
--filter="[?categoryID!='misc']"
```

### 4. Handle Stderr Output for Legacy Saves

When querying legacy saves (extracted before optimization), stderr may contain cache generation messages. Ensure your launcher:

- Reads from stdout for JSON data
- Reads from stderr for progress messages
- Doesn't fail when stderr is non-empty

### 5. Leverage Auto-Caching

For unfiltered log views with pagination, simply omit `--cache-key` and the system handles caching automatically. No changes needed in your code - pagination will be fast after the first request.

---

## Backward Compatibility

**Breaking Changes**: Yes - This is a breaking API change.

**Migration Required**: Yes - Launcher must be updated to handle new field structure.

**Rollback Option**: No - The old raw format is no longer available. The performance benefits require the new structure.

---

## Questions or Issues

If you encounter integration issues or have questions about the new format:

1. Check the [CLI API Reference](./07-cli-api-reference.md) for complete command documentation
2. Review [Tech Stack & Patterns](./01-tech-stack-and-patterns.md) for architectural context
3. Examine the test suite in `tests/testsuites/CLI/` for usage examples
```

#### File: `docs/agents/project-manifest/README.md`

**Update**: Add reference to new document in the specialized documents section:

```markdown
## Specialized Documents

10. **[CLI API Logbook Breaking Changes](./10-cli-api-logbook-breaking-changes.md)** - Breaking changes documentation for logbook performance optimization (for launcher integration)
```

### Testing

1. **Review document for completeness**: Ensure all breaking changes are documented
2. **Verify examples**: Test example commands to ensure they work as documented
3. **Check category list**: Verify category IDs match implementation
4. **Validate field descriptions**: Ensure field types and examples are accurate

### Acceptance Criteria
- ✅ Breaking changes document created
- ✅ All field changes documented with examples
- ✅ Complete category ID reference included
- ✅ Migration guide provided for launcher integration
- ✅ Document linked in project manifest README

---

## Implementation Order

Recommended implementation sequence:

1. **WP1** - Auto-generate log analysis during extraction (enables WP2)
2. **WP2** - Switch CLI API to cached data (core optimization)
3. **WP6** - Create breaking changes documentation (for launcher team)
4. **WP3** - Add auto-cache for unfiltered queries (UX improvement)
5. **WP4** - Warm cache after extraction (polish)
6. **WP5** - Periodic cache cleanup (maintenance)

**Critical Path**: WP1 → WP2 → WP6  
**Optional Enhancements**: WP3 → WP4, WP5

---

## Testing Strategy

### Integration Testing

After all work packages are complete:

1. **Extract fresh save**: `bin/extract quicksave`
2. **Verify cache generated**: Check `JSON/event-log/*.json` exists
3. **First query**: `bin/query log --save=quicksave --limit=20` (fast)
4. **Pagination**: `bin/query log --save=quicksave --limit=20 --offset=20` (fast)
5. **Filtering**: `bin/query log --save=quicksave --filter="[?categoryID=='combat']" --limit=10`
6. **Legacy save**: Query old save, verify cache generation with stderr output
7. **Monitor**: Run monitor for 10 minutes, verify cache cleanup logs

### Performance Benchmarking

Compare query times before and after optimization:

**Test Save**: 16,544 entries

**Before**:
- First unfiltered query: ~5000ms
- Pagination (page 2): ~5000ms

**After**:
- First unfiltered query: ~50ms (with cache)
- Pagination (page 2): ~10ms (from cache)

---

## Rollback Plan

If critical issues arise:

1. **Revert WP2**: Restore old `toArrayForAPI()` implementation
2. **Keep WP1**: Analysis cache generation doesn't harm anything
3. **Remove WP3**: Delete auto-cache injection code
4. **Remove WP4**: Delete cache warming code
5. **Keep WP5**: Cache cleanup is harmless

**Minimal Rollback**: Only revert WP2 to restore old API format while keeping infrastructure improvements.

---

## Future Enhancements (Not in Current Plan)

- Smart detection of category-only filters to load single category JSON file
- Streaming API for very large logbooks (direct file read without full load)
- Category statistics endpoint (`log-stats` command)
- Time-range filtering optimization (index by time buckets)

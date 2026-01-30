# CLI API Implementation - Work Package Breakdown

This document organizes the CLI API implementation into distinct, incremental work packages that can be implemented and validated independently.

---

## Work Package Overview

| WP# | Name | Duration | Dependencies | Validation |
|-----|------|----------|--------------|------------|
| WP1 | Foundation & Response Builder | 3-4 hours | None | Unit tests pass |
| WP2 | Cache Manager | 3-4 hours | None | Unit tests pass |
| WP3 | Query Validator | 2-3 hours | WP1 | Unit tests pass |
| WP4 | Data Serializers (Core) | 4-5 hours | WP1 | Manual testing |
| WP5 | Data Serializers (Extended) | 4-5 hours | WP4 | Manual testing |
| WP6 | Query Handler (Core Commands) | 4-5 hours | WP1, WP2, WP3, WP4 | Integration tests |
| WP7 | Query Handler (Collection Commands) | 2-3 hours | WP6 | Integration tests |
| WP8 | Entry Points & Scripts | 1-2 hours | WP6, WP7 | Manual testing |
| WP9 | Documentation & Deprecation | 4-5 hours | WP8 | Documentation review |

**Total Estimated Time**: 27-36 hours

---

## WP1: Foundation & Response Builder

**Duration**: 3-4 hours  
**Dependencies**: None  
**Status**: Not Started

### Objectives
- Create the `JsonResponseBuilder` helper class
- Implement standard response envelope format
- Add version reading from VERSION file
- Create base exception handling

### Files to Create
- `src/X4/SaveViewer/CLI/JsonResponseBuilder.php`

### Deliverables

#### JsonResponseBuilder Class
```php
class JsonResponseBuilder
{
    public static function success(
        string $command, 
        mixed $data, 
        ?array $pagination = null
    ): string
    
    public static function error(
        \Throwable $e, 
        ?string $command = null
    ): string
    
    private static function getVersion(): string
    private static function getTimestamp(): string
    private static function buildErrorChain(\Throwable $e): array
}
```

### Implementation Tasks
1. [ ] Create `JsonResponseBuilder` class skeleton
2. [ ] Implement `success()` method with envelope structure
3. [ ] Implement `error()` method with exception chain
4. [ ] Add `getVersion()` to read VERSION file
5. [ ] Add `getTimestamp()` for ISO 8601 UTC timestamps
6. [ ] Add `buildErrorChain()` for nested exceptions
7. [ ] Handle `BaseException` details extraction
8. [ ] Add JSON encoding with proper flags
9. [ ] Add `--pretty` flag support (parameter)

### Validation Criteria
- [ ] Unit test: success response has all required fields
- [ ] Unit test: error response matches JsonOutput format
- [ ] Unit test: version field matches VERSION file content
- [ ] Unit test: timestamp is valid ISO 8601 UTC
- [ ] Unit test: exception chain includes all nested exceptions
- [ ] Unit test: BaseException details are included
- [ ] Unit test: JSON encoding uses correct flags
- [ ] Unit test: pretty printing works when enabled

### Testing Example
```php
$response = JsonResponseBuilder::success('test', ['item' => 'value']);
$json = json_decode($response, true);
assert($json['success'] === true);
assert($json['version'] === trim(file_get_contents(VERSION)));
assert(isset($json['timestamp']));
assert($json['command'] === 'test');
assert($json['data'] === ['item' => 'value']);
```

---

## WP2: Cache Manager

**Duration**: 3-4 hours  
**Dependencies**: None  
**Status**: Not Started

### Objectives
- Create the `QueryCache` class
- Implement per-save cache storage
- Add cache validation based on save modification time
- Implement cache clearing functionality

### Files to Create
- `src/X4/SaveViewer/CLI/QueryCache.php`

### Deliverables

#### QueryCache Class
```php
class QueryCache
{
    public function __construct(SaveManager $manager)
    
    public function store(BaseSaveFile $save, string $cacheKey, array $data): void
    public function retrieve(BaseSaveFile $save, string $cacheKey): ?array
    public function isValid(BaseSaveFile $save, string $cacheKey): bool
    public function clearAll(): int  // Returns count of cleared caches
    
    private function getCachePath(BaseSaveFile $save, string $cacheKey): string
    private function getCacheDir(BaseSaveFile $save): string
    private function getSaveModifiedTime(BaseSaveFile $save): int
}
```

### Implementation Tasks
1. [ ] Create `QueryCache` class skeleton
2. [ ] Implement cache path generation (`.cache/query-<key>.json`)
3. [ ] Implement `store()` method with directory creation
4. [ ] Implement `retrieve()` method with validation
5. [ ] Implement `isValid()` checking save modification time
6. [ ] Implement `clearAll()` to remove all `.cache` directories
7. [ ] Add error handling for file operations
8. [ ] Add logging for cache operations (optional)

### Validation Criteria
- [ ] Unit test: cache directory is created automatically
- [ ] Unit test: cache file is stored with correct name
- [ ] Unit test: retrieve returns stored data
- [ ] Unit test: retrieve returns null for non-existent cache
- [ ] Unit test: isValid returns false when save modified
- [ ] Unit test: isValid returns true when save unchanged
- [ ] Unit test: clearAll removes all cache directories
- [ ] Unit test: clearAll returns correct count
- [ ] Integration test: cache isolated per save

### Testing Example
```php
$cache = new QueryCache($manager);
$save = $manager->getSaveByName('quicksave');
$data = ['test' => 'data'];

$cache->store($save, 'test-key', $data);
assert($cache->isValid($save, 'test-key') === true);

$retrieved = $cache->retrieve($save, 'test-key');
assert($retrieved === $data);

// Simulate save modification
touch($save->getSaveFile()->getPath());
assert($cache->isValid($save, 'test-key') === false);
```

---

## WP3: Query Validator

**Duration**: 2-3 hours  
**Dependencies**: WP1 (JsonResponseBuilder)  
**Status**: Not Started

### Objectives
- Create the `QueryValidator` class
- Implement save existence and extraction validation
- Implement pagination parameter validation
- Add actionable error messages

### Files to Create
- `src/X4/SaveViewer/CLI/QueryValidator.php`
- `src/X4/SaveViewer/CLI/QueryValidationException.php`

### Deliverables

#### QueryValidator Class
```php
class QueryValidator
{
    public function __construct(SaveManager $manager)
    
    public function validateSave(string $saveIdentifier): BaseSaveFile
    public function validatePagination(?int $limit, ?int $offset): void
    public function validateCacheKey(?string $cacheKey): void
    
    private function throwValidationError(
        string $message, 
        int $code, 
        array $actions = []
    ): never
}

class QueryValidationException extends SaveViewerException
{
    private array $actions;
    
    public function __construct(
        string $message, 
        int $code, 
        array $actions = []
    )
    
    public function getActions(): array
}
```

### Implementation Tasks
1. [ ] Create `QueryValidationException` class
2. [ ] Create `QueryValidator` class skeleton
3. [ ] Implement `validateSave()` checking existence
4. [ ] Implement `validateSave()` checking extraction status
5. [ ] Add actionable error messages with commands
6. [ ] Implement `validatePagination()` for limit/offset
7. [ ] Implement `validateCacheKey()` for valid characters
8. [ ] Add proper error codes from existing constants

### Validation Criteria
- [ ] Unit test: validates existing save successfully
- [ ] Unit test: throws error for non-existent save
- [ ] Unit test: throws error for unextracted save with action
- [ ] Unit test: validates positive limit/offset
- [ ] Unit test: throws error for negative limit/offset
- [ ] Unit test: validates cache key format
- [ ] Unit test: error includes suggested action
- [ ] Unit test: uses correct error codes

### Testing Example
```php
$validator = new QueryValidator($manager);

// Valid save
$save = $validator->validateSave('quicksave');
assert($save instanceof BaseSaveFile);

// Invalid save
try {
    $validator->validateSave('nonexistent');
    assert(false, 'Should throw exception');
} catch (QueryValidationException $e) {
    assert($e->getCode() === SaveManager::ERROR_CANNOT_FIND_BY_NAME);
    assert(!empty($e->getActions()));
}

// Valid pagination
$validator->validatePagination(50, 0); // Should not throw

// Invalid pagination
try {
    $validator->validatePagination(-1, 0);
    assert(false, 'Should throw exception');
} catch (QueryValidationException $e) {
    // Expected
}
```

---

## WP4: Data Serializers (Core)

**Duration**: 4-5 hours  
**Dependencies**: WP1 (JsonResponseBuilder)  
**Status**: Not Started

### Objectives
- Implement `toArrayForAPI()` for core SaveReader classes
- Handle DateTime and GameTime conversions
- Ensure JSON-serializable output

### Files to Modify
- `src/X4/SaveViewer/Data/SaveReader/SaveInfo.php`
- `src/X4/SaveViewer/Data/SaveReader/PlayerInfo.php`
- `src/X4/SaveViewer/Data/SaveReader/Statistics.php`
- `src/X4/SaveViewer/Data/SaveReader/Factions.php`

### Deliverables

Each class gets a `toArrayForAPI()` method that returns JSON-serializable data.

#### SaveInfo
```php
public function toArrayForAPI(): array
{
    return [
        'saveName' => $this->getSaveName(),
        'playerName' => $this->getPlayerName(),
        'money' => $this->getMoney(),
        'moneyFormatted' => $this->getMoneyPretty(),
        'saveDate' => $this->getSaveDate()?->format('c'),
        'gameStartTime' => $this->getGameStartTime(),
        'location' => $this->getString('location')
    ];
}
```

### Implementation Tasks
1. [ ] Implement `SaveInfo::toArrayForAPI()`
2. [ ] Implement `PlayerInfo::toArrayForAPI()`
3. [ ] Implement `Statistics::toArrayForAPI()`
4. [ ] Implement `Factions::toArrayForAPI()`
5. [ ] Add helper method for DateTime conversion
6. [ ] Add helper method for GameTime conversion
7. [ ] Test JSON encoding of all outputs

### Validation Criteria
- [ ] Manual test: SaveInfo returns valid JSON structure
- [ ] Manual test: PlayerInfo returns valid JSON structure
- [ ] Manual test: Statistics returns valid JSON structure
- [ ] Manual test: Factions returns valid JSON structure
- [ ] Manual test: DateTime fields are ISO 8601 strings
- [ ] Manual test: No object instances in output
- [ ] Manual test: Empty results return {} not null
- [ ] Integration test: json_encode succeeds on all outputs

### Testing Example
```php
$reader = $save->getDataReader();
$saveInfo = $reader->getSaveInfo();
$data = $saveInfo->toArrayForAPI();

assert(is_array($data));
assert(isset($data['saveName']));
assert(isset($data['playerName']));
assert(json_encode($data) !== false);
```

---

## WP5: Data Serializers (Extended)

**Duration**: 4-5 hours  
**Dependencies**: WP4 (Core Serializers)  
**Status**: Not Started

### Objectives
- Implement `toArrayForAPI()` for list-returning SaveReader classes
- Handle collections and large datasets
- Ensure consistent array structure

### Files to Modify
- `src/X4/SaveViewer/Data/SaveReader/Blueprints.php`
- `src/X4/SaveViewer/Data/SaveReader/Inventory.php`
- `src/X4/SaveViewer/Data/SaveReader/Log.php`
- `src/X4/SaveViewer/Data/SaveReader/KhaakStationsReader.php`
- `src/X4/SaveViewer/Data/SaveReader/ShipLossesReader.php`

### Deliverables

Each class gets a `toArrayForAPI()` method that returns an array of items.

#### Blueprints
```php
public function toArrayForAPI(): array
{
    $result = [];
    foreach ($this->getBlueprints() as $blueprint) {
        $result[] = [
            'id' => $blueprint->getID(),
            'name' => $blueprint->getName(),
            'owned' => $this->isOwned($blueprint->getID()),
            'category' => $blueprint->getCategory()->getID(),
            'race' => $blueprint->getRace()->getID(),
            'type' => $blueprint->getType()
        ];
    }
    return $result;
}
```

### Implementation Tasks
1. [ ] Implement `Blueprints::toArrayForAPI()`
2. [ ] Implement `Inventory::toArrayForAPI()`
3. [ ] Implement `Log::toArrayForAPI()`
4. [ ] Implement `KhaakStationsReader::toArrayForAPI()`
5. [ ] Implement `ShipLossesReader::toArrayForAPI()`
6. [ ] Ensure empty lists return [] not null
7. [ ] Test with large datasets (performance check)

### Validation Criteria
- [ ] Manual test: Blueprints returns array of objects
- [ ] Manual test: Inventory returns array of objects
- [ ] Manual test: Log returns array of objects
- [ ] Manual test: KhaakStationsReader returns array
- [ ] Manual test: ShipLossesReader returns array
- [ ] Manual test: Empty results return [] not null
- [ ] Manual test: Large datasets complete in reasonable time
- [ ] Integration test: All outputs JSON-encodable

### Testing Example
```php
$reader = $save->getDataReader();
$blueprints = $reader->getBlueprints();
$data = $blueprints->toArrayForAPI();

assert(is_array($data));
assert(count($data) > 0);
assert(isset($data[0]['id']));
assert(isset($data[0]['owned']));
assert(json_encode($data) !== false);
```

---

## WP6: Query Handler (Core Commands)

**Duration**: 4-5 hours  
**Dependencies**: WP1, WP2, WP3, WP4, WP5  
**Status**: Not Started

### Objectives
- Create the main `QueryHandler` class
- Implement CLI argument parsing with league/climate
- Implement core query commands
- Integrate JMESPath filtering
- Integrate caching and pagination

### Files to Create
- `src/X4/SaveViewer/CLI/QueryHandler.php`

### Deliverables

#### QueryHandler Class
```php
class QueryHandler
{
    private SaveManager $manager;
    private QueryCache $cache;
    private QueryValidator $validator;
    private CLImate $cli;
    
    public function __construct(SaveManager $manager)
    
    public static function createFromConfig(): self
    
    public function handle(): void
    
    private function executeCommand(string $command): void
    private function applyCaching(BaseSaveFile $save, array $data): array
    private function applyFilter(array $data, ?string $filter): array
    private function applyPagination(array $data, ?int $limit, ?int $offset): array
    private function buildPaginationMetadata(int $total, ?int $limit, ?int $offset): ?array
}
```

### Core Commands to Implement
- `save-info` - Returns SaveInfo object
- `player` - Returns PlayerInfo object
- `stats` - Returns Statistics object
- `factions` - Returns Factions object
- `blueprints` - Returns Blueprints array
- `inventory` - Returns Inventory array
- `log` - Returns Log entries array
- `khaak-stations` - Returns Khaa'k stations array
- `ship-losses` - Returns Ship losses array

### Implementation Tasks
1. [ ] Create `QueryHandler` class skeleton
2. [ ] Set up league/climate argument parsing
3. [ ] Add command registration for core commands
4. [ ] Implement command routing/dispatch
5. [ ] Implement `save-info` command
6. [ ] Implement `player` command
7. [ ] Implement `stats` command
8. [ ] Implement `factions` command
9. [ ] Implement `blueprints` command
10. [ ] Implement `inventory` command
11. [ ] Implement `log` command
12. [ ] Implement `khaak-stations` command
13. [ ] Implement `ship-losses` command
14. [ ] Integrate `QueryValidator` for input validation
15. [ ] Integrate `QueryCache` for caching
16. [ ] Implement JMESPath filtering with `JmesPath\Env::search()`
17. [ ] Implement pagination logic
18. [ ] Add `--pretty` flag support
19. [ ] Handle `JmesPath\SyntaxErrorException` pass-through

### Validation Criteria
- [ ] Integration test: Each command returns valid JSON
- [ ] Integration test: Success response has correct envelope
- [ ] Integration test: Validation errors return proper format
- [ ] Integration test: JMESPath filters work correctly
- [ ] Integration test: Pagination works with limit/offset
- [ ] Integration test: Cache is used for repeated queries
- [ ] Integration test: --pretty flag formats output
- [ ] Integration test: Invalid JMESPath returns syntax error
- [ ] Manual test: All core commands work end-to-end

### Testing Example
```bash
# Test save-info
bin/query save-info --save=quicksave --pretty

# Test with filter
bin/query blueprints --save=quicksave --filter="[?owned==\`true\`]"

# Test with pagination
bin/query blueprints --save=quicksave --limit=10 --offset=0

# Test with cache
bin/query blueprints --save=quicksave --limit=10 --offset=0 --cache-key=bp1
bin/query blueprints --save=quicksave --limit=10 --offset=10 --cache-key=bp1
```

---

## WP7: Query Handler (Collection Commands)

**Duration**: 2-3 hours  
**Dependencies**: WP6 (Core Query Handler)  
**Status**: Not Started

### Objectives
- Add collection query commands
- Reuse existing Collection `toArray()` methods
- Apply filtering and pagination to collections

### Collection Commands to Implement
- `ships` - Ships collection
- `stations` - Stations collection
- `people` - People collection
- `sectors` - Sectors collection
- `zones` - Zones collection
- `regions` - Regions collection
- `clusters` - Clusters collection
- `celestials` - Celestials collection
- `event-log` - Event log collection

### Implementation Tasks
1. [ ] Add command registration for collection commands
2. [ ] Implement `ships` command
3. [ ] Implement `stations` command
4. [ ] Implement `people` command
5. [ ] Implement `sectors` command
6. [ ] Implement `zones` command
7. [ ] Implement `regions` command
8. [ ] Implement `clusters` command
9. [ ] Implement `celestials` command
10. [ ] Implement `event-log` command
11. [ ] Test filtering on collection data
12. [ ] Test pagination on large collections

### Validation Criteria
- [ ] Integration test: Each collection command returns array
- [ ] Integration test: Collection data matches existing format
- [ ] Integration test: Filters work on collection fields
- [ ] Integration test: Pagination works on collections
- [ ] Integration test: Large collections handle efficiently
- [ ] Manual test: All collection commands work end-to-end

### Testing Example
```bash
# Test ships collection
bin/query ships --save=quicksave --limit=50

# Test with filter
bin/query ships --save=quicksave --filter="[?faction=='argon']"

# Test stations
bin/query stations --save=quicksave --filter="[?owner=='player']"
```

---

## WP8: Entry Points & Scripts

**Duration**: 1-2 hours  
**Dependencies**: WP6, WP7 (Query Handler complete)  
**Status**: Not Started

### Objectives
- Create PHP entry point script
- Create cross-platform wrapper scripts
- Implement global exception handling
- Test on all platforms

### Files to Create
- `bin/php/query.php`
- `bin/query` (bash)
- `bin/query.bat` (Windows)

### Deliverables

#### PHP Entry Point (`bin/php/query.php`)
```php
<?php
declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Bin;

use Mistralys\X4\SaveViewer\CLI\QueryHandler;
use Mistralys\X4\SaveViewer\CLI\JsonResponseBuilder;

require_once __DIR__.'/prepend.php';

try {
    QueryHandler::createFromConfig()->handle();
    exit(0);
} catch (\Throwable $e) {
    echo JsonResponseBuilder::error($e);
    exit(1);
}
```

#### Bash Wrapper (`bin/query`)
```bash
#!/usr/bin/env bash
php "$(dirname -- "$0")/php/query.php" "$@"
```

#### Windows Batch (`bin/query.bat`)
```batch
@echo off
php "%~dp0\php\query.php" %*
```

### Implementation Tasks
1. [ ] Create `bin/php/query.php` with exception handling
2. [ ] Create `bin/query` bash wrapper
3. [ ] Make bash wrapper executable (chmod +x)
4. [ ] Create `bin/query.bat` Windows wrapper
5. [ ] Test on Linux/macOS
6. [ ] Test on Windows CMD
7. [ ] Test on Windows PowerShell
8. [ ] Verify exit codes (0 success, 1 error)
9. [ ] Test exception handling outputs valid JSON

### Validation Criteria
- [ ] Manual test: Linux/macOS execution works
- [ ] Manual test: Windows CMD execution works
- [ ] Manual test: Windows PowerShell execution works
- [ ] Manual test: Exit code 0 on success
- [ ] Manual test: Exit code 1 on error
- [ ] Manual test: Exceptions output valid JSON
- [ ] Manual test: All command-line flags pass through
- [ ] Manual test: Output is valid JSON in all cases

### Testing Example
```bash
# Linux/macOS
./bin/query save-info --save=quicksave
echo $?  # Should be 0

./bin/query save-info --save=nonexistent
echo $?  # Should be 1

# Windows CMD
bin\query.bat save-info --save=quicksave
echo %ERRORLEVEL%  # Should be 0

# Windows PowerShell
.\bin\query.bat save-info --save=quicksave
$LASTEXITCODE  # Should be 0
```

---

## WP9: Documentation & Deprecation

**Duration**: 4-5 hours  
**Dependencies**: WP8 (All implementation complete)  
**Status**: Not Started

### Objectives
- Update README.md with CLI API section
- Update project manifest README
- Add deprecation notices to UI code
- Update VERSION and changelog
- Verify all documentation is accurate

### Files to Modify
- `README.md`
- `docs/agents/project-manifest/README.md`
- `src/X4/SaveViewer/Monitor/X4Server.php`
- `src/X4/SaveViewer/SaveViewer.php`
- `VERSION`
- `changelog.md`

### Documentation Already Created
- ✅ `docs/agents/project-manifest/07-cli-api-reference.md` (already complete)
- ✅ `docs/agents/plans/plan-viewerCliApi.prompt.md` (already complete)

### Implementation Tasks

#### README.md Updates
1. [ ] Add "CLI API" section after "Extract tool command line"
2. [ ] Include quick start examples
3. [ ] Link to 07-cli-api-reference.md
4. [ ] Mark "Web Interface" section as DEPRECATED
5. [ ] Add deprecation warning box

#### Project Manifest Updates
6. [ ] Add document 07 to core documents list
7. [ ] Update document numbering (NDJSON → 6)
8. [ ] Add description and "when to read" for document 07

#### Code Deprecation
9. [ ] Add `@deprecated` to `X4Server` class docblock
10. [ ] Add `@deprecated` to `SaveViewer` class docblock
11. [ ] Include deprecation message and alternative

#### Version Updates
12. [ ] Update VERSION file to 0.1.0
13. [ ] Add v0.1.0 entry to changelog.md
14. [ ] List all new features and deprecations

#### Documentation Verification
15. [ ] Verify all CLI commands documented in 07
16. [ ] Verify all JMESPath examples work
17. [ ] Verify all Rust integration examples are correct
18. [ ] Verify all error codes are documented
19. [ ] Test all example commands from documentation

### Validation Criteria
- [ ] Manual review: README.md CLI section complete
- [ ] Manual review: README.md deprecation notice clear
- [ ] Manual review: Project manifest updated correctly
- [ ] Manual review: Deprecation notices in code
- [ ] Manual review: VERSION and changelog updated
- [ ] Manual test: All documentation examples work
- [ ] Manual test: All command examples are correct
- [ ] Manual test: All filter examples are valid
- [ ] Code review: Deprecation annotations present

### Testing Example
```bash
# Test all examples from documentation
# (Run each command from 07-cli-api-reference.md)

# Verify deprecation notices appear in IDE
# (Open X4Server.php and SaveViewer.php in IDE)

# Verify version
cat VERSION  # Should show 0.1.0

# Verify changelog
grep "v0.1.0" changelog.md  # Should find entry
```

---

## Implementation Order & Dependencies

```
WP1 (Foundation)     WP2 (Cache)
    ↓                    ↓
    WP3 (Validator)      |
         ↓               |
    WP4 (Core Serializers)
              ↓
    WP5 (Extended Serializers)
              ↓
         WP1+WP2+WP3+WP4+WP5
              ↓
    WP6 (Core Query Handler)
              ↓
    WP7 (Collection Commands)
              ↓
    WP8 (Entry Points)
              ↓
    WP9 (Documentation)
```

### Recommended Implementation Sequence

**Phase 1: Foundation (Can be parallel)**
1. WP1: Foundation & Response Builder
2. WP2: Cache Manager

**Phase 2: Validation & Data (WP3 requires WP1)**
3. WP3: Query Validator
4. WP4: Data Serializers (Core)
5. WP5: Data Serializers (Extended)

**Phase 3: Integration (Requires all above)**
6. WP6: Query Handler (Core Commands)
7. WP7: Query Handler (Collection Commands)

**Phase 4: Deployment (Requires all above)**
8. WP8: Entry Points & Scripts
9. WP9: Documentation & Deprecation

---

## Validation Checklist

After completing all work packages:

### Functional Tests
- [ ] All 9 core commands return valid JSON
- [ ] All 9 collection commands return valid JSON
- [ ] `clear-cache` command works
- [ ] JMESPath filtering works on all data types
- [ ] Pagination works with limit/offset
- [ ] Cache reuse works with cache-key
- [ ] Pretty printing works with --pretty flag
- [ ] Error responses have correct format
- [ ] Exit codes are correct (0/1)

### Cross-Platform Tests
- [ ] Linux execution works
- [ ] macOS execution works
- [ ] Windows CMD execution works
- [ ] Windows PowerShell execution works

### Performance Tests
- [ ] Large datasets (1000+ items) handle efficiently
- [ ] Cached pagination is fast
- [ ] Non-cached pagination completes in reasonable time

### Documentation Tests
- [ ] All example commands in docs work
- [ ] All filter examples are valid
- [ ] All Rust integration examples compile (optional)
- [ ] Deprecation notices visible in IDE

### Integration Tests
- [ ] Can query immediately after extraction
- [ ] Cache invalidates on save modification
- [ ] Multiple saves isolated correctly
- [ ] Error recovery suggests correct actions

---

## Rollback Plan

Each work package can be reverted independently if issues arise:

1. **WP1-WP5**: Delete created files, no impact on existing code
2. **WP6-WP7**: Delete QueryHandler.php, no impact on existing functionality
3. **WP8**: Delete entry point scripts, web viewer still works
4. **WP9**: Revert documentation changes, remove deprecation notices

**Critical Point**: WP8 completion - Before this, no user-facing changes exist.

---

## Success Metrics

- ✅ All 19 query commands functional
- ✅ JMESPath filtering works on all data types
- ✅ Pagination with caching implemented
- ✅ Cross-platform support verified
- ✅ Documentation complete and accurate
- ✅ Zero breaking changes to existing code
- ✅ Web UI remains functional (deprecated but working)
- ✅ All validation tests pass

---

## Notes

- Work packages are sized for 2-5 hour sessions
- Each package has clear validation criteria
- Parallel work possible on WP1+WP2
- Can validate incrementally without affecting production
- Clear rollback strategy for each package
- Documentation already created (07-cli-api-reference.md)

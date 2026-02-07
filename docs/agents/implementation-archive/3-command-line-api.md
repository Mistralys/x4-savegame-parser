# Plan: Convert Web Viewer to CLI API with JMESPath Filtering

## Overview

Replace the current web viewer's custom HTTP server with a CLI API that outputs standard JSON. The API will use JMESPath for query filtering (compatible with both PHP `mtdowling/jmespath.php` and Rust `jmespath` crate), implement per-save result caching for efficient pagination, pass through JMESPath exceptions for transparent error handling, and use a standard response envelope with variable payload shapes appropriate to each query type. The `version` field will reflect the project version from the `VERSION` file, cache directories will be hidden with dot-prefix, and a cache clearing command will be provided.

---

## Steps

### 1. Create `QueryHandler` CLI class

**File**: [`src/X4/SaveViewer/CLI/QueryHandler.php`](../../src/X4/SaveViewer/CLI/QueryHandler.php)

Create the main CLI handler using `league/climate` for argument parsing with explicit commands:

**Query Commands**:
- `blueprints` - List blueprints with owned/unowned filtering
- `stats` - Game statistics summary
- `khaak-stations` - List of Khaa'k stations
- `ship-losses` - Ship losses log
- `factions` - Faction information
- `inventory` - Player inventory
- `log` - Event log entries
- `player` - Player information
- `save-info` - Save metadata

**Collection Commands**:
- `ships` - Ships collection
- `stations` - Stations collection
- `people` - People collection
- `sectors` - Sectors collection
- `zones` - Zones collection
- `regions` - Regions collection
- `clusters` - Clusters collection
- `celestials` - Celestials collection
- `event-log` - Event log collection

**Special Commands**:
- `clear-cache` - Remove all cached query results

**Required Flags**:
- `--save=<name|id>` - Target save (required except for `clear-cache`)

**Optional Flags**:
- `--filter=<jmespath>` - JMESPath filter expression
- `--limit=<n>` - Pagination limit (positive integer)
- `--offset=<n>` - Pagination offset (positive integer)
- `--cache-key=<id>` - Cache key for result set reuse
- `--pretty` - Enable pretty-printed JSON output

---

### 2. Implement `JsonResponseBuilder` helper

**File**: [`src/X4/SaveViewer/CLI/JsonResponseBuilder.php`](../../src/X4/SaveViewer/CLI/JsonResponseBuilder.php)

Create a response builder that outputs a **standard response envelope**.

**Success Response Format**:
```json
{
  "success": true,
  "version": "0.0.3",
  "command": "blueprints",
  "timestamp": "2026-01-30T10:30:45+00:00",
  "data": <variable_shape>,
  "pagination": {
    "total": 500,
    "limit": 50,
    "offset": 100,
    "hasMore": true
  }
}
```

**Error Response Format**:
```json
{
  "success": false,
  "version": "0.0.3",
  "command": "blueprints",
  "timestamp": "2026-01-30T10:30:45+00:00",
  "type": "error",
  "message": "Save not found",
  "code": 12125,
  "errors": [
    {
      "message": "Save not found",
      "code": 12125,
      "class": "Mistralys\\X4\\SaveViewer\\SaveViewerException",
      "trace": "...",
      "details": "..."
    }
  ]
}
```

**Data Shape Rules**:
- `version` - Read from `VERSION` file
- `data` - Array for list queries (ships, blueprints, etc.)
- `data` - Object for summary queries (save-info, stats, player)
- Empty lists return `[]`, never `null`
- `pagination` - Only present when `--limit` specified

**JSON Encoding**:
- Always use: `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`
- Add `JSON_PRETTY_PRINT` only when `--pretty` flag present

**Error Handling**:
- Match `JsonOutput::error()` structure from monitor
- Include full exception chain with details for `BaseException`

---

### 3. Create `QueryCache` manager

**File**: [`src/X4/SaveViewer/CLI/QueryCache.php`](../../src/X4/SaveViewer/CLI/QueryCache.php)

Implement per-save result caching for efficient pagination.

**Cache Storage**:
- Location: `<save-storage-folder>/.cache/query-<cache-key>.json`
- Per-save isolation in hidden `.cache` directories
- Automatic directory creation

**Cache Validation**:
- Check save file modification timestamp via `BaseSaveFile::getDateModified()`
- Compare against cache file timestamp
- Automatically invalidate stale caches when save modified

**Public Methods**:
```php
public function store(BaseSaveFile $save, string $cacheKey, array $data): void
public function retrieve(BaseSaveFile $save, string $cacheKey): ?array
public function isValid(BaseSaveFile $save, string $cacheKey): bool
public function clearAll(): void  // For clear-cache command
```

**Cache Lifecycle**:
- Cache remains valid as long as save file unmodified
- Invalidate automatically if save overwritten by user
- `clearAll()` removes all `.cache` directories across all saves

---

### 4. Add data serializers to SaveReader classes

Create `toArrayForAPI(): array` methods in the following classes:

**List Data (returns arrays)**:
- [`Blueprints`](../../src/X4/SaveViewer/Data/SaveReader/Blueprints.php)
- [`Inventory`](../../src/X4/SaveViewer/Data/SaveReader/Inventory.php)
- [`Log`](../../src/X4/SaveViewer/Data/SaveReader/Log.php)
- [`KhaakStationsReader`](../../src/X4/SaveViewer/Data/SaveReader/KhaakStationsReader.php)
- [`ShipLossesReader`](../../src/X4/SaveViewer/Data/SaveReader/ShipLossesReader.php)

**Summary Data (returns objects)**:
- [`Statistics`](../../src/X4/SaveViewer/Data/SaveReader/Statistics.php)
- [`Factions`](../../src/X4/SaveViewer/Data/SaveReader/Factions.php)
- [`PlayerInfo`](../../src/X4/SaveViewer/Data/SaveReader/PlayerInfo.php)
- [`SaveInfo`](../../src/X4/SaveViewer/Data/SaveReader/SaveInfo.php)

**Collection Data**:
- Collections already have `toArray()` methods - use existing implementation

**Serialization Rules**:
- Convert `DateTime` to ISO 8601 format
- Convert `GameTime` to formatted strings
- Expand nested structures into flat arrays/objects
- Empty results return `[]` for lists, `{}` for objects, never `null`
- Ensure all data is JSON-serializable (no objects, resources, etc.)

**JMESPath Filtering**:
- Apply filters using `JmesPath\Env::search($expression, $data)`
- **Pass through `JmesPath\SyntaxErrorException` directly** - do not catch
- Apply filter before pagination
- Cache filtered results when `--cache-key` provided

---

### 5. Implement `QueryValidator`

**File**: [`src/X4/SaveViewer/CLI/QueryValidator.php`](../../src/X4/SaveViewer/CLI/QueryValidator.php)

Create validation layer for query parameters.

**Save Validation**:
- Check existence: `SaveManager::nameExists()` and `SaveManager::idExists()`
- Verify extraction: `BaseSaveFile::isUnpacked()`
- Return actionable errors with suggestions

**Pagination Validation**:
- `--limit` must be positive integer
- `--offset` must be positive integer
- Return validation errors in standard format

**Error Format with Actions**:
```json
{
  "success": false,
  "type": "error",
  "message": "Save 'quicksave' is not extracted",
  "code": 12345,
  "actions": [
    "Run: bin/extract -e quicksave"
  ]
}
```

**JMESPath Validation**:
- **Do NOT validate JMESPath syntax**
- Let `JmesPath\SyntaxErrorException` pass through naturally
- Exception will be caught by entry point and formatted via `JsonResponseBuilder::error()`

**Error Codes**:
- Use existing constants from `SaveManager` (e.g., `ERROR_CANNOT_FIND_BY_NAME`)
- Document all error codes in API reference

---

### 6. Create entry point scripts

Create three entry point files for cross-platform support.

**PHP Entry Point**: [`bin/php/query.php`](../../bin/php/query.php)
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

**Bash Wrapper**: [`bin/query`](../../bin/query)
```bash
#!/usr/bin/env bash

php "$(dirname -- "$0")/php/query.php" "$@"
```

**Windows Wrapper**: [`bin/query.bat`](../../bin/query.bat)
```batch
@echo off

php "%~dp0\php\query.php" %*
```

**Exit Codes**:
- `0` - Success (valid JSON response with `"success": true`)
- `1` - Error (valid JSON response with `"success": false`)

**Error Handling**:
- Wrap all execution in try-catch
- Output all exceptions as JSON via `JsonResponseBuilder::error()`
- Include `JmesPath\SyntaxErrorException` in error output

---

### 7. Create comprehensive CLI API documentation

**File**: [`docs/agents/project-manifest/07-cli-api-reference.md`](../project-manifest/07-cli-api-reference.md)

Create complete API reference documentation for the launcher agent.

**Documentation Sections**:

1. **Overview** - Purpose and usage of CLI API
2. **Standard Response Envelope** - Success and error formats
3. **Query Commands Reference** - All commands with schemas
4. **JMESPath Feature Subset** - Supported syntax and functions
5. **Filtering Examples** - Common patterns for each data type
6. **Pagination Workflow** - Using limit, offset, and cache-key
7. **Cache Management** - Cache lifecycle and clear-cache command
8. **Error Reference** - All error codes with descriptions
9. **Integration Guide** - How to consume from Rust/Tauri

**JMESPath Features to Document**:
- **Filters**: `[?field=='value']`, `[?field>10]`, `[?field!=null]`
- **Projections**: `[*].{name: name, id: id}`, `{count: length(@)}`
- **Pipes**: `[] | [0]`, `[*].name | [0]`
- **Functions**: `length()`, `sort_by()`, `contains()`, `to_number()`, `to_string()`, `keys()`, `values()`
- **Logical operators**: `&&`, `||`, `!`

**Common Filter Examples**:
```bash
# Blueprints - owned only
--filter="[?owned==true]"

# Ships - by faction
--filter="[?faction=='argon']"

# Log entries - by category
--filter="[?category=='combat']"

# Blueprints - specific category
--filter="[?category=='ships']"

# Complex - owned ships in specific category
--filter="[?owned==true && category=='ships'].{name: name, id: id}"
```

**Pagination Example**:
```bash
# First page
bin/query ships --save=quicksave --limit=50 --offset=0 --cache-key=ships-all

# Second page (uses cached filtered result)
bin/query ships --save=quicksave --limit=50 --offset=50 --cache-key=ships-all
```

**Version Field**:
- Document that `version` field matches project version from `VERSION` file
- Explain when API version would change (breaking changes only)

---

### 8. Update project documentation and deprecate UI

Update multiple files to reflect the new CLI API and deprecate the web viewer.

**README.md Update**: [`README.md`](../../README.md)

Add new "CLI API" section after "Extract tool command line":
```markdown
## CLI API

The CLI API provides programmatic access to all savegame data via JSON output.
This is the recommended way to integrate with external applications.

### Quick Start

```bash
# Query save information
./bin/query save-info --save=quicksave --pretty

# List all ships
./bin/query ships --save=quicksave

# Filter blueprints (owned only)
./bin/query blueprints --save=quicksave --filter="[?owned==true]"

# Paginate through ships
./bin/query ships --save=quicksave --limit=50 --offset=0 --cache-key=ships-1

# Clear cache
./bin/query clear-cache
```

See [CLI API Reference](docs/agents/project-manifest/07-cli-api-reference.md) for complete documentation.

## Web Interface (DEPRECATED)

> **⚠️ DEPRECATED**: The web interface will be removed in a future version.
> Please use the CLI API instead.

...existing UI documentation...
```

**Project Manifest Update**: [`docs/agents/project-manifest/README.md`](../project-manifest/README.md)

Add document 07 to "Core Documents" section:
```markdown
### 7. [CLI API Reference](./07-cli-api-reference.md)
**Purpose**: Complete reference for the CLI query API and JMESPath filtering.

**Contents**:
- Standard response envelope specification
- All query commands with input/output schemas
- JMESPath feature subset and filtering examples
- Pagination and caching workflows
- Error codes and handling
- Integration guide for external applications

**When to read**: When building applications that query savegame data or implementing launchers.
```

Update "Specialized Documents" section to move NDJSON to document 6:
```markdown
## Specialized Documents

### 6. [NDJSON Interface](./ndjson-interface.md)
...existing content...
```

**Code Deprecation Annotations**:

Add to [`X4Server`](../../src/X4/SaveViewer/Monitor/X4Server.php):
```php
/**
 * @deprecated Use CLI API via bin/query instead. This server will be removed in a future version.
 */
class X4Server extends BaseMonitor
```

Add to [`SaveViewer`](../../src/X4/SaveViewer/SaveViewer.php):
```php
/**
 * @deprecated Use CLI API via bin/query instead. This UI will be removed in a future version.
 */
class SaveViewer extends X4Application
```

**Version and Changelog Updates**:

Update [`VERSION`](../../VERSION):
```
0.1.0
```

Update [`changelog.md`](../../changelog.md):
```markdown
### v0.1.0 - CLI API Release
- **NEW**: CLI API with JMESPath filtering
- **NEW**: Query caching for pagination
- **NEW**: Standard JSON response envelope
- **NEW**: Comprehensive API documentation
- **DEPRECATED**: Web UI (use CLI API instead)
- **DEPRECATED**: UI Server (use CLI API instead)
```

---

## Further Considerations

### 1. Cache storage location strategy
**Decision**: Per-save isolation in hidden `.cache` directories  
**Rationale**: Keeps cache data organized, prevents cross-save contamination, easy cleanup per save

### 2. JMESPath expression handling
**Decision**: Pass through `SyntaxErrorException` directly  
**Rationale**: Launcher gets raw syntax errors for debugging, no need for custom error messages

### 3. Response envelope consistency
**Decision**: Standard envelope with variable `data` shape  
**Rationale**: Consistent structure for error handling and metadata, flexible payload for different query types

### 4. Version numbering
**Decision**: Use project version from `VERSION` file  
**Rationale**: Simplifies version management, sufficient for tracking breaking changes

### 5. Command naming
**Decision**: Preserve singular/plural naturally  
**Rationale**: Reflects data semantics (singular for summaries, plural for lists)

### 6. Cache clearing mechanism
**Decision**: Dedicated `clear-cache` command  
**Rationale**: Explicit control for maintenance, useful for development and debugging

---

## Implementation Notes

### Dependencies
- JMESPath: Already added to `composer.json` as `mtdowling/jmespath.php`
- league/climate: Already present for CLI argument parsing
- No new dependencies required

### Testing Strategy
1. Unit tests for `JsonResponseBuilder` (envelope format)
2. Unit tests for `QueryCache` (validation, expiration)
3. Integration tests for each query command
4. JMESPath filter tests with various expressions
5. Pagination tests with cache keys

### Performance Considerations
- Cache large filtered result sets (ships, stations)
- Consider memory limits for large collections
- JMESPath filtering on full dataset before pagination
- Cache validation via timestamp comparison (fast)

### Security Considerations
- Input validation on all flags
- Safe file operations for cache storage
- No shell command injection risks
- JSON encoding prevents XSS

---

## Success Criteria

✅ All SaveReader data accessible via CLI  
✅ JMESPath filtering works on all data types  
✅ Pagination with caching for large datasets  
✅ Standard JSON response envelope  
✅ Error handling matches monitor format  
✅ Per-save cache isolation  
✅ Cache invalidation on save modification  
✅ Comprehensive API documentation  
✅ Web UI marked as deprecated  
✅ Cross-platform entry point scripts  

---

## Timeline Estimate

- **Step 1** (QueryHandler): 4-6 hours
- **Step 2** (JsonResponseBuilder): 2-3 hours
- **Step 3** (QueryCache): 3-4 hours
- **Step 4** (Data Serializers): 6-8 hours
- **Step 5** (QueryValidator): 2-3 hours
- **Step 6** (Entry Points): 1-2 hours
- **Step 7** (Documentation): 4-5 hours
- **Step 8** (Project Updates): 2-3 hours

**Total**: 24-34 hours

---

## Notes

- This plan prioritizes the launcher application as the primary consumer
- No human-friendly shortcuts or aliases
- Strictly explicit command syntax
- Compatible with Rust `jmespath` crate for launcher implementation
- Cache strategy optimized for immutable save files

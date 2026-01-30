# CLI API Reference

This document provides a complete technical reference for the X4 Savegame Parser CLI API. It is designed for developers building applications (particularly the Rust/Tauri launcher) that need to query and display savegame data programmatically.

---

## Table of Contents

1. [Overview](#overview)
2. [Invocation](#invocation)
3. [Standard Response Envelope](#standard-response-envelope)
4. [Query Commands](#query-commands)
5. [JMESPath Query Language](#jmespath-query-language)
6. [Pagination](#pagination)
7. [Caching](#caching)
8. [Error Handling](#error-handling)
9. [Data Schemas](#data-schemas)
10. [Integration Guide](#integration-guide)

---

## Overview

The CLI API provides programmatic access to all savegame data through a command-line interface that outputs structured JSON. It replaces the legacy web viewer with a more maintainable and integration-friendly solution.

**Key Features**:
- Standard JSON response envelope for consistent parsing
- JMESPath query language for filtering and transforming data
- Result caching for efficient pagination
- Cross-platform support (Windows, Linux, macOS)
- Designed for machine consumption (no human-friendly shortcuts)

**Primary Use Case**: The Rust/Tauri launcher application that monitors saves and displays extracted data.

---

## Invocation

### Basic Syntax

```bash
bin/query <command> --save=<name|id> [options]
```

### Platform-Specific Entry Points

**Linux/macOS**:
```bash
./bin/query ships --save=quicksave
```

**Windows (CMD)**:
```batch
bin\query.bat ships --save=quicksave
```

**Windows (PowerShell)**:
```powershell
.\bin\query.bat ships --save=quicksave
```

### Global Flags

All commands (except `clear-cache`) support these flags:

| Flag | Type | Required | Description |
|------|------|----------|-------------|
| `--save` | string | Yes* | Save name (e.g., "quicksave") or save ID |
| `--filter` | string | No | JMESPath expression for filtering data |
| `--limit` | integer | No | Maximum number of results to return |
| `--offset` | integer | No | Number of results to skip (pagination) |
| `--cache-key` | string | No | Cache identifier for reusing filtered results |
| `--pretty` | flag | No | Enable pretty-printed JSON output |

*Not required for `list-saves` and `clear-cache` commands

### Exit Codes

- **0**: Success - Valid JSON response with `"success": true`
- **1**: Error - Valid JSON response with `"success": false`

---

## Standard Response Envelope

All responses use a consistent envelope structure for predictable parsing.

### Success Response

```json
{
  "success": true,
  "version": "0.1.0",
  "command": "ships",
  "timestamp": "2026-01-30T14:23:45+00:00",
  "data": <variable_shape>,
  "pagination": {
    "total": 1523,
    "limit": 50,
    "offset": 0,
    "hasMore": true
  }
}
```

### Error Response

```json
{
  "success": false,
  "version": "0.1.0",
  "command": "ships",
  "timestamp": "2026-01-30T14:23:45+00:00",
  "type": "error",
  "message": "Save 'quicksave' is not extracted",
  "code": 12126,
  "errors": [
    {
      "message": "Save 'quicksave' is not extracted",
      "code": 12126,
      "class": "Mistralys\\X4\\SaveViewer\\SaveViewerException",
      "trace": "...",
      "details": "Additional context..."
    }
  ],
  "actions": [
    "Run: bin/extract -e quicksave"
  ]
}
```

### Envelope Fields

| Field | Type | Always Present | Description |
|-------|------|----------------|-------------|
| `success` | boolean | Yes | `true` for success, `false` for errors |
| `version` | string | Yes | API version (matches project VERSION file) |
| `command` | string | Yes | The command that was executed |
| `timestamp` | string | Yes | ISO 8601 timestamp in UTC |
| `data` | array/object | Success only | Query results (shape varies by command) |
| `pagination` | object | When `--limit` used | Pagination metadata |
| `type` | string | Error only | Always `"error"` for failed requests |
| `message` | string | Error only | Human-readable error message |
| `code` | integer | Error only | Error code for programmatic handling |
| `errors` | array | Error only | Full exception chain with stack traces |
| `actions` | array | Error only (optional) | Suggested actions to resolve the error |

### Data Shape Rules

The `data` field shape depends on the query type:

- **List queries** (ships, blueprints, stations, etc.): Returns **array** `[]`
- **Summary queries** (save-info, stats, player): Returns **object** `{}`
- **Empty results**: Lists return `[]`, objects return `{}`
- **Never returns**: `null` for data field

---

## Query Commands

### List Commands (Returns Arrays)

#### `blueprints`
Returns all blueprints with ownership information.

```bash
bin/query blueprints --save=quicksave
```

**Data Fields**: `id`, `name`, `owned`, `category`, `race`, `type`

**Common Filters**:
```bash
# Owned blueprints only
--filter="[?owned==\`true\`]"

# By category
--filter="[?category=='ships']"

# Unowned ship blueprints
--filter="[?owned==\`false\` && category=='ships']"
```

---

#### `ships`
Returns all ships in the game universe.

```bash
bin/query ships --save=quicksave
```

**Data Fields**: `componentId`, `name`, `faction`, `sector`, `owner`, `class`, `purpose`

**Common Filters**:
```bash
# Ships owned by player
--filter="[?owner=='player']"

# Ships in specific sector
--filter="[?sector=='Argon Prime']"

# By faction
--filter="[?faction=='argon']"

# Combat ships only
--filter="[?purpose=='combat']"
```

---

#### `stations`
Returns all stations in the game universe.

```bash
bin/query stations --save=quicksave
```

**Data Fields**: `componentId`, `name`, `faction`, `sector`, `owner`, `type`, `products`

**Common Filters**:
```bash
# Player stations
--filter="[?owner=='player']"

# By type
--filter="[?type=='production']"

# In specific sector
--filter="[?sector=='Grand Exchange']"
```

---

#### `people`
Returns all NPCs and crew members.

```bash
bin/query people --save=quicksave
```

**Data Fields**: `componentId`, `name`, `race`, `role`, `location`, `employer`

**Common Filters**:
```bash
# Player crew
--filter="[?employer=='player']"

# By race
--filter="[?race=='argon']"

# By role
--filter="[?role=='pilot']"
```

---

#### `khaak-stations`
Returns detected Khaa'k stations.

```bash
bin/query khaak-stations --save=quicksave
```

**Data Fields**: `componentId`, `name`, `sector`, `zone`, `discoveredTime`

---

#### `ship-losses`
Returns log of destroyed ships.

```bash
bin/query ship-losses --save=quicksave
```

**Data Fields**: `time`, `timeFormatted`, `shipName`, `location`, `commander`, `destroyedBy`, `category`

**Common Filters**:
```bash
# Recent losses
--filter="[?time > \`1000000\`]"

# By category
--filter="[?category=='combat']"

# Losses in specific location
--filter="[?contains(location, 'Argon Prime')]"
```

---

#### `log`
Returns game event log entries.

```bash
bin/query log --save=quicksave
```

**Data Fields**: `time`, `timeFormatted`, `category`, `title`, `text`, `money`

**Common Filters**:
```bash
# By category
--filter="[?category=='mission']"

# Recent events
--filter="[?time > \`5000000\`]"

# Money-related events
--filter="[?money != \`0\`]"
```

---

#### `inventory`
Returns player inventory items.

```bash
bin/query inventory --save=quicksave
```

**Data Fields**: `wareId`, `name`, `amount`, `type`, `averagePrice`

**Common Filters**:
```bash
# By type
--filter="[?type=='resource']"

# High value items
--filter="[?averagePrice > \`10000\`]"
```

---

#### Collection Commands

These return raw collection data from the parser:

- `sectors` - All sectors
- `zones` - All zones  
- `regions` - All regions
- `clusters` - All clusters
- `celestials` - All celestial bodies
- `event-log` - Raw event log collection

**Example**:
```bash
bin/query sectors --save=quicksave
```

---

### Summary Commands (Returns Objects)

#### `save-info`
Returns metadata about the save file.

```bash
bin/query save-info --save=quicksave
```

**Data Fields**:
```json
{
  "saveName": "My Game",
  "playerName": "Commander Shepard",
  "money": 15234567,
  "moneyFormatted": "152,345.67 Cr",
  "saveDate": "2026-01-30T14:23:45+00:00",
  "gameStartTime": 1234567.89,
  "location": "Argon Prime",
  "extractionDuration": 135.42,
  "extractionDurationFormatted": "2m 15s"
}
```

**Field Notes**:
- `extractionDuration`: Time in seconds (float) that the extraction process took. Returns `null` for saves extracted before this feature was added.
- `extractionDurationFormatted`: Human-readable duration (e.g., "2m 15s", "1h 23m 45s"). Returns `null` for legacy saves.

---

#### `player`
Returns player information.

```bash
bin/query player --save=quicksave
```

**Data Fields**:
```json
{
  "name": "Commander Shepard",
  "code": "ABC123",
  "blueprints": ["ship_arg_s_fighter_01_a", "..."],
  "wares": {...}
}
```

---

#### `stats`
Returns game statistics.

```bash
bin/query stats --save=quicksave
```

**Data Fields**: Various stat IDs and values (shape varies by game state)

---

#### `factions`
Returns faction information.

```bash
bin/query factions --save=quicksave
```

**Data Fields**: Faction relationships and standings

---

### Special Commands

#### `list-saves`
Returns a list of all available saves (both main and archived).

```bash
bin/query list-saves
```

**No `--save` flag required**. Returns an object with two arrays:
- `main`: Saves in the game folder
- `archived`: Extracted saves in storage

**Data Fields**:
- `id`: Save identifier
- `name`: Save name
- `dateModified`: Last modification date (ISO 8601)
- `isUnpacked`: Whether the save has been extracted
- `hasBackup`: Whether a backup exists
- `storageFolder`: Folder name in storage (archived saves only)

**Example Response**:
```json
{
  "success": true,
  "version": "0.1.0",
  "command": "list-saves",
  "timestamp": "2026-01-30T15:30:00+00:00",
  "data": {
    "main": [
      {
        "id": "quicksave",
        "name": "quicksave",
        "dateModified": "2026-01-30T14:23:45+00:00",
        "isUnpacked": true,
        "hasBackup": true
      }
    ],
    "archived": [
      {
        "id": "unpack-20230524120000-quicksave",
        "name": "quicksave",
        "dateModified": "2023-05-24T12:00:00+00:00",
        "isUnpacked": true,
        "hasBackup": true,
        "storageFolder": "unpack-20230524120000-quicksave"
      }
    ]
  }
}
```

---

#### `queue-extraction`
Queue saves for automatic extraction by the monitor.

```bash
# Queue single save
bin/query queue-extraction --save=autosave_01

# Queue multiple saves
bin/query queue-extraction --saves="autosave_01 autosave_02 save_020"

# View queue
bin/query queue-extraction --list

# Clear queue
bin/query queue-extraction --clear
```

**No `--save` flag required for `--list` and `--clear` operations**.

**Flags**:
- `--save`: Single save to queue (name or ID)
- `--saves`: Space-separated list of saves to queue (names or IDs)
- `--list`: Display current queue contents
- `--clear`: Clear all queued saves

**Important Notes**:
- Accepts both save **names** (e.g., `autosave_01`) and **IDs** (e.g., `53b0c253` from `list-saves`)
- Can queue **unextracted** saves - this is the primary use case
- Non-existent saves are skipped with a warning in the response
- Duplicate saves are automatically prevented

**How It Works**:
1. Saves are added to a persistent queue file (`extraction-queue.json` in storage folder)
2. Monitor checks queue before processing the most recent save
3. Queued saves are extracted in order (FIFO - First In, First Out)
4. Already-extracted saves are skipped automatically
5. Non-existent saves are removed from queue automatically

**Queue Response** (all saves valid):
```json
{
  "success": true,
  "command": "queue-extraction",
  "data": {
    "queued": ["autosave_01", "autosave_02"],
    "count": 2,
    "message": "Queued 2 saves for extraction",
    "totalInQueue": 5
  }
}
```

**Queue Response** (with skipped saves):
```json
{
  "success": true,
  "command": "queue-extraction",
  "data": {
    "queued": ["autosave_01", "autosave_02"],
    "count": 2,
    "message": "Queued 2 saves for extraction",
    "totalInQueue": 5,
    "skipped": ["nonexistent"],
    "warning": "1 save not found and skipped"
  }
}
```

**List Response**:
```json
{
  "success": true,
  "command": "queue-extraction",
  "data": {
    "queue": ["autosave_01", "autosave_02", "save_020"],
    "count": 3
  }
}
```

**Use Case Example**:
```bash
# 1. List available saves (get IDs)
bin/query list-saves

# Output shows:
# {
#   "data": {
#     "main": [
#       {"id": "53b0c253", "name": "autosave_02", "isUnpacked": false},
#       {"id": "3d62bff6", "name": "autosave_01", "isUnpacked": false}
#     ]
#   }
# }

# 2. Queue saves using IDs or names (both work!)
bin/query queue-extraction --saves="53b0c253 3d62bff6"
# OR using names:
bin/query queue-extraction --saves="autosave_02 autosave_01"

# 3. Start the monitor (or it's already running)
bin/run-monitor

# Monitor will process:
# > Processing queued save [53b0c253]...
# > Processing queued save [3d62bff6]...
# > Queue empty, monitoring for new saves.
```

---

#### `clear-cache`
Removes all cached query results.

```bash
bin/query clear-cache
```

**No `--save` flag required**. Returns success response with empty data.

---

## JMESPath Query Language

The CLI API uses [JMESPath](https://jmespath.org/) for filtering and transforming data. This section documents the supported subset.

### Basic Filters

Filter arrays using `[?expression]` syntax:

```bash
# Equality
--filter="[?faction=='argon']"

# Inequality
--filter="[?faction!='xenon']"

# Comparison
--filter="[?money > \`10000\`]"
--filter="[?time >= \`5000000\`]"

# Null checks
--filter="[?owner != null]"
```

**Note**: Use backticks for literals: `` \`true\` ``, `` \`false\` ``, `` \`null\` ``, `` \`123\` ``

### Logical Operators

Combine conditions with `&&` (AND) and `||` (OR):

```bash
# AND
--filter="[?owned==\`true\` && category=='ships']"

# OR
--filter="[?faction=='argon' || faction=='antigone']"

# NOT
--filter="[?!(faction=='xenon')]"

# Complex
--filter="[?(faction=='argon' || faction=='teladi') && owner=='player']"
```

### Projections

Transform result objects using projections:

```bash
# Select specific fields
--filter="[*].{name: name, id: componentId}"

# Rename fields
--filter="[*].{shipName: name, location: sector}"

# Nested access
--filter="[*].{name: name, sectorName: location.sector}"
```

**Result**:
```json
[
  {"name": "Phoenix", "id": "ship_001"},
  {"name": "Odyssey", "id": "ship_002"}
]
```

### Pipes

Chain operations using `|`:

```bash
# Filter then project
--filter="[?faction=='argon'] | [*].{name: name}"

# Get first result
--filter="[?owner=='player'] | [0]"

# Get last result
--filter="[?owned==\`true\`] | [-1]"
```

### Functions

#### `length()`
Count array elements:

```bash
# Count owned blueprints
--filter="length([?owned==\`true\`])"
```

#### `sort_by()`
Sort by field:

```bash
# Sort by name ascending
--filter="sort_by([*], &name)"

# Sort by time descending (use reverse)
--filter="reverse(sort_by([*], &time))"
```

#### `contains()`
Check string/array membership:

```bash
# String contains
--filter="[?contains(location, 'Argon')]"

# Array contains
--filter="[?contains(tags, 'important')]"
```

#### `to_number()` / `to_string()`
Type conversions:

```bash
--filter="[?to_number(money) > \`10000\`]"
--filter="[*].{id: to_string(componentId)}"
```

#### `keys()` / `values()`
Extract object keys or values:

```bash
--filter="keys(@)"
--filter="values(@)"
```

### Complex Examples

#### Paginate filtered results
```bash
# Get first 50 Argon ships
--filter="[?faction=='argon']" --limit=50 --offset=0

# Next 50
--filter="[?faction=='argon']" --limit=50 --offset=50
```

#### Multi-condition filter with projection
```bash
--filter="[?faction=='argon' && owner=='player'].{name: name, location: sector, class: class}"
```

#### Count by category
```bash
--filter="{ships: length([?category=='ships']), stations: length([?category=='stations'])}"
```

#### Top 10 most expensive items
```bash
--filter="reverse(sort_by([*], &averagePrice))[:10]"
```

---

## Pagination

Pagination is controlled by `--limit` and `--offset` flags, with optional caching for performance.

### Basic Pagination

```bash
# First page (50 items)
bin/query ships --save=quicksave --limit=50 --offset=0

# Second page
bin/query ships --save=quicksave --limit=50 --offset=50

# Third page
bin/query ships --save=quicksave --limit=50 --offset=100
```

### Pagination Metadata

When `--limit` is specified, the response includes pagination info:

```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "total": 1523,
    "limit": 50,
    "offset": 0,
    "hasMore": true
  }
}
```

**Fields**:
- `total`: Total number of items in result set
- `limit`: Requested limit (items per page)
- `offset`: Current offset (starting position)
- `hasMore`: `true` if more items available after current page

### Performance Note

Without caching, each pagination request re-applies filters to the full dataset. Use `--cache-key` for better performance.

---

## Caching

Result caching enables efficient pagination by storing filtered results and reusing them across multiple requests.

### Cache Key Strategy

```bash
# First request - filter and cache results
bin/query ships --save=quicksave \
  --filter="[?faction=='argon']" \
  --limit=50 --offset=0 \
  --cache-key="argon-ships-1"

# Subsequent requests - reuse cached filtered results
bin/query ships --save=quicksave \
  --limit=50 --offset=50 \
  --cache-key="argon-ships-1"

bin/query ships --save=quicksave \
  --limit=50 --offset=100 \
  --cache-key="argon-ships-1"
```

### Cache Behavior

**Storage Location**: `<save-storage-folder>/.cache/query-<cache-key>.json`

**Validation**: Cache is automatically invalidated if:
- Save file is modified (timestamp check)
- Save file is overwritten with newer version
- User runs `clear-cache` command

**Isolation**: Each save has its own `.cache` directory - no cross-save contamination

**Lifecycle**:
1. First request with `--cache-key`: Filter applied, results cached
2. Subsequent requests with same key: Cached results used, pagination applied
3. Save modified: Cache automatically invalidated
4. New request: Re-filter and create new cache

### Cache Management

```bash
# Clear all caches globally
bin/query clear-cache
```

**Recommended Practice**: Use unique cache keys per filter expression:
```bash
--cache-key="ships-argon-player"
--cache-key="blueprints-owned-ships"
--cache-key="log-combat-recent"
```

---

## Error Handling

All errors return JSON with `"success": false` and exit code `1`.

### Common Error Codes

| Code | Constant | Description | Resolution |
|------|----------|-------------|------------|
| 12125 | `ERROR_CANNOT_FIND_BY_NAME` | Save not found by name | Verify save name, check available saves |
| 12126 | `ERROR_CANNOT_FIND_BY_ID` | Save not found by ID | Verify save ID exists |
| 136002 | `ERROR_SAVEGAME_NOT_FOUND` | Save file doesn't exist | Check saves folder path |
| 137401 | `ERROR_SAVEGAME_MUST_BE_UNZIPPED` | Save not extracted | Run `bin/extract -e <name>` |

### Error Response Structure

```json
{
  "success": false,
  "version": "0.1.0",
  "command": "ships",
  "timestamp": "2026-01-30T14:23:45+00:00",
  "type": "error",
  "message": "Save 'quicksave' is not extracted",
  "code": 12126,
  "errors": [
    {
      "message": "Save 'quicksave' is not extracted",
      "code": 12126,
      "class": "Mistralys\\X4\\SaveViewer\\SaveViewerException",
      "trace": "...",
      "details": "Save file exists but JSON data not generated"
    }
  ],
  "actions": [
    "Run: bin/extract -e quicksave"
  ]
}
```

### JMESPath Syntax Errors

Invalid JMESPath expressions are passed through directly:

```json
{
  "success": false,
  "type": "error",
  "message": "Syntax error at character 5",
  "code": 0,
  "errors": [
    {
      "message": "Syntax error at character 5",
      "class": "JmesPath\\SyntaxErrorException",
      "trace": "..."
    }
  ]
}
```

### Handling in Rust

```rust
use serde::{Deserialize, Serialize};

#[derive(Debug, Deserialize)]
struct Response {
    success: bool,
    version: String,
    command: String,
    timestamp: String,
    
    // Success fields
    data: Option<serde_json::Value>,
    pagination: Option<Pagination>,
    
    // Error fields
    #[serde(rename = "type")]
    error_type: Option<String>,
    message: Option<String>,
    code: Option<i32>,
    errors: Option<Vec<ErrorDetail>>,
    actions: Option<Vec<String>>,
}

#[derive(Debug, Deserialize)]
struct Pagination {
    total: usize,
    limit: usize,
    offset: usize,
    #[serde(rename = "hasMore")]
    has_more: bool,
}

#[derive(Debug, Deserialize)]
struct ErrorDetail {
    message: String,
    code: i32,
    class: String,
    trace: String,
    details: Option<String>,
}

fn handle_response(json: &str) -> Result<serde_json::Value, String> {
    let response: Response = serde_json::from_str(json)?;
    
    if response.success {
        Ok(response.data.unwrap())
    } else {
        Err(response.message.unwrap_or_else(|| "Unknown error".to_string()))
    }
}
```

---

## Data Schemas

### Ships Collection

```typescript
interface Ship {
  componentId: string;
  connectionId: string;
  name: string;
  faction: string;
  owner: string;
  sector: string;
  zone: string;
  class: string;        // "ship_arg_m_fighter_01_a"
  purpose: string;      // "combat", "trade", "mining"
  hull: number;
  shields: number;
  cargo: {
    capacity: number;
    used: number;
  };
}
```

### Stations Collection

```typescript
interface Station {
  componentId: string;
  connectionId: string;
  name: string;
  faction: string;
  owner: string;
  sector: string;
  zone: string;
  type: string;         // "production", "habitation", "defense"
  products: string[];   // Ware IDs produced
  modules: number;
}
```

### Blueprints

```typescript
interface Blueprint {
  id: string;           // "ship_arg_s_fighter_01_a"
  name: string;         // "Argon Nova"
  owned: boolean;
  category: string;     // "ships", "stations", "equipment"
  race: string;         // "argon", "teladi", etc.
  type: string;         // "fighter", "destroyer", etc.
  description: string;
}
```

### Save Info

```typescript
interface SaveInfo {
  saveName: string;
  playerName: string;
  money: number;
  moneyFormatted: string;
  saveDate: string;     // ISO 8601
  gameStartTime: number;
  location: string;
}
```

### Ship Losses

```typescript
interface ShipLoss {
  time: number;         // Game time
  timeFormatted: string;
  shipName: string;
  location: string;
  commander: string;
  destroyedBy: string;
  category: string;     // "combat", "accident"
}
```

### Log Entry

```typescript
interface LogEntry {
  time: number;
  timeFormatted: string;
  category: string;     // "mission", "trade", "combat", etc.
  title: string;
  text: string;
  money: number;
}
```

---

## Integration Guide

### Rust/Tauri Implementation

#### 1. Execute CLI Command

```rust
use std::process::Command;

fn query_cli(command: &str, save: &str, filter: Option<&str>) -> Result<String, String> {
    let mut cmd = Command::new("php");
    cmd.arg("bin/php/query.php");
    cmd.arg(command);
    cmd.arg("--save");
    cmd.arg(save);
    
    if let Some(f) = filter {
        cmd.arg("--filter");
        cmd.arg(f);
    }
    
    let output = cmd.output()
        .map_err(|e| format!("Failed to execute: {}", e))?;
    
    if output.status.success() {
        Ok(String::from_utf8_lossy(&output.stdout).to_string())
    } else {
        // Still parse JSON on error (exit code 1)
        Ok(String::from_utf8_lossy(&output.stdout).to_string())
    }
}
```

#### 2. Parse Response

```rust
use serde_json::Value;

fn parse_query_response(json: &str) -> Result<Value, String> {
    let response: Value = serde_json::from_str(json)
        .map_err(|e| format!("JSON parse error: {}", e))?;
    
    let success = response["success"].as_bool()
        .ok_or("Missing 'success' field")?;
    
    if success {
        response["data"].clone()
            .ok_or("Missing 'data' field")
    } else {
        let message = response["message"].as_str()
            .unwrap_or("Unknown error");
        Err(message.to_string())
    }
}
```

#### 3. Type-Safe Queries

```rust
#[derive(Debug, Deserialize)]
struct Ship {
    #[serde(rename = "componentId")]
    component_id: String,
    name: String,
    faction: String,
    owner: String,
    sector: String,
}

fn get_player_ships(save: &str) -> Result<Vec<Ship>, String> {
    let json = query_cli("ships", save, Some("[?owner=='player']"))?;
    let data = parse_query_response(&json)?;
    
    serde_json::from_value(data)
        .map_err(|e| format!("Deserialization error: {}", e))
}
```

#### 4. Pagination Helper

```rust
struct PaginatedQuery {
    save: String,
    command: String,
    filter: Option<String>,
    cache_key: String,
    page_size: usize,
}

impl PaginatedQuery {
    fn fetch_page(&self, page: usize) -> Result<(Vec<Value>, bool), String> {
        let offset = page * self.page_size;
        
        let mut cmd = Command::new("php");
        cmd.arg("bin/php/query.php");
        cmd.arg(&self.command);
        cmd.arg("--save").arg(&self.save);
        cmd.arg("--limit").arg(self.page_size.to_string());
        cmd.arg("--offset").arg(offset.to_string());
        cmd.arg("--cache-key").arg(&self.cache_key);
        
        if let Some(f) = &self.filter {
            cmd.arg("--filter").arg(f);
        }
        
        let output = cmd.output()?;
        let json = String::from_utf8_lossy(&output.stdout);
        let response: Value = serde_json::from_str(&json)?;
        
        let data = response["data"].as_array()
            .ok_or("Expected array data")?;
        let has_more = response["pagination"]["hasMore"].as_bool()
            .unwrap_or(false);
        
        Ok((data.clone(), has_more))
    }
}
```

#### 5. JMESPath Filter Builder

```rust
struct FilterBuilder {
    conditions: Vec<String>,
}

impl FilterBuilder {
    fn new() -> Self {
        Self { conditions: Vec::new() }
    }
    
    fn equals(mut self, field: &str, value: &str) -> Self {
        self.conditions.push(format!("{}=='{}'", field, value));
        self
    }
    
    fn greater_than(mut self, field: &str, value: i64) -> Self {
        self.conditions.push(format!("{} > `{}`", field, value));
        self
    }
    
    fn build(self) -> String {
        if self.conditions.is_empty() {
            "[*]".to_string()
        } else {
            format!("[?{}]", self.conditions.join(" && "))
        }
    }
}

// Usage:
let filter = FilterBuilder::new()
    .equals("faction", "argon")
    .equals("owner", "player")
    .build();
// Result: "[?faction=='argon' && owner=='player']"
```

### Error Recovery

```rust
fn query_with_extraction(command: &str, save: &str) -> Result<Value, String> {
    let result = query_cli(command, save, None);
    
    match result {
        Ok(json) => {
            let response: Value = serde_json::from_str(&json)?;
            
            if response["success"].as_bool() == Some(false) {
                let code = response["code"].as_i64();
                
                // Handle "not extracted" error
                if code == Some(137401) {
                    eprintln!("Save not extracted, running extraction...");
                    extract_save(save)?;
                    
                    // Retry query
                    let json = query_cli(command, save, None)?;
                    parse_query_response(&json)
                } else {
                    Err(response["message"].as_str()
                        .unwrap_or("Unknown error").to_string())
                }
            } else {
                parse_query_response(&json)
            }
        }
        Err(e) => Err(e)
    }
}

fn extract_save(save: &str) -> Result<(), String> {
    let output = Command::new("php")
        .arg("bin/php/extract.php")
        .arg("-e")
        .arg(save)
        .output()
        .map_err(|e| format!("Extraction failed: {}", e))?;
    
    if output.status.success() {
        Ok(())
    } else {
        Err("Extraction process failed".to_string())
    }
}
```

---

## Best Practices

### 1. Always Check Success Flag

```rust
let response: Value = serde_json::from_str(&json)?;
if !response["success"].as_bool().unwrap_or(false) {
    // Handle error
}
```

### 2. Use Type-Safe Deserialization

```rust
#[derive(Deserialize)]
struct ApiResponse<T> {
    success: bool,
    data: Option<T>,
    message: Option<String>,
}

let response: ApiResponse<Vec<Ship>> = serde_json::from_str(&json)?;
```

### 3. Cache Aggressively

Use `--cache-key` for any filtered query that will be paginated:

```rust
let cache_key = format!("{}_{}", command, filter_hash);
```

### 4. Escape Filter Strings

JMESPath uses backticks for literals. Ensure proper escaping:

```rust
fn escape_jmespath_string(s: &str) -> String {
    format!("'{}'", s.replace("'", "\\'"))
}
```

### 5. Handle Partial Results

When pagination is used, check `hasMore` to determine if more data exists:

```rust
let has_more = response["pagination"]["hasMore"].as_bool().unwrap_or(false);
if has_more {
    // Fetch next page
}
```

### 6. Monitor for Save Changes

Before using cached results, verify the save hasn't been modified:

```rust
use std::fs::metadata;
use std::time::SystemTime;

fn check_save_modified(save_path: &Path, cached_time: SystemTime) -> bool {
    metadata(save_path)
        .and_then(|m| m.modified())
        .map(|t| t > cached_time)
        .unwrap_or(true)
}
```

---

## Version Compatibility

The `version` field in responses indicates the API version. Breaking changes will increment the minor version number (e.g., `0.1.0` → `0.2.0`).

**Current Version**: `0.1.0`

**Compatibility Promise**:
- Patch versions (0.1.x): Backward compatible
- Minor versions (0.x.0): May introduce breaking changes
- Major versions (x.0.0): Significant breaking changes

**Checking Compatibility**:

```rust
fn check_api_version(response: &Value) -> Result<(), String> {
    let version = response["version"].as_str()
        .ok_or("Missing version field")?;
    
    let parts: Vec<&str> = version.split('.').collect();
    let minor: u32 = parts.get(1)
        .and_then(|s| s.parse().ok())
        .ok_or("Invalid version format")?;
    
    if minor > 1 {
        return Err(format!("Incompatible API version: {}", version));
    }
    
    Ok(())
}
```

---

## Performance Considerations

### Response Size

Large collections (ships, stations) can return thousands of items. Use filters and pagination to reduce response size:

```bash
# Bad: Returns all 5000+ ships
bin/query ships --save=quicksave

# Good: Returns 50 relevant ships
bin/query ships --save=quicksave \
  --filter="[?owner=='player']" \
  --limit=50
```

### Caching Strategy

- **First query**: May be slow (filtering applied)
- **Cached queries**: Fast (pre-filtered results)
- **Cache invalidation**: Automatic on save modification

**Recommended**: Always use `--cache-key` for paginated queries.

### Parallel Queries

CLI commands can be executed in parallel (separate processes):

```rust
use rayon::prelude::*;

let queries = vec!["ships", "stations", "blueprints"];
let results: Vec<_> = queries.par_iter()
    .map(|cmd| query_cli(cmd, "quicksave", None))
    .collect();
```

---

## Troubleshooting

### Query Returns Empty Array

**Check**:
1. Is the save extracted? (`bin/extract -l`)
2. Does the filter match any data? (remove `--filter` to see all)
3. Is the save ID/name correct?

### JMESPath Syntax Error

**Common mistakes**:
- Forgetting backticks for literals: `` \`true\` ``, `` \`123\` ``
- Incorrect escaping: Use `\'` for quotes in strings
- Missing closing brackets: `[?field=='value'`]

### Cache Not Working

**Verify**:
1. Same `--cache-key` used across requests
2. Save file not modified between requests
3. `.cache` directory exists in save storage folder

### Performance Issues

**Solutions**:
1. Add `--filter` to reduce dataset size
2. Use `--cache-key` for repeated queries
3. Increase `--limit` to reduce number of requests
4. Run `clear-cache` if cache grows too large

---

## Summary

The CLI API provides a robust, machine-readable interface for accessing X4 savegame data:

- ✅ Standard JSON envelope for consistent parsing
- ✅ JMESPath filtering for flexible queries
- ✅ Pagination with caching for performance
- ✅ Comprehensive error handling with actionable suggestions
- ✅ Cross-platform support (Windows, Linux, macOS)
- ✅ Type-safe integration with Rust/Tauri

For implementation questions or issues, refer to the source code or open an issue on the project repository.

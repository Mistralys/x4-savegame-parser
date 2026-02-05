# Tech Stack & Patterns

## Runtime Environment

- **PHP Version**: 8.4+ (strict typing enabled)
- **Required Extensions**:
  - `ext-xmlreader` - Streaming XML parsing
  - `ext-dom` - DOM-based XML manipulation
  - `ext-zlib` - Compression/decompression for `.gz` savegame files

## Core Dependencies

### Primary Libraries

- **mistralys/x4-core** (>=1.2.0) - Base application framework
  - Provides `X4Application`, `UserInterface`, `BasePage`, `BasePageWithNav`
  - UI component system (Button, Icon, DataGrid, etc.)
  - Request routing and session management

- **react/http** (>=1.9.0) - Async HTTP server
  - Event-driven HTTP server for UI hosting
  - Socket server for local development

- **react/async** (>=3.0.0) - Promise-based async utilities
  - Used in monitor for non-blocking operations

- **league/climate** (^3.8.2) - CLI framework
  - Command-line argument parsing
  - Formatted terminal output

- **mtdowling/jmespath.php** (^2.7) - JMESPath query language
  - JSON filtering and transformation
  - Used in CLI API for data queries

### Supporting Libraries

- **mistralys/application-utils** family - Utility collections
  - FileHelper, ConvertHelper, StringBuilder
  - ArrayDataCollection base class

- **mistralys/version-parser** (>=1.0.0) - Version string parsing
- **mistralys/application-localization** (>=1.4.1) - i18n support
- **mistralys/x4-data-extractor** - Game data extraction utilities

### Development Tools

- **phpunit/phpunit** (>=9.6.7) - Unit testing framework
- **phpstan/phpstan** (>=1.10) - Static analysis

## Testing Strategy

### Test Framework
- **PHPUnit** for unit and integration tests
- **PHPStan** for static analysis (level 8)

### Test Data Approach
- **Synthetic Test Data**: Minimal JSON files committed to git
- **Location**: `tests/files/test-saves/unpack-20230524120000-quicksave/`
- **Collections**: 10 complete collection JSON files with 2 items each
- **Benefit**: Tests run without game installation or save extraction

### Test Coverage
- **CLI API Tests**: 32 tests covering QueryHandler, collections, filtering, pagination, caching
- **Current Coverage**: ~40% for CLI components
- **Test Suites**:
  - `tests/testsuites/CLI/` - CLI API functionality
  - `tests/testsuites/Parser/` - Parser components
  - `tests/testsuites/Reader/` - Data readers

### Running Tests
```bash
vendor/bin/phpunit                              # Run all tests
vendor/bin/phpunit tests/testsuites/CLI/        # Run CLI tests only
vendor/bin/phpunit --testdox                    # Run with documentation
```

## Architectural Patterns

### 1. Collections Pattern

Central aggregation of parsed game entities. All Type instances are registered in typed Collections.

```
Collections (hub)
  ├── ShipsCollection
  ├── StationsCollection
  ├── PeopleCollection
  ├── SectorsCollection
  └── [7 more typed collections]
```

- **Singleton per parse session**: One Collections instance per SaveParser execution
- **Type registration**: Each parsed component registers itself in appropriate collection
- **Cross-referencing**: Components store `uniqueID` references to other components
- **JSON serialization**: Collections serialize to JSON files for storage

### 2. Fragment-Based XML Parsing

Two-stage parsing approach for handling large (1GB+) XML files:

**Stage 1: Stream Extraction (XMLReader)**
- Reads XML without loading entire file into memory
- Extracts specific tag paths (e.g., `savegame.universe.connections`)
- Saves fragments as individual XML files in temp directory

**Stage 2: DOM Processing (DOMDocument)**
- Loads manageable fragment files
- Navigates XML tree structure
- Populates Collections with Type instances

```
BaseXMLParser (abstract)
  ├── SaveParser - Main orchestrator
  │
BaseFragment (abstract)
  ├── BaseDOMFragment - For DOM-based fragments
  └── BaseXMLFragment - For custom parsing
```

### 3. Type System (Data Models)

All game entities extend `BaseComponentType` (which extends `ArrayDataCollection`).

**Key characteristics**:
- Immutable `componentID` and `connectionID`
- Parent-child relationships via `uniqueID` references
- Data stored as associative arrays (key-value pairs)
- Automatic JSON serialization via `toArray()`

**Type Traits** (Composition):
- `PersonContainerTrait` - Can contain NPCs
- `ShipContainerTrait` - Can contain ships
- `PlayerContainerTrait` - Can register player

### 4. Data Reader Pattern

High-level API for accessing parsed save data. Lazy-loaded readers that parse JSON files on demand.

```
SaveReader (facade)
  ├── Blueprints - Ship/station blueprints
  ├── Factions - Faction relations
  ├── Statistics - Game stats
  ├── Log - Event log with categories
  ├── Inventory - Player inventory
  └── [more specialized readers]
```

### 5. CLI API Pattern

Query interface for programmatic access to savegame data with filtering and pagination.

```
QueryHandler (orchestrator)
  ├── QueryValidator - Input validation
  ├── QueryCache - Result caching per-save
  ├── JsonResponseBuilder - Standard envelope
  └── JMESPath filtering - Query language
```

**Key components**:
- **Standard Response Envelope**: Consistent JSON structure (`success`, `version`, `data`, `pagination`)
- **JMESPath Filtering**: Powerful query language for filtering arrays/objects
- **Query Caching**: Per-save cache isolation for efficient pagination
- **Validation Layer**: Pre-execution validation with actionable error messages
- **Data Serializers**: `toArrayForAPI()` methods on all readers for JSON output

**Query flow**:
1. Parse CLI arguments (command, flags)
2. Validate save, pagination, cache key
3. Load data from SaveReader
4. Apply JMESPath filter (if provided)
5. Check/use cache (if cache-key provided)
6. Apply pagination (limit/offset)
7. Build JSON response envelope

### 6. Monitor Pattern (Observer)

Event-driven monitoring system with pluggable output formats.

```
BaseMonitor (abstract)
  ├── X4Monitor - Savegame folder watcher
  └── X4Server - HTTP UI server
      
MonitorOutputInterface
  ├── ConsoleOutput - Human-readable logs
  └── JsonOutput - NDJSON machine-readable stream
```

**Event Loop**: ReactPHP event loop with tick-based polling
**Notifications**: Fire events (`SAVE_DETECTED`, `SAVE_PARSING_STARTED`, etc.)

### 7. UI Page Hierarchy (DEPRECATED)

Three-tier page structure inherited from `mistralys/x4-core`:

```
BasePage (from x4-core)
  └── Page (SaveViewer base)
      ├── MainPage - Top-level navigation pages
      └── PageWithNav - Pages with sub-navigation
          ├── ViewSave - Savegame detail view
          │   └── [Subpages: Home, Statistics, Blueprints, etc.]
          └── ViewPlanPage - Construction plan viewer
              └── [Subpages: Overview, Settings]
```

**Rendering flow**:
1. Route HTTP request to Page class
2. Page calls `init()`, `preRender()`
3. Page renders navigation, then `renderContent()`
4. Output HTML via UserInterface templating

### 8. Configuration Management

Singleton configuration loader with type-safe getters.

- **Source**: `config.json` (falls back to `config.dist.json`)
- **Format**: JSON key-value pairs
- **Access**: Static methods on `Config` class
- **Validation**: Type coercion and defaults

## Storage Strategy

### Primary Storage: JSON Files

All extracted data stored as prettified JSON for:
- Human readability
- Easy third-party integration
- Version control friendly
- No database dependencies

**Storage structure**:
```
storage/
  └── unpack-{datetime}-{savename}/
      ├── analysis.json         # Metadata
      ├── backup.gz             # Original savegame backup
      ├── .cache/               # Query result cache (hidden)
      │   └── query-{key}.json  # Cached filtered results
      ├── JSON/                 # Parsed data
      │   ├── collection-ships.json
      │   ├── collection-stations.json
      │   ├── data-blueprints.json
      │   └── [more data files]
      └── XML/                  # Temporary fragments (optional)
          └── [fragment files, deleted after parse]
```

### Temporary Storage: XML Fragments

- Created during extraction phase
- Deleted after parsing (configurable via `keepXMLFiles`)
- Located in `{storage}/XML/` subdirectory

## Execution Modes

### 1. CLI Extraction Mode
- **Entry**: `bin/extract` (wrapper) → `bin/php/extract.php`
- **Handler**: `CLIHandler`
- **Features**: List saves, extract single/multiple, rebuild JSON

### 2. CLI API Mode
- **Entry**: `bin/query` (wrapper) → `bin/php/query.php`
- **Handler**: `QueryHandler`
- **Output**: Standard JSON response envelope
- **Features**: Query savegame data with JMESPath filtering, pagination, caching

### 3. UI Server Mode (DEPRECATED)
- **Entry**: `bin/run-ui` (wrapper) → `bin/php/run-ui.php`
- **Server**: `X4Server` with ReactPHP HTTP server
- **Port**: Configurable (default: 9494)
- **Access**: Browser-based interface
- **Status**: Deprecated in favor of CLI API

### 4. Monitor Mode
- **Entry**: `bin/run-monitor` (wrapper) → `bin/php/run-monitor.php`
- **Monitor**: `X4Monitor` with event loop
- **Output**: Console or NDJSON (`--json` flag)
- **Frequency**: Tick-based polling (default: 5 seconds)

## Key Design Decisions

1. **Synchronous I/O**: All file operations use blocking PHP I/O (not async)
2. **No Database**: JSON files serve as the data layer
3. **Stateless Parsing**: Each parse creates fresh Collections instance
4. **Reference by ID**: Components reference each other via `uniqueID` strings
5. **Lazy Loading**: UI data readers parse JSON on-demand
6. **Two-Stage XML**: Stream extraction + DOM parsing for memory efficiency
7. **Event-Driven Monitoring**: ReactPHP event loop for responsiveness
8. **CLI API over Web UI**: Programmatic JSON API preferred over browser interface
9. **JMESPath for Filtering**: Standard query language for flexible data filtering
10. **Per-Save Caching**: Query caches isolated by save to prevent cross-contamination
11. **Standard Response Envelope**: Consistent JSON structure for predictable parsing
12. **Deprecation of Web UI**: Moving away from HTTP server toward CLI-first approach

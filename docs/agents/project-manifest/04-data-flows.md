# Data Flows

This document describes the key data flow patterns in the X4 Savegame Parser application.

---

## 1. CLI Extraction Flow

**Entry Point**: `bin/extract` → `bin/php/extract.php`

**Purpose**: Manually extract savegame data to JSON files.

### Flow Diagram

```
User Command
    ↓
[CLIHandler::handle()]
    ↓
Parse Arguments (league/climate)
    ↓
[SaveManager::getSaveByName()]
    ↓
[SaveGameFile] Wrapper
    ↓
┌─────────────────────────────────────────┐
│ SaveParser::unpack() - Main Pipeline   │
│ - Capture start time (microtime(true)) │
└─────────────────────────────────────────┘
    ↓
┌──────────────────────────────────────┐
│ STAGE 1: Unzip (.gz → .xml)         │
│ - SaveGameFile::unzip()              │
│ - Uses PHP's gzopen/gzread           │
│ - Writes to temp XML file            │
└──────────────────────────────────────┘
    ↓
┌──────────────────────────────────────┐
│ STAGE 2: Fragment Extraction         │
│ - BaseXMLParser::processFile()       │
│ - XMLReader stream parsing           │
│ - Extract specific tag paths         │
│ - Write fragments to XML/            │
└──────────────────────────────────────┘
    ↓
    └─→ Fragment files:
         - XML/connections.xml
         - XML/log.xml
         - XML/stats.xml
         - etc.
    ↓
┌──────────────────────────────────────┐
│ STAGE 3: DOM Parsing                 │
│ - BaseXMLParser::postProcessFragments│
│ - Each Fragment::parseDOM()          │
│ - DOMDocument loads fragment         │
│ - Navigate XML tree                  │
│ - Create Type instances              │
│ - Register in Collections            │
└──────────────────────────────────────┘
    ↓
┌──────────────────────────────────────┐
│ Collections (in-memory)              │
│ - ShipsCollection                    │
│ - StationsCollection                 │
│ - PeopleCollection                   │
│ - SectorsCollection                  │
│ - etc.                               │
│                                      │
│ Each Type has:                       │
│ - componentID, connectionID          │
│ - data array (properties)            │
│ - uniqueID references to others      │
└──────────────────────────────────────┘
    ↓
┌──────────────────────────────────────┐
│ STAGE 4: Data Processing             │
│ - DataProcessingHub::process()       │
│ - Run specialized processors         │
│ - Generate derived data              │
│   (e.g., Khaa'k stations list)       │
└──────────────────────────────────────┘
    ↓
┌──────────────────────────────────────┐
│ STAGE 5: JSON Serialization          │
│ - Collections::save()                │
│ - Each collection::toArray()         │
│ - FileHelper::saveAsJSON()           │
│ - Write to JSON/ directory           │
└──────────────────────────────────────┘
    ↓
    └─→ JSON files:
         - JSON/collection-ships.json
         - JSON/collection-stations.json
         - JSON/collection-people.json
         - JSON/data-blueprints.json
         - JSON/data-khaak-stations.json
         - etc.
    ↓
┌──────────────────────────────────────┐
│ STAGE 6: Metadata & Backup           │
│ - Calculate extraction duration      │
│ - FileAnalysis::setExtractionDuration│
│ - FileAnalysis::save()               │
│ - Write analysis.json (includes      │
│   duration in seconds + formatted)   │
│ - Optional: create backup.gz         │
└──────────────────────────────────────┘
    ↓
┌──────────────────────────────────────┐
│ STAGE 7: Cleanup                     │
│ - SaveParser::cleanUp()              │
│ - Delete XML/ folder (if configured) │
│ - Keep JSON data only                │
└──────────────────────────────────────┘
    ↓
DONE - Data ready for UI consumption
```

### Key Classes Involved

- **CLIHandler**: Command parsing and execution
- **SaveManager**: File discovery and management
- **SaveGameFile**: File wrapper with unzip capability
- **SaveParser**: Main orchestrator
- **BaseXMLParser**: XMLReader streaming logic
- **Fragment classes**: DOM parsing per XML section
- **Collections**: In-memory entity registry
- **Type classes**: Data models
- **DataProcessingHub**: Post-processing logic
- **FileAnalysis**: Metadata tracking

### Data Transformations

1. **Compressed Binary** (`.gz`) → **XML** (uncompressed)
2. **Large XML** (1GB+) → **XML Fragments** (manageable chunks)
3. **XML Fragments** → **Type Objects** (OOP models)
4. **Type Objects** → **Collections** (indexed registry)
5. **Collections** → **JSON Files** (persistent storage)

---

## 2. Monitor Flow (Automatic Extraction)

**Entry Point**: `bin/run-monitor` → `bin/php/run-monitor.php`

**Purpose**: Watch savegame folder and auto-extract new saves.

### Flow Diagram

```
[Monitor Start]
    ↓
Parse CLI flags (--json for NDJSON output)
    ↓
[X4Monitor::start()]
    ↓
Initialize ReactPHP Event Loop
    ↓
[X4Monitor::setup()]
    ↓
Notify: MONITOR_STARTED
    ↓
┌────────────────────────────────────────┐
│ Event Loop (Tick-based, every 5 sec)  │
└────────────────────────────────────────┘
    ↓
    ↓ (each tick)
    ↓
[X4Monitor::_handleTick()]
    ↓
┌────────────────────────────────────────┐
│ Check for New Savegame                 │
│ - SaveManager::getCurrentSave()        │
│ - Get most recently modified file      │
└────────────────────────────────────────┘
    ↓
    ├─→ No new save → Log "No savegame found" → Continue loop
    ↓
    └─→ New save detected
        ↓
        Notify: SAVE_DETECTED
        ↓
        ┌────────────────────────────────┐
        │ Check if Already Parsed        │
        │ - BaseSaveFile::hasData()      │
        │ - Check for analysis.json      │
        └────────────────────────────────┘
        ↓
        ├─→ Already parsed → Log "Skipping" → Continue loop
        ↓
        └─→ Not parsed → Begin extraction
            ↓
            Notify: SAVE_PARSING_STARTED
            ↓
            ┌────────────────────────────────┐
            │ Async Promise Execution        │
            │ (React\Async\await)            │
            └────────────────────────────────┘
            ↓
            Notify: SAVE_UNZIPPING
            ↓
            [SaveGameFile::unzip()]
            ↓
            Notify: SAVE_EXTRACTING
            ↓
            [SaveParser::unpack()]
            ↓
            ┌─────────────────────────────────┐
            │ Same as CLI Extraction Flow     │
            │ (Stages 2-7 from above)         │
            └─────────────────────────────────┘
            ↓
            Notify: SAVE_PARSING_COMPLETE
            ↓
            Log: "Parsing complete"
            ↓
            Continue event loop
```

### NDJSON Event Stream

When run with `--json` flag, events are output as NDJSON (one JSON object per line):

```json
{"type":"event","timestamp":"2026-01-29T10:30:00+00:00","name":"MONITOR_STARTED","payload":{}}
{"type":"tick","timestamp":"2026-01-29T10:30:05+00:00","counter":1}
{"type":"event","timestamp":"2026-01-29T10:30:10+00:00","name":"SAVE_DETECTED","payload":{"name":"quicksave","path":"..."}}
{"type":"event","timestamp":"2026-01-29T10:30:10+00:00","name":"SAVE_PARSING_STARTED","payload":{"name":"quicksave"}}
{"type":"event","timestamp":"2026-01-29T10:30:10+00:00","name":"SAVE_UNZIPPING","payload":{}}
{"type":"event","timestamp":"2026-01-29T10:30:15+00:00","name":"SAVE_EXTRACTING","payload":{}}
{"type":"log","timestamp":"2026-01-29T10:30:45+00:00","message":"Parsing complete","level":"info"}
{"type":"event","timestamp":"2026-01-29T10:30:45+00:00","name":"SAVE_PARSING_COMPLETE","payload":{"name":"quicksave"}}
{"type":"tick","timestamp":"2026-01-29T10:30:50+00:00","counter":2}
```

See [ndjson-interface.md](./ndjson-interface.md) for complete NDJSON specification.

### Key Classes Involved

- **X4Monitor**: Monitor daemon
- **BaseMonitor**: Event loop and tick management
- **MonitorOutputInterface**: Output abstraction
- **JsonOutput**: NDJSON formatter
- **ConsoleOutput**: Human-readable formatter
- **SaveManager**: File discovery
- **SaveParser**: Extraction orchestration (reuses CLI flow)

### Execution Context

- **Event Loop**: ReactPHP `Loop::run()`
- **Tick Interval**: Configurable (default 5 seconds)
- **Async Execution**: `React\Async\await()` for non-blocking parsing
- **Output**: Stdout (NDJSON or console text)

---

## 3. UI Server & Request Flow

**Entry Point**: `bin/run-ui` → `bin/php/run-ui.php`

**Purpose**: Serve web-based UI for browsing savegames.

### Server Initialization

```
[Server Start]
    ↓
[X4Server::start()]
    ↓
Create ReactPHP HTTP Server
    ↓
[X4Server::setup()]
    ↓
Bind to host:port (default: localhost:9494)
    ↓
Start event loop
    ↓
Server listening... (log URL)
    ↓
Wait for HTTP requests
```

### Request Handling Flow

```
Browser Request (e.g., GET /ViewSave?save=quicksave-12345)
    ↓
[X4Server::handleRequest(ServerRequestInterface)]
    ↓
Parse request target & query params
    ↓
┌────────────────────────────────────────┐
│ Route Analysis                         │
│ - Is it a static file? (js, css, etc) │
│ - Or a page route?                     │
└────────────────────────────────────────┘
    ↓
    ├─→ Static file request
    │   ↓
    │   Check extension in ALLOWED_EXTENSIONS
    │   ↓
    │   ├─→ Allowed → Serve file (200 OK)
    │   └─→ Denied → 403 Forbidden
    │
    └─→ Page route request
        ↓
        [SaveViewer::getUI()]
        ↓
        [UserInterface::route($requestVars)]
        ↓
        ┌────────────────────────────────┐
        │ Page Resolution                │
        │ - Extract page name from URL   │
        │ - Look up in registered pages  │
        │ - Instantiate Page class       │
        └────────────────────────────────┘
        ↓
        Page Instance (e.g., ViewSave)
        ↓
        [Page::init()]
        ↓
        [Page::preRender()]
        ↓
        ┌────────────────────────────────┐
        │ Data Loading                   │
        │ - Parse URL params             │
        │ - Load BaseSaveFile            │
        │ - Create SaveReader            │
        └────────────────────────────────┘
        ↓
        [Page::renderContent()]
        ↓
        ┌────────────────────────────────┐
        │ Data Reader Access             │
        │ (Lazy JSON parsing)            │
        └────────────────────────────────┘
        ↓
        ├─→ $reader->getBlueprints()
        │   ↓
        │   Parse JSON/data-blueprints.json
        │   ↓
        │   Return Blueprints object
        │
        ├─→ $reader->getStatistics()
        │   ↓
        │   Parse JSON/stats.json
        │   ↓
        │   Return Statistics object
        │
        └─→ $reader->getLog()
            ↓
            Parse JSON/collection-eventlog.json
            ↓
            Return Log object with categories
        ↓
        ┌────────────────────────────────┐
        │ HTML Rendering                 │
        │ - Page renders HTML            │
        │ - UI components (Grid, Button) │
        │ - Template system              │
        └────────────────────────────────┘
        ↓
        [UserInterface::render()]
        ↓
        HTML output
        ↓
        [Response] (200 OK, text/html)
        ↓
        Send to browser
```

### Data Access Pattern (SaveReader)

The UI uses lazy-loading data readers that parse JSON files on-demand:

```
ViewSave Page
    ↓
$reader = $save->getDataReader()
    ↓
    ├─→ $reader->getBlueprints()
    │       ↓
    │   [Blueprints::__construct()]
    │       ↓
    │   Read JSON/data-blueprints.json
    │       ↓
    │   Parse JSON to array
    │       ↓
    │   Return Blueprints object
    │       ↓
    │   $blueprints->getOwned()
    │   $blueprints->getUnowned()
    │
    ├─→ $reader->getStatistics()
    │       ↓
    │   Read JSON/stats.json
    │       ↓
    │   Return Statistics object
    │       ↓
    │   $stats->getStats()
    │
    └─→ $reader->getLog()
            ↓
        Read JSON/collection-eventlog.json
            ↓
        Parse entries
            ↓
        Categorize with LogCategories
            ↓
        Return Log object
            ↓
        $log->getEntriesByCategory('combat')
```

### Navigation Structure

```
Main Pages (Top-level nav)
    ├─→ SavesList (list of active saves)
    ├─→ ArchivedSavesPage (list of archived saves)
    └─→ ConstructionPlansPage (construction plans browser)

ViewSave (Save detail with sub-nav)
    ├─→ Home (properties overview)
    ├─→ Statistics (game stats)
    ├─→ BlueprintsPage (owned/unowned blueprints)
    ├─→ EventLogPage (event log with categories)
    ├─→ Factions (faction relations)
    ├─→ Inventory (player inventory)
    ├─→ KhaakOverviewPage (Khaa'k stations)
    └─→ Losses (ship losses)

ViewPlanPage (Construction plan detail)
    ├─→ PlanOverviewPage (modules list)
    └─→ PlanSettingsPage (plan configuration)
```

### Key Classes Involved

- **X4Server**: HTTP server daemon
- **SaveViewer**: Application class (extends X4Application)
- **UserInterface**: Routing and rendering (from x4-core)
- **Page classes**: ViewSave, SavesList, etc.
- **SaveReader**: Data access facade
- **Data readers**: Blueprints, Statistics, Log, etc.
- **UI components**: DataGrid, Button, Icon (from x4-core)

---

## 4. Construction Plans Flow

**Entry Point**: UI page or programmatic access

**Purpose**: Parse and display user-created construction plans.

### Flow Diagram

```
[ConstructionPlansParser::createFromConfig()]
    ↓
Locate constructionplans.xml
(in game folder: {gameFolder}/constructionplans.xml)
    ↓
┌────────────────────────────────────────┐
│ XML Parsing (DOMDocument)              │
│ - Load entire XML file                 │
│ - Find all <plan> elements             │
└────────────────────────────────────────┘
    ↓
For each <plan>:
    ↓
    [ConstructionPlan] object
        ↓
        Extract attributes:
        - id (plan identifier)
        - name (plan label)
        ↓
        Find all <module> children
        ↓
        For each <module>:
            ↓
            [PlanModule] object
                ↓
                Extract attributes:
                - macro (module type ID)
                - amount (quantity)
                ↓
                Lookup category in ModuleCategories
                ↓
                Determine if production module
            ↓
        Store modules in plan
    ↓
    Store plan in parser
    ↓
Sort plans alphabetically by label
    ↓
[ConstructionPlansParser] ready
    ↓
    ├─→ UI Access:
    │   ↓
    │   [ViewPlanPage]
    │   ↓
    │   Display modules grouped by category
    │   ↓
    │   Show production calculations
    │
    └─→ Programmatic Access:
        ↓
        $parser->getPlans()
        $parser->getPlan($id)
        ↓
        $plan->getModules()
        $plan->getProductions()
        $plan->getHabitats()
```

### Data Structure

```
ConstructionPlansParser
    └─→ ConstructionPlan[]
            ├─→ id: string
            ├─→ label: string
            └─→ PlanModule[]
                    ├─→ macro: string (e.g., "module_gen_prod_energycells_01")
                    ├─→ amount: int
                    ├─→ category: string (e.g., "production", "habitats")
                    └─→ isProduction: bool
```

### Module Categories

Defined in `ModuleCategories` class:

- **production** - Production modules (e.g., energy cells, food)
- **processing** - Processing modules (refining)
- **habitats** - Living quarters
- **defence** - Weapons and shields
- **storage** - Cargo storage
- **docks** - Docking bays
- **build** - Build storage and shipyards

### Key Classes Involved

- **ConstructionPlansParser**: Main parser
- **ConstructionPlan**: Individual plan model
- **PlanModule**: Module within a plan
- **ModuleCategories**: Category definitions
- **ProductionModule**: Specialized production module (extends PlanModule)
- **ViewPlanPage**: UI for plan viewing
- **PlanOverviewPage**: Module list sub-page
- **PlanSettingsPage**: Plan configuration sub-page

---

## 5. Save File Discovery Flow

**Context**: Finding savegame files on disk

**Purpose**: Scan savegame folder and identify valid saves.

### Flow Diagram

```
[SaveSelector::getSaveGames()]
    ↓
Scan savesFolder (e.g., C:\Users\...\X4\11111111)
    ↓
Find all .xml.gz files
    ↓
For each file:
    ↓
    Create [SaveGameFile] wrapper
        ↓
        Detect file mode (zip or xml)
        ↓
        Extract metadata:
        - baseName (without extension)
        - modified date
        ↓
        Create [FileAnalysis]
            ↓
            Determine storage folder path:
            storage/unpack-{datetime}-{basename}/
            ↓
            Check if analysis.json exists
                ↓
                ├─→ Exists: Load metadata
                └─→ Not exists: Fresh save
    ↓
Sort by modified date (newest first)
    ↓
Return SaveGameFile[] array
    ↓
[SaveManager] wraps in MainSave/ArchivedSave
```

### Archived Saves Discovery

```
[SaveManager::getArchivedSaves()]
    ↓
Scan storageFolder for unpack-* directories
    ↓
For each directory:
    ↓
    Check for analysis.json
    ↓
    ├─→ Exists: Create [ArchivedSave]
    └─→ Not exists: Skip
    ↓
Sort by date (newest first)
    ↓
Return ArchivedSave[] array
```

### Key Classes Involved

- **SaveSelector**: File discovery
- **SaveGameFile**: File wrapper
- **FileAnalysis**: Metadata tracking
- **SaveManager**: High-level save management
- **MainSave**: Active save file
- **ArchivedSave**: Archived save (no source .gz file)

---

## 6. Data Processing Flow (Post-Parse)

**Context**: After Collections are populated

**Purpose**: Generate derived/aggregated data from parsed entities.

### Flow Diagram

```
[SaveParser::postProcessFragments()]
    ↓
Collections populated with Type objects
    ↓
[DataProcessingHub::process()]
    ↓
Discover all processor classes in Processors/
    ↓
For each processor:
    ↓
    Instantiate processor with Collections
    ↓
    [BaseDataProcessor::process()]
        ↓
        Access collections:
        - $collections->ships()
        - $collections->stations()
        - etc.
        ↓
        Apply custom logic:
        ↓
        Example: KhaakStationsList
            ↓
            Filter stations by owner = "khaak"
            ↓
            Extract station properties
            ↓
            Build derived data structure
        ↓
        [BaseDataProcessor::saveAsJSON()]
            ↓
            Write to JSON/data-{processor}.json
    ↓
All processors complete
    ↓
Additional derived data available for UI
```

### Example: Khaa'k Stations Processor

```
[KhaakStationsList::_process()]
    ↓
$stations = $collections->stations()->getComponentsByType('station')
    ↓
Filter: $station->getOwner() === 'khaak'
    ↓
For each Khaa'k station:
    ↓
    Extract:
    - name
    - code
    - sector
    - state (normal/wreck)
    ↓
Build array of Khaa'k stations
    ↓
[saveAsJSON($data, 'khaak-stations')]
    ↓
Write to JSON/data-khaak-stations.json
```

### Key Classes Involved

- **DataProcessingHub**: Processor orchestrator
- **BaseDataProcessor**: Abstract processor base
- **KhaakStationsList**: Khaa'k stations aggregator
- **Other processors**: Custom data aggregations

---

## 7. Backup Creation Flow

**Context**: Creating savegame backups

**Purpose**: Copy .gz file to storage for archival.

### Flow Diagram

```
[SaveParser::createBackup()]
    ↓
Check if $saveFile is set
    ↓
Get backup file path:
storage/unpack-{datetime}-{name}/backup.gz
    ↓
Copy source .gz to backup location
    ↓
[FileHelper::copyFile()]
    ↓
Backup created
```

### Manual Backup (UI)

```
User clicks "Create Backup" button
    ↓
[CreateBackup Page]
    ↓
$save = $this->requireSave()
    ↓
$save->getSaveFile()->getZipFile()
    ↓
Copy to backup.gz in storage
    ↓
Redirect to save view
```

---

## Summary of Key Data Flows

| Flow | Entry Point | Output | Storage Location |
|------|-------------|--------|------------------|
| **CLI Extraction** | `bin/extract` | JSON files | `storage/unpack-*/JSON/` |
| **Monitor** | `bin/run-monitor` | JSON files + NDJSON events | `storage/unpack-*/JSON/` + stdout |
| **UI Server** | `bin/run-ui` | HTML pages | In-memory (reads JSON files) |
| **Construction Plans** | UI or code | ConstructionPlan objects | Reads `{gameFolder}/constructionplans.xml` |
| **Save Discovery** | SaveSelector | SaveGameFile[] | Scans `{savesFolder}/*.gz` |
| **Data Processing** | Post-parse | Derived JSON files | `storage/unpack-*/JSON/data-*.json` |
| **Backup** | Manual or auto | `.gz` copy | `storage/unpack-*/backup.gz` |

---

## Data Lifecycle

```
1. User plays X4 → Game saves to .xml.gz
2. Monitor detects new save → Triggers extraction
3. Extraction unzips → Creates XML fragments → Parses to Collections → Saves as JSON
4. JSON files persist in storage/unpack-{datetime}-{name}/JSON/
5. UI reads JSON files on-demand → Displays in browser
6. User can re-extract or delete archived data
```

---

## Performance Considerations

1. **Streaming XML**: XMLReader avoids loading 1GB+ files into memory
2. **Fragment Approach**: Breaks large XML into manageable pieces
3. **Lazy Loading**: UI readers parse JSON only when accessed
4. **Event Loop**: ReactPHP enables non-blocking I/O for servers
5. **JSON Storage**: Fast read access, no database overhead
6. **Cleanup**: Optional XML deletion reduces disk usage

---

## Error Handling

Each flow includes error handling:

- **File not found**: Throws SaveViewerException
- **Parse errors**: Logged and skipped (continues processing)
- **HTTP errors**: Returns appropriate status codes (403, 404, 500)
- **Invalid data**: Validation with defaults and type checking
- **Monitor failures**: Logged to output, loop continues

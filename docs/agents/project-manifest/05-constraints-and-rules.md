# Constraints & Rules

This document lists the established architectural constraints, conventions, and rules that govern the X4 Savegame Parser codebase.

---

## Language & Type System

### PHP Version

**Rule**: PHP 8.4+ required

**Reason**: Uses modern PHP features (typed properties, strict types, attributes)

**Impact**:
- All files must declare `strict_types=1`
- Type hints required on all method parameters and return values
- Nullable types explicitly declared with `?Type` or `Type|null`

**Example**:
```php
declare(strict_types=1);

class Example
{
    public function process(string $name, ?int $count = null) : array
    {
        // Implementation
    }
}
```

---

### Strict Typing

**Rule**: All PHP files use `declare(strict_types=1)`

**Reason**: Prevents type coercion bugs, enforces type safety

**Impact**:
- No automatic type conversions
- Passing wrong type throws TypeError
- Helps catch bugs at runtime

---

### Type Annotations

**Rule**: All public methods must have complete type hints

**Reason**: Self-documenting code, IDE support, static analysis

**Impact**:
- Parameter types required
- Return types required
- Property types required (PHP 7.4+)
- PHPDoc comments for complex types (arrays of objects)

**Example**:
```php
/**
 * @param string[] $names
 * @return BaseComponentType[]
 */
public function getComponentsByNames(array $names) : array
{
    // Implementation
}
```

---

## File I/O Operations

### Synchronous I/O Only

**Rule**: All file operations are **synchronous** (blocking)

**Reason**: Simplicity, PHP's native I/O is synchronous

**Impact**:
- No async/await for file operations
- ReactPHP event loop doesn't make file I/O non-blocking
- Long file operations block execution
- Use streaming (XMLReader) for large files instead

**Example**:
```php
// This is blocking, even in ReactPHP context
$contents = file_get_contents('large-file.xml'); // Blocks until complete

// Use streaming for large files
$reader = new XMLReader();
$reader->open('large-file.xml'); // Still blocking, but processes incrementally
```

---

### File Paths

**Rule**: Use `FolderInfo` and `FileInfo` from `application-utils` for all file operations

**Reason**: Cross-platform path handling, validation, metadata access

**Impact**:
- Don't use raw string paths in business logic
- Use `FolderInfo::factory($path)` to create instances
- Methods return proper types instead of strings

**Example**:
```php
use AppUtils\FileHelper\FolderInfo;
use AppUtils\FileHelper\FileInfo;

$folder = FolderInfo::factory('/path/to/folder');
$file = FileInfo::factory('/path/to/file.json');

if ($folder->exists()) {
    $files = $folder->getFiles();
}
```

---

## XML Parsing Strategy

### Two-Stage Parsing

**Rule**: Large XML files must use two-stage parsing: **Stream Extraction** → **DOM Processing**

**Reason**: Memory efficiency for 1GB+ XML files

**Impact**:
- Cannot use DOMDocument directly on full savegame XML
- Must extract fragments first using XMLReader
- Fragments processed separately with DOMDocument

**Stages**:

1. **Stage 1**: XMLReader streams through file, extracts specific tag paths, writes fragments
2. **Stage 2**: DOMDocument loads each fragment, navigates tree, populates Collections

**Example**:
```php
// Stage 1: Stream extraction (XMLReader)
class BaseXMLParser
{
    protected function processFile() : void
    {
        $xml = new XMLReader();
        $xml->open($this->xmlFile);
        
        while ($xml->read()) {
            // Extract fragments without loading full file
        }
    }
}

// Stage 2: DOM processing (DOMDocument)
class SaveInfoFragment extends BaseDOMFragment
{
    protected function parseDOM(DOMDocument $dom) : void
    {
        // Now we can use DOM methods safely
        foreach($dom->firstChild->childNodes as $node) {
            // Process
        }
    }
}
```

---

### Fragment Cleanup

**Rule**: XML fragments are **temporary** and should be deleted after parsing

**Configuration**: `keepXMLFiles` in `config.json` (default: `false`)

**Impact**:
- XML fragments stored in `{storage}/XML/`
- Automatically deleted by `SaveParser::cleanUp()` unless `keepXMLFiles = true`
- Only JSON data persists by default

**Example**:
```php
$parser = SaveParser::create($saveFile)
    ->optionKeepXML(false) // Delete XML after parsing
    ->unpack();
```

---

## Data Storage

### JSON as Primary Storage

**Rule**: All parsed data must be stored as **prettified JSON** files

**Reason**:
- Human-readable
- Easy third-party integration
- No database dependencies
- Version control friendly

**Impact**:
- Collections serialize via `toArray()` → JSON
- Prettified (indented) format for readability
- One JSON file per collection/data type
- Files stored in `{storage}/JSON/`

**File Naming**:
- Collections: `collection-{collectionID}.json`
- Data files: `data-{dataID}.json`
- Metadata: `analysis.json`

**Example**:
```php
// Collections save to JSON
class BaseCollection
{
    public function save() : self
    {
        JSONFile::factory($this->getFilePath())
            ->putData($this->toArray(), true); // true = prettify
        return $this;
    }
}
```

---

### No Database

**Rule**: Application must **not** require a database

**Reason**: Simplicity, portability, JSON files sufficient for read-heavy workload

**Impact**:
- All data stored in JSON files
- No SQL queries
- No database schema migrations
- Query capabilities limited to in-memory array operations

---

### Storage Directory Structure

**Rule**: Each parsed save gets its own timestamped directory

**Format**: `storage/unpack-{datetime}-{savename}/`

**Structure**:
```
storage/
  └── unpack-20260129103045-quicksave/
      ├── analysis.json          # Metadata
      ├── backup.gz              # Original savegame backup
      ├── JSON/                  # Parsed data (persistent)
      │   ├── collection-ships.json
      │   ├── collection-stations.json
      │   ├── data-blueprints.json
      │   └── [more files]
      └── XML/                   # Fragments (temporary)
          └── [deleted after parse unless keepXMLFiles=true]
```

**Impact**:
- Each save isolated
- Date in folder name for sorting
- Can have multiple parses of same save (different timestamps)

---

## Collections System

### Collections as Singletons per Parse

**Rule**: One `Collections` instance per `SaveParser` execution

**Reason**: Centralized entity registry during parsing

**Impact**:
- All Type instances register in same Collections instance
- Collections instance passed to all Type constructors
- No global Collections singleton across application
- Each parse session gets fresh Collections

**Example**:
```php
$parser = SaveParser::create($saveFile);
$parser->unpack(); // Creates new Collections instance

$collections = $parser->getCollections();
$ships = $collections->ships()->getComponentsByType('ship');
```

---

### Type Registration

**Rule**: All Type instances must register themselves in appropriate Collection

**Reason**: Enables cross-referencing, lookups, serialization

**Impact**:
- Type constructors call `$collection->addComponent($this)`
- Collections maintain arrays of Type instances
- Enables `getByUniqueID()` lookups

**Example**:
```php
class ShipType extends BaseComponentType
{
    public function __construct(ShipContainerInterface $parentComponent, string $connectionID, string $componentID)
    {
        parent::__construct($parentComponent->getCollections(), $connectionID, $componentID);
        
        // Register in collection
        $this->getCollections()->ships()->addComponent($this);
    }
}
```

---

### Component References

**Rule**: Components reference each other via `uniqueID` strings, not object references

**Reason**: Enables JSON serialization, prevents circular reference issues

**Format**: `{typeID}:{componentID}` (e.g., `"ship:player_ship_001"`)

**Impact**:
- Parent-child relationships stored as strings
- Must resolve via `Collections::getByUniqueID()` to get object
- Arrays of sub-components stored as `uniqueID[]`

**Example**:
```php
class ShipType extends BaseComponentType
{
    public function addPerson(PersonType $person) : self
    {
        // Store uniqueID, not object reference
        $persons = $this->getArray(self::KEY_PERSONS);
        $persons[] = $person->getUniqueID(); // "person:123"
        return $this->setKey(self::KEY_PERSONS, $persons);
    }
    
    public function getPersons() : array
    {
        // Resolve uniqueIDs to objects
        return $this->getComponentsByKey(self::KEY_PERSONS);
    }
}
```

---

## Data Model Conventions

### ArrayDataCollection Base

**Rule**: All Type classes extend `ArrayDataCollection` (from `application-utils`)

**Reason**: Provides key-value storage with type-safe getters

**Impact**:
- Data stored in `$this->data` array
- Use `setKey()`, `getKey()`, `getString()`, `getInt()`, etc.
- Automatic `toArray()` for serialization

**Example**:
```php
class ShipType extends BaseComponentType // which extends ArrayDataCollection
{
    public function setName(string $name) : self
    {
        return $this->setKey(self::KEY_NAME, $name);
    }
    
    public function getName() : string
    {
        return $this->getString(self::KEY_NAME);
    }
}
```

---

### Default Data

**Rule**: All Type classes must implement `getDefaultData()` with initial values

**Reason**: Ensures all keys exist, prevents undefined index errors

**Impact**:
- Constructor merges default data with provided data
- All keys have at least empty/default values
- Safe to call getters without existence checks

**Example**:
```php
class ShipType extends BaseComponentType
{
    protected function getDefaultData() : array
    {
        return array(
            self::KEY_NAME => '',
            self::KEY_OWNER => '',
            self::KEY_STATE => self::STATE_NORMAL,
            self::KEY_CODE => '',
            // ... more keys
        );
    }
}
```

---

### Immutable IDs

**Rule**: `componentID` and `connectionID` are **immutable** after construction

**Reason**: These IDs are used for lookups and references

**Impact**:
- Set once in constructor
- No setters provided
- Used to build `uniqueID`

**Example**:
```php
class BaseComponentType
{
    public function __construct(Collections $collections, string $connectionID, string $componentID)
    {
        // IDs set here, never changed
        parent::__construct(array(
            self::KEY_CONNECTION_ID => $connectionID,
            self::KEY_COMPONENT_ID => $componentID,
            // ...
        ));
    }
    
    // No setComponentID() or setConnectionID() methods
}
```

---

## UI Architecture

### Inheritance from x4-core

**Rule**: All UI classes must extend base classes from `mistralys/x4-core`

**Reason**: Framework provides routing, rendering, component system

**Impact**:
- Cannot use alternative UI frameworks
- Must follow x4-core patterns for pages, components
- Inherit abstract methods that must be implemented

**Base Classes** (from x4-core):
- `X4Application` - Application entry point
- `UserInterface` - Routing and rendering manager
- `BasePage` - Single page base
- `BasePageWithNav` - Page with sub-navigation
- UI components: `Button`, `Icon`, `DataGrid`, etc.

**Example**:
```php
use Mistralys\X4\UI\Page\BasePage;

class SavesList extends MainPage // which extends Page -> BasePage
{
    // Must implement abstract methods from BasePage
    public function getURLName() : string { return 'SavesList'; }
    public function getTitle() : string { return 'Savegames'; }
    protected function renderContent() : void { /* render HTML */ }
}
```

---

### Page Registration

**Rule**: All pages must be registered in `SaveViewer::registerPages()`

**Reason**: Framework requires explicit page registration for routing

**Impact**:
- New pages won't be accessible until registered
- URL name must be unique
- Registration maps URL to class

**Example**:
```php
class SaveViewer extends X4Application
{
    public function registerPages(UserInterface $ui) : void
    {
        $ui->registerPage(SavesList::URL_NAME, SavesList::class);
        $ui->registerPage(ViewSave::URL_NAME, ViewSave::class);
        // ... more pages
    }
}
```

---

### URL Generation

**Rule**: Never hardcode URLs, always use getter methods

**Reason**: Ensures correct routing, parameter encoding

**Impact**:
- Pages provide `getURL*()` methods
- URLs built with proper query parameters
- Changes to routing don't break links

**Example**:
```php
// BAD: Hardcoded URL
$url = '/ViewSave?save=quicksave-12345';

// GOOD: Use URL generator
$url = $save->getURLView();
```

---

## Monitor System

### ReactPHP Event Loop

**Rule**: Monitors must use ReactPHP event loop for server/timer functionality

**Reason**: Enables non-blocking HTTP server and periodic ticks

**Impact**:
- Monitors run in infinite loop (`Loop::run()`)
- Tick-based polling, not filesystem watching
- Must run from command line (not web server)

**Example**:
```php
class BaseMonitor
{
    public function start() : void
    {
        $this->loop = Loop::get();
        
        // Set up timer for ticks
        $this->loop->addPeriodicTimer($this->tickSize, function() {
            $this->handleTick();
        });
        
        // Start event loop (blocks forever)
        $this->loop->run();
    }
}
```

---

### CLI-Only Execution

**Rule**: Monitors can **only** run from command line

**Reason**: Long-running processes, event loop incompatible with web requests

**Impact**:
- Constructor checks `PHP_SAPI === 'cli'`
- Throws exception if run from web server
- Must use `bin/run-monitor` or `bin/run-ui`

**Example**:
```php
class BaseMonitor
{
    public function __construct()
    {
        if (PHP_SAPI !== 'cli') {
            throw new SaveViewerException(
                'The monitor can only be run from the command line.',
                '',
                self::ERROR_NOT_COMMAND_LINE
            );
        }
    }
}
```

---

### Output Abstraction

**Rule**: Monitor output must use `MonitorOutputInterface` abstraction

**Reason**: Enables multiple output formats (console, NDJSON)

**Impact**:
- Don't use `echo` or `print` directly
- Use `$this->log()`, `$this->notify()`
- Output format determined by concrete implementation

**Example**:
```php
class X4Monitor extends BaseMonitor
{
    protected function _handleTick() : void
    {
        $this->log('Processing tick %s', $this->getTickCounter());
        $this->notify('SAVE_DETECTED', ['name' => $saveName]);
    }
}
```

---

### NDJSON Protocol

**Rule**: When using `--json` flag, output must be valid NDJSON

**Specification**: See [ndjson-interface.md](./ndjson-interface.md)

**Impact**:
- One JSON object per line
- No pretty-printing
- Every message has `type` and `timestamp`
- Newline-separated (PHP_EOL)

**Example**:
```json
{"type":"event","timestamp":"2026-01-29T10:30:00+00:00","name":"MONITOR_STARTED","payload":{}}
{"type":"tick","timestamp":"2026-01-29T10:30:05+00:00","counter":1}
```

---

## Configuration Management

### Singleton Pattern

**Rule**: `Config` class uses **singleton** pattern with static methods

**Reason**: Single source of truth, loaded once

**Impact**:
- No instances created manually
- Access via `Config::get()`, `Config::getString()`, etc.
- Automatically loads on first access

**Example**:
```php
// BAD: Don't instantiate
$config = new Config(); // Constructor is private

// GOOD: Use static methods
$host = Config::getViewerHost();
$port = Config::getViewerPort();
```

---

### Configuration File

**Rule**: Configuration must be in `config.json` (created from `config.dist.json`)

**Reason**: User-specific settings, gitignored

**Impact**:
- `config.dist.json` is template (committed to git)
- User copies to `config.json` and customizes
- `config.json` is gitignored
- Falls back to dist if user config missing

**Required Keys**:
```json
{
  "gameFolder": "C:\\Users\\...\\X4\\11111111",
  "viewerHost": "localhost",
  "viewerPort": 9494,
  "autoBackupEnabled": true,
  "keepXMLFiles": false,
  "loggingEnabled": false
}
```

---

### Derived Paths

**Rule**: `savesFolder` and `storageFolder` are **derived** from `gameFolder` unless explicitly set

**Calculation**:
- `savesFolder` = `{gameFolder}/save/`
- `storageFolder` = Application decides (typically in project root or user-specified)

**Impact**:
- User only needs to set `gameFolder` in most cases
- Can override `savesFolder` and `storageFolder` if needed

---

## Error Handling

### Exception Types

**Rule**: Use `SaveViewerException` for application-specific errors

**Reason**: Distinguishes application errors from PHP/library errors

**Impact**:
- All custom exceptions extend `SaveViewerException`
- Use error codes for identification
- Include context in messages

**Example**:
```php
throw new SaveViewerException(
    'Savegame not found.',
    sprintf('Savegame with name [%s] was not found.', $name),
    self::ERROR_CANNOT_FIND_BY_NAME
);
```

---

### Error Constants

**Rule**: All error codes must be defined as class constants

**Naming**: `ERROR_{DESCRIPTION}` in SCREAMING_SNAKE_CASE

**Impact**:
- Self-documenting error codes
- Easy to search codebase
- PHPDoc can reference constants

**Example**:
```php
class SaveManager
{
    public const int ERROR_CANNOT_FIND_BY_NAME = 12125;
    public const int ERROR_CANNOT_FIND_BY_ID = 12126;
}
```

---

## Testing

### Test Base Classes

**Rule**: All tests must extend appropriate base class

**Base Classes**:
- `X4ParserTestCase` - For parser tests
- `X4LogCategoriesTestCase` - For log category tests

**Reason**: Provides common setup, fixtures, utilities

**Impact**:
- Don't extend PHPUnit's `TestCase` directly
- Use helper methods from base classes
- Consistent test environment

---

### Fixtures

**Rule**: Test save files stored in `tests/files/`

**Structure**:
```
tests/files/
  ├── save-files/    # Full savegame files
  └── test-saves/    # Smaller test data
```

**Impact**:
- Don't use production saves in tests
- Keep test files small
- Commit to repository

---

## Code Style

### Namespace Structure

**Rule**: All classes in `Mistralys\X4\SaveViewer\` namespace

**Structure**: Mirrors directory structure

**Example**:
```
src/X4/SaveViewer/Data/SaveManager.php
→ namespace Mistralys\X4\SaveViewer\Data;
```

---

### Class Naming

**Rule**: Classes use **PascalCase**, files match class names

**Example**:
- Class: `SaveManager`
- File: `SaveManager.php`

---

### Constant Naming

**Rule**: Constants use **SCREAMING_SNAKE_CASE**

**Example**:
```php
public const string KEY_SAVE_NAME = 'save-name';
public const int ERROR_NOT_FOUND = 12345;
```

---

### Method Naming

**Rule**: Methods use **camelCase**

**Conventions**:
- Getters: `getName()`, `getShips()`
- Setters: `setName()`, `addShip()`
- Boolean: `isWreck()`, `hasData()`, `canProcess()`
- Factory: `create()`, `createFromConfig()`

---

### Fluent Interface

**Rule**: Setters and configuration methods should return `self`

**Reason**: Enables method chaining

**Example**:
```php
$parser = SaveParser::create($saveFile)
    ->optionAutoBackup(true)
    ->optionKeepXML(false)
    ->unpack();
```

---

## Performance Guidelines

### Memory Management

**Rule**: Avoid loading large files entirely into memory

**Strategies**:
- Use XMLReader for streaming
- Process in chunks
- Extract fragments before DOM parsing

---

### Lazy Loading

**Rule**: Data readers should parse JSON on-demand, not in constructor

**Reason**: UI may not need all data

**Example**:
```php
class SaveReader
{
    private ?Blueprints $blueprints = null;
    
    public function getBlueprints() : Blueprints
    {
        if (!isset($this->blueprints)) {
            $this->blueprints = new Blueprints($this); // Parsed here
        }
        return $this->blueprints;
    }
}
```

---

### Caching

**Rule**: SaveSelector caches file lists in private property

**Reason**: Avoid repeated filesystem scans

**Example**:
```php
class SaveSelector
{
    private ?array $cachedFiles = null;
    
    public function getSaveGames() : array
    {
        if ($this->cachedFiles !== null) {
            return $this->cachedFiles;
        }
        
        // Scan filesystem
        $this->cachedFiles = $this->scanSaves();
        return $this->cachedFiles;
    }
}
```

---

## Summary of Key Constraints

| Constraint | Rationale | Impact |
|------------|-----------|--------|
| **Synchronous I/O** | PHP limitation | All file ops block |
| **Two-stage XML parsing** | Memory efficiency | Cannot use DOM on full file |
| **JSON storage** | Simplicity, portability | No database needed |
| **Collections per parse** | Isolation | Fresh instance each parse |
| **uniqueID references** | Serialization | No object references in data |
| **x4-core inheritance** | Framework dependency | Must follow framework patterns |
| **ReactPHP for servers** | Event-driven architecture | Monitors CLI-only |
| **Config singleton** | Central configuration | Static access only |
| **Strict typing** | Type safety | No implicit conversions |
| **XML cleanup** | Disk space | Fragments deleted by default |

---

## Deprecations & Future Considerations

Currently there are no deprecated features, but future architectural changes should consider:

1. **Async file I/O**: PHP 8.1+ fibers could enable async file operations
2. **Database option**: For larger datasets, optional SQLite/MySQL storage
3. **WebSocket support**: For real-time monitor updates in UI
4. **Plugin system**: Allow custom data processors and readers
5. **API endpoints**: RESTful API for third-party integration

Any changes to these constraints must be documented in this file and communicated to all developers/AI agents.

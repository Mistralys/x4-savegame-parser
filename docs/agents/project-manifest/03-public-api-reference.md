# Public API Reference

This document lists **method signatures only** for all public classes and interfaces. Implementation details are omitted.

---

## Core Services

### SaveManager

**Location**: `src/X4/SaveViewer/Data/SaveManager.php`

**Purpose**: Manages access to savegame files (active and archived).

```php
class SaveManager
{
    // Constants
    public const int ERROR_CANNOT_FIND_BY_NAME = 12125;
    public const int ERROR_CANNOT_FIND_BY_ID = 12126;

    // Constructor
    public function __construct(SaveSelector $selector);

    // Factory methods
    public static function create($savesFolder, $storageFolder) : SaveManager;
    public static function createFromConfig() : SaveManager;

    // Folder access
    public function getSavesFolder() : FolderInfo;
    public function getStorageFolder() : FolderInfo;

    // Savegame retrieval
    public function getSaves() : array; // Returns MainSave[]
    public function getArchivedSaves() : array; // Returns ArchivedSave[]
    public function getSaveByName(string $name) : MainSave;
    public function getSaveNames() : array; // Returns string[]
    
    // Queries
    public function nameExists(string $name) : bool;
    public function idExists(string $id) : bool;
    public function getByID(string $id) : BaseSaveFile;
    public function getCurrentSave() : ?MainSave;
    
    // URL generation (for UI)
    public function getURLSavesList() : string;
    public function getURLSavesArchive() : string;
    public function getURLConstructionPlans() : string;
}
```

---

### SaveParser

**Location**: `src/X4/SaveViewer/SaveParser.php`

**Purpose**: Main parser that extracts and processes XML savegame data.

```php
class SaveParser extends BaseXMLParser
{
    // Constants
    public const int ERROR_SAVEGAME_MUST_BE_UNZIPPED = 137401;
    public const int ERROR_CANNOT_BACKUP_WITHOUT_SAVE = 137402;

    // Factory methods
    public static function create($saveFile) : SaveParser; // Accepts SaveGameFile or MainSave
    public static function createFromAnalysis(FileAnalysis $analysis) : SaveParser;
    public static function createFromMonitorConfig(MainSave $save) : SaveParser;

    // Constructor
    public function __construct(FileAnalysis $analysis, string $xmlFilePath, ?SaveGameFile $saveFile);

    // Configuration
    public function optionAutoBackup(bool $enabled = true) : self;
    public function optionKeepXML(bool $keep = true) : self;

    // Collections access
    public function getCollections() : Collections;

    // Execution
    public function unpack() : self; // Full extraction pipeline
    public function processFile() : self;
    public function postProcessFragments() : self;
    public function cleanUp() : void;
    public function createBackup() : self;
}
```

---

### SaveReader

**Location**: `src/X4/SaveViewer/Data/SaveReader.php`

**Purpose**: Facade for reading parsed savegame data.

```php
class SaveReader
{
    // Constructor
    public function __construct(BaseSaveFile $saveFile);

    // Core access
    public function getSaveFile() : BaseSaveFile;
    public function getCollections() : Collections;

    // Specialized readers (lazy-loaded)
    public function getSaveInfo() : SaveInfo;
    public function getPlayer() : PlayerInfo;
    public function getBlueprints() : Blueprints;
    public function getStatistics() : Statistics;
    public function getLog() : Log;
    public function getFactions() : Factions;
    public function getInventory() : Inventory;
    public function getKhaakStations() : KhaakStationsReader;
    public function getShipLosses() : ShipLossesReader;
    public function getGameTime() : GameTime;

    // Raw data access
    public function getRawData(string $dataID) : array;
    public function dataExists(string $dataID) : bool;
    public function getDataPath(string $dataID) : string;
}
```

---

### Config

**Location**: `src/X4/SaveViewer/Config/Config.php`

**Purpose**: Singleton configuration manager.

```php
class Config
{
    // Configuration keys
    public const string KEY_SAVES_FOLDER = 'savesFolder';
    public const string KEY_GAME_FOLDER = 'gameFolder';
    public const string KEY_STORAGE_FOLDER = 'storageFolder';
    public const string KEY_VIEWER_HOST = 'viewerHost';
    public const string KEY_VIEWER_PORT = 'viewerPort';
    public const string KEY_AUTO_BACKUP_ENABLED = 'autoBackupEnabled';
    public const string KEY_KEEP_XML_FILES = 'keepXMLFiles';
    public const string KEY_LOGGING_ENABLED = 'loggingEnabled';

    // Loading
    public static function loadFromFile(string $path) : void;
    public static function ensureLoaded(?string $path = null) : void;

    // Generic access
    public static function get(string $key, $default = null);
    public static function getString(string $key, string $default = '') : string;
    public static function getInt(string $key, int $default = 0) : int;
    public static function getBool(string $key, bool $default = false) : bool;

    // Convenience methods
    public static function getSavesFolder() : FolderInfo;
    public static function getStorageFolder() : FolderInfo;
    public static function getGameFolder() : FolderInfo;
    public static function getViewerHost() : string;
    public static function getViewerPort() : int;
    public static function isAutoBackupEnabled() : bool;
    public static function isKeepXMLFiles() : bool;
    public static function isLoggingEnabled() : bool;
}
```

---

### SaveSelector

**Location**: `src/X4/SaveViewer/Parser/SaveSelector.php`

**Purpose**: Finds and selects savegame files from disk.

```php
class SaveSelector implements DebuggableInterface
{
    // Constants
    public const int ERROR_MOST_RECENT_FILE_NOT_FOUND = 136001;
    public const int ERROR_SAVEGAME_NOT_FOUND = 136002;
    public const int ERROR_CANNOT_ACCESS_SAVES_FOLDER = 136003;
    public const int ERROR_CANNOT_ACCESS_SAVE_FILE = 136004;
    public const string TEMP_SAVE_NAME = 'temp_save';

    // Constructor
    public function __construct(FolderInfo $savesFolder, FolderInfo $storageFolder);

    // Factory methods
    public static function create($savesFolder, $storageFolder) : SaveSelector;
    public static function createFromConfig() : SaveSelector;

    // Folder access
    public function getSavesFolder() : FolderInfo;
    public function getStorageFolder() : FolderInfo;

    // File discovery
    public function getMostRecent() : ?SaveGameFile;
    public function getSaveGames() : array; // Returns SaveGameFile[]
    public function getSaveGameByName(string $name) : SaveGameFile;
    public function getArchivedSaves() : array; // Returns FileAnalysis[]
    
    // Queries
    public function nameExists(string $name) : bool;
}
```

---

### FileAnalysis

**Location**: `src/X4/SaveViewer/Parser/FileAnalysis.php`

**Purpose**: Metadata and status tracking for a parsed savegame.

```php
class FileAnalysis extends ArrayDataCollection
{
    // Constants
    public const string ANALYSIS_FILE_NAME = 'analysis.json';
    public const string BACKUP_ARCHIVE_FILE_NAME = 'backup.gz';
    public const string KEY_PROCESS_DATE = 'process-dates';
    public const string KEY_SAVE_DATE = 'save-date';
    public const string KEY_SAVE_ID = 'save-id';
    public const string KEY_SAVE_NAME = 'save-name';

    // Factory methods
    public static function createFromSaveFile(SaveGameFile $file) : FileAnalysis;
    public static function createFromDataFile($analysisFile) : FileAnalysis;

    // Persistence
    public function save() : self;
    public function exists() : bool;

    // File/folder access
    public function getStorageFolder() : FolderInfo;
    public function getStorageFile() : JSONFile;
    public function getXMLFolder() : FolderInfo;
    public function getJSONFolder() : FolderInfo;
    public function getBackupFile() : FileInfo;

    // Metadata
    public function getSaveID() : string;
    public function getSaveName() : string;
    public function getDateModified() : DateTime;
    public function hasSaveID() : bool;
    public function hasXML() : bool;
    
    // Processing tracking
    public function markProcessed() : self;
    public function getLastProcessDate() : ?DateTime;
    public function getAllProcessDates() : array;
}
```

---

### SaveGameFile

**Location**: `src/X4/SaveViewer/Parser/SaveSelector/SaveGameFile.php`

**Purpose**: Wrapper for a savegame file (.xml.gz or .xml).

```php
class SaveGameFile
{
    // Constants
    public const string FILE_MODE_ZIP = 'zip';
    public const string FILE_MODE_XML = 'xml';
    public const string STORAGE_FOLDER_DATE_FORMAT = 'YmdHis';

    // Constructor
    public function __construct(FolderInfo $outputFolder, ?FileInfo $zipFile, ?FileInfo $xmlFile);

    // File mode
    public function getFileMode() : string;
    public function isGZipped() : bool;
    public function isUnzipped() : bool;

    // Identification
    public function getID() : string;
    public function getBaseName() : string;
    public function getPath() : string;

    // File operations
    public function unzip() : self;
    public function deleteXML() : self;
    
    // File access
    public function getZipFile() : FileInfo;
    public function getXMLFile() : FileInfo;
    public function requireXMLFile() : FileInfo;
    public function getReferenceFile() : FileInfo;
    
    // Metadata
    public function getDateModified() : DateTime;
    public function getStorageFolder() : FolderInfo;
    public function getAnalysis() : FileAnalysis;
}
```

---

### BaseSaveFile

**Location**: `src/X4/SaveViewer/Data/SaveManager/BaseSaveFile.php`

**Purpose**: Abstract base for MainSave and ArchivedSave.

```php
abstract class BaseSaveFile
{
    // Constants
    public const int ERROR_BACKUP_INVALID_DATA = 89601;
    public const string PARAM_SAVE_ID = 'save';

    // Constructor
    public function __construct(SaveManager $manager, FileAnalysis $analysis);

    // Identification
    public function getSaveID() : string;
    public function getSaveName() : string;
    public function getDateModified() : DateTime;
    
    // Abstract methods
    abstract public function getTypeLabel() : string;
    abstract public function getSaveFile() : SaveGameFile;

    // Status queries
    public function hasData() : bool;
    public function isUnpacked() : bool;
    public function hasBackup() : bool;
    public function hasXML() : bool;

    // Data access
    public function getDataReader() : SaveReader;
    public function getAnalysis() : FileAnalysis;
    public function getStorageFolder() : FolderInfo;
    public function getDataFolder() : FolderInfo;
    
    // URL generation
    public function getURLView() : string;
    public function getURLUnpack() : string;
    public function getURLBackup() : string;
    public function getURLDeleteArchive() : string;
}
```

---

## Collections System

### Collections (Hub)

**Location**: `src/X4/SaveViewer/Parser/Collections.php`

**Purpose**: Central registry of all typed collections.

```php
class Collections
{
    // Constants
    public const int ERROR_INVALID_UNIQUE_ID = 135001;
    public const int ERROR_NO_COMPONENT_FOUND_BY_ID = 135002;

    // Constructor
    public function __construct(FolderInfo $outputFolder);

    // Folder access
    public function getOutputFolder() : FolderInfo;

    // Collection accessors
    public function celestials() : CelestialsCollection;
    public function clusters() : ClustersCollection;
    public function eventLog() : EventLogCollection;
    public function people() : PeopleCollection;
    public function player() : PlayerCollection;
    public function regions() : RegionsCollection;
    public function sectors() : SectorsCollection;
    public function ships() : ShipsCollection;
    public function stations() : StationsCollection;
    public function zones() : ZonesCollection;

    // Component lookup
    public function getByUniqueID(string $uniqueID) : ?BaseComponentType;
    public function requireByUniqueID(string $uniqueID) : BaseComponentType;

    // Persistence
    public function save() : self;
}
```

---

### BaseCollection (Abstract)

**Location**: `src/X4/SaveViewer/Parser/Collections/BaseCollection.php`

**Purpose**: Base class for all typed collections.

```php
abstract class BaseCollection
{
    // Constructor
    public function __construct(Collections $collections);

    // Abstract methods
    abstract public function getCollectionID() : string;

    // Component management
    public function getComponentByID(string $typeID, string $componentID) : ?BaseComponentType;
    public function getComponentsByType(string $type) : array; // Returns BaseComponentType[]
    protected function addComponent(ComponentInterface $component) : void;

    // Persistence
    public function save() : self;
    public function loadData() : array;
    public function toArray() : array;
    public function getFilePath() : string;
}
```

---

### Typed Collections

All typed collections extend `BaseCollection` and follow this pattern:

```php
// ShipsCollection
class ShipsCollection extends BaseCollection
{
    public const COLLECTION_ID = 'ships';
    
    public function getCollectionID() : string;
    public function createShip(ShipContainerInterface $container, string $connectionID, string $componentID) : ShipType;
}

// StationsCollection
class StationsCollection extends BaseCollection
{
    public const COLLECTION_ID = 'stations';
    
    public function getCollectionID() : string;
    public function createStation(ZoneType $zone, string $connectionID, string $componentID) : StationType;
}

// PeopleCollection
class PeopleCollection extends BaseCollection
{
    public const COLLECTION_ID = 'people';
    
    public function getCollectionID() : string;
    public function createPerson(PersonContainerInterface $container, string $name = '') : PersonType;
}

// Similar patterns for:
// - CelestialsCollection
// - ClustersCollection
// - EventLogCollection
// - PlayerCollection
// - RegionsCollection
// - SectorsCollection
// - ZonesCollection
```

---

## Type Models (Data Classes)

### BaseComponentType (Abstract)

**Location**: `src/X4/SaveViewer/Parser/Types/BaseComponentType.php`

**Purpose**: Base class for all game entity types. Extends `ArrayDataCollection`.

```php
abstract class BaseComponentType extends ArrayDataCollection implements ComponentInterface
{
    // Keys
    public const KEY_CONNECTION_ID = 'connectionID';
    public const KEY_COMPONENT_ID = 'componentID';
    public const KEY_PARENT_COMPONENT = 'parentComponent';

    // Constants
    public const ERROR_EMPTY_COMPONENT_ID = 135101;

    // Constructor
    public function __construct(Collections $collections, string $connectionID, string $componentID);

    // Abstract methods
    abstract protected function getDefaultData() : array;
    abstract public function getTypeID() : string;

    // Collections access
    public function getCollections() : Collections;

    // Identification
    public function getComponentID() : string;
    public function getConnectionID() : string;
    public function getUniqueID() : string;

    // Relationships
    public function getParentComponent() : ?BaseComponentType;
    protected function setParentComponent(ComponentInterface $component) : self;
    public function getComponentsByKey(string $key) : array; // Returns BaseComponentType[]
    protected function setKeyComponent(string $key, BaseComponentType $component) : self;

    // Serialization
    public function toArray() : array;
}
```

---

### Type Classes Summary

All Type classes extend `BaseComponentType` and implement specific interfaces/traits as needed.

| Type Class | Type ID | Key Properties | Implements/Uses Traits |
|------------|---------|----------------|------------------------|
| **ShipType** | `'ship'` | name, owner, state, class, size, pilot, hull | PersonContainer, ShipContainer, PlayerContainer |
| **StationType** | `'station'` | name, owner, code, class, macro, state | PersonContainer, ShipContainer |
| **PersonType** | `'person'` | name, role, race, gender, owner, cover | - |
| **PlayerType** | `'player'` | name, money, entityID | - |
| **SectorType** | `'sector'` | name, owner | ShipContainer |
| **ZoneType** | `'zone'` | code | ShipContainer |
| **ClusterType** | `'cluster'` | connectionID | - |
| **RegionType** | `'region'` | connectionID | - |
| **CelestialBodyType** | `'celestial'` | name, class, owner | - |
| **LogEntryType** | `'log-entry'` | time, category, title, text | - |

**Common Methods** (examples from ShipType, applicable to others):

```php
class ShipType extends BaseComponentType implements PersonContainerInterface, ShipContainerInterface, PlayerContainerInterface
{
    public const string TYPE_ID = 'ship';
    
    // Type-specific keys
    public const string KEY_NAME = 'name';
    public const string KEY_OWNER = 'owner';
    public const string KEY_STATE = 'state';
    public const string KEY_CODE = 'code';
    public const string KEY_CLASS = 'class';
    // ... more keys

    // Constructor
    public function __construct(ShipContainerInterface $parentComponent, string $connectionID, string $componentID);

    // Accessors (vary by type)
    public function getName() : string;
    public function getOwner() : string;
    public function getCode() : string;
    public function getState() : string;
    public function getLabel() : string;
    
    // Mutators
    public function setName(string $name) : self;
    public function setOwner(string $owner) : self;
    public function setState(string $state) : self;
    
    // Status checks
    public function isWreck() : bool;
    public function isPilotPlayer() : bool;
    
    // Container trait methods (if applicable)
    public function addPerson(PersonType $person) : self;
    public function addShip(ShipType $ship) : self;
    public function registerPlayer(PlayerType $player) : self;
}
```

---

## Data Readers

### Blueprints

**Location**: `src/X4/SaveViewer/Data/SaveReader/Blueprints.php`

```php
class Blueprints
{
    public function __construct(SaveReader $reader);
    
    public function getAll() : array;
    public function getOwned() : array;
    public function getUnowned() : array;
    public function getByMacro(string $macro) : array;
    public function countOwned() : int;
    public function countUnowned() : int;
    public function getURLList(BaseSaveFile $save, string $mode = 'owned') : string;
}
```

---

### Factions

**Location**: `src/X4/SaveViewer/Data/SaveReader/Factions.php`

```php
class Factions
{
    public function __construct(SaveReader $reader);
    
    public function getAll() : array;
    public function getByName(string $name);
    public function nameExists(string $name) : bool;
    public function getRelations(string $factionName) : array;
    public function getURLList(BaseSaveFile $save) : string;
    public function getURLRelations(BaseSaveFile $save, string $factionName) : string;
}
```

---

### Statistics

**Location**: `src/X4/SaveViewer/Data/SaveReader/Statistics.php`

```php
class Statistics
{
    public function __construct(SaveReader $reader);
    
    public function getStats() : array;
    public function getStat(string $id, $default = null);
    public function exists() : bool;
}
```

---

### Log

**Location**: `src/X4/SaveViewer/Data/SaveReader/Log.php`

```php
class Log
{
    public function __construct(SaveReader $reader);
    
    public function getEntries() : array;
    public function getCategories() : LogCategories;
    public function getEntriesByCategory(string $categoryID) : array;
    public function countEntries() : int;
    public function exists() : bool;
    public function getURLView(BaseSaveFile $save) : string;
}
```

---

### Inventory

**Location**: `src/X4/SaveViewer/Data/SaveReader/Inventory.php`

```php
class Inventory
{
    public function __construct(SaveReader $reader);
    
    public function getWares() : array; // Returns Ware[]
    public function countWares() : int;
    public function getTotal() : int;
    public function exists() : bool;
}
```

---

### PlayerInfo

**Location**: `src/X4/SaveViewer/Data/SaveReader/PlayerInfo.php`

```php
class PlayerInfo
{
    public function __construct(SaveReader $reader);
    
    public function getName() : string;
    public function getMoney() : int;
    public function getEntityID() : string;
    public function exists() : bool;
}
```

---

### SaveInfo

**Location**: `src/X4/SaveViewer/Data/SaveReader/SaveInfo.php`

```php
class SaveInfo
{
    public function __construct(SaveReader $reader);
    
    public function getSaveName() : string;
    public function getSaveDate() : string;
    public function getGameCode() : string;
    public function getGameGUID() : string;
    public function getStartTime() : string;
    public function exists() : bool;
}
```

---

### KhaakStationsReader

**Location**: `src/X4/SaveViewer/Data/SaveReader/KhaakStationsReader.php`

```php
class KhaakStationsReader
{
    public function __construct(SaveReader $reader);
    
    public function getStations() : array;
    public function countStations() : int;
    public function exists() : bool;
    public function getURLView(BaseSaveFile $save) : string;
}
```

---

### ShipLossesReader

**Location**: `src/X4/SaveViewer/Data/SaveReader/ShipLossesReader.php`

```php
class ShipLossesReader
{
    public function __construct(SaveReader $reader);
    
    public function getLosses() : array;
    public function countLosses() : int;
    public function exists() : bool;
    public function getURLView(BaseSaveFile $save) : string;
}
```

---

## Monitor System

### BaseMonitor (Abstract)

**Location**: `src/X4/SaveViewer/Monitor/BaseMonitor.php`

```php
abstract class BaseMonitor
{
    // Constants
    public const ERROR_NOT_COMMAND_LINE = 136301;
    public const ERROR_CANNOT_START_LOOP = 136302;

    // Constructor
    public function __construct();

    // Configuration
    public function getTickCounter() : int;
    public function getTickSize() : int;
    public function setOutput(MonitorOutputInterface $output) : self;

    // Execution
    public function start() : void;
    abstract protected function setup() : void;
    abstract protected function _handleTick() : void;

    // Logging & notifications
    protected function log(string $message, ...$args) : void;
    protected function logHeader(string $message, ...$args) : void;
    protected function notify(string $eventName, array $payload = []) : void;
}
```

---

### X4Monitor

**Location**: `src/X4/SaveViewer/Monitor/X4Monitor.php`

**Purpose**: Monitors savegame folder and auto-extracts new saves.

```php
class X4Monitor extends BaseMonitor
{
    // Configuration
    public function optionLogging(bool $enabled) : self;
    public function optionAutoBackup(bool $enabled) : self;
    public function optionKeepXML(bool $keep) : self;

    // Overrides
    protected function setup() : void;
    protected function _handleTick() : void;
}
```

---

### X4Server

**Location**: `src/X4/SaveViewer/Monitor/X4Server.php`

**Purpose**: HTTP server for the web UI.

```php
class X4Server extends BaseMonitor
{
    // Constants
    public const ALLOWED_EXTENSIONS = ['js', 'html', 'css', 'md', 'txt', 'json', 'map', 'otf', 'woff', 'woff2', 'eot', 'ttf', 'svg', 'jpg', 'png', 'ico'];

    // Request handling
    public function handleRequest(ServerRequestInterface $request) : Response;

    // Overrides
    protected function setup() : void;
    protected function _handleTick() : void;
}
```

---

### MonitorOutputInterface

**Location**: `src/X4/SaveViewer/Monitor/Output/MonitorOutputInterface.php`

```php
interface MonitorOutputInterface
{
    public function write(string $message) : void;
    public function writeHeader(string $message) : void;
    public function notify(string $eventName, array $payload = []) : void;
    public function setLoggingEnabled(bool $enabled) : void;
    public function isLoggingEnabled() : bool;
}
```

---

### ConsoleOutput

**Location**: `src/X4/SaveViewer/Monitor/Output/ConsoleOutput.php`

```php
class ConsoleOutput implements MonitorOutputInterface
{
    public function write(string $message) : void;
    public function writeHeader(string $message) : void;
    public function notify(string $eventName, array $payload = []) : void;
    public function setLoggingEnabled(bool $enabled) : void;
    public function isLoggingEnabled() : bool;
}
```

---

### JsonOutput

**Location**: `src/X4/SaveViewer/Monitor/Output/JsonOutput.php`

**Purpose**: NDJSON output for machine consumption.

```php
class JsonOutput implements MonitorOutputInterface
{
    public function write(string $message) : void;
    public function writeHeader(string $message) : void;
    public function notify(string $eventName, array $payload = []) : void;
    public function setLoggingEnabled(bool $enabled) : void;
    public function isLoggingEnabled() : bool;
}
```

---

## CLI System

### CLIHandler

**Location**: `src/X4/SaveViewer/CLI/CLIHandler.php`

**Purpose**: Handles command-line extraction commands.

```php
class CLIHandler
{
    // Commands
    public const COMMAND_EXTRACT = 'extract';
    public const COMMAND_HELP = 'help';
    public const COMMAND_LIST = 'list';
    public const COMMAND_EXTRACT_ALL = 'extract-all';
    public const COMMAND_KEEP_XML = 'keep-xml';
    public const COMMAND_NO_BACKUP = 'no-backup';
    public const COMMAND_REBUILD_JSON = 'rebuild-json';
    public const COMMAND_LIST_ARCHIVED = 'list-archived';

    // Constructor
    public function __construct(SaveManager $manager);

    // Factory methods
    public static function create(SaveManager $manager) : CLIHandler;
    public static function createFromConfig() : CLIHandler;

    // Execution
    public function handle() : void;
}
```

---

## UI System

### SaveViewer (Application)

**Location**: `src/X4/SaveViewer/SaveViewer.php`

**Purpose**: Main application class (extends `X4Application` from `mistralys/x4-core`).

```php
class SaveViewer extends X4Application
{
    // Constructor
    public function __construct();

    // Application methods (from X4Application)
    public function getTitle() : string;
    public function getVersion() : string;
    public function getDefaultPageID() : ?string;
    public function registerPages(UserInterface $ui) : void;

    // Save management
    public function getSaveManager() : SaveManager;
    public function getConstructionPlans() : ConstructionPlansParser;
}
```

---

### Page Hierarchy

**Base Classes**:

```php
// From mistralys/x4-core (inherited)
abstract class BasePage
{
    abstract public function getURLName() : string;
    abstract public function getTitle() : string;
    abstract public function getSubtitle() : string;
    abstract public function getAbstract() : string;
    abstract protected function renderContent() : void;
    
    protected function redirect(string $url) : void;
}

abstract class BasePageWithNav extends BasePage
{
    abstract public function getNavTitle() : string;
    abstract public function getDefaultSubPageID() : string;
    abstract protected function initSubPages() : void;
    abstract protected function getURLParams() : array;
}
```

**SaveViewer Extensions**:

```php
// src/X4/SaveViewer/UI/Page.php
abstract class Page extends BasePage
{
    protected SaveManager $manager;
    
    protected function init() : void;
    protected function requireSave() : BaseSaveFile;
}

// src/X4/SaveViewer/UI/MainPage.php
abstract class MainPage extends Page
{
    public function getNavItems() : array;
    protected function preRender() : void;
    protected function getURLParams() : array;
}

// src/X4/SaveViewer/UI/PageWithNav.php
abstract class PageWithNav extends BasePageWithNav
{
    protected SaveManager $saveManager;
    
    protected function requireSave() : BaseSaveFile;
}
```

---

### Main Pages

```php
// SavesList
class SavesList extends MainPage
{
    public const URL_NAME = 'SavesList';
    
    public function getURLName() : string;
    public function getTitle() : string;
    public function getSubtitle() : string;
    public function getAbstract() : string;
    protected function renderContent() : void;
}

// ArchivedSavesPage
class ArchivedSavesPage extends MainPage
{
    public const URL_NAME = 'ArchivedSaves';
    
    public function getURLName() : string;
    public function getTitle() : string;
    public function getSubtitle() : string;
    public function getAbstract() : string;
    protected function renderContent() : void;
}

// ViewSave (with sub-pages)
class ViewSave extends PageWithNav
{
    public const URL_NAME = 'ViewSave';
    
    public function getURLName() : string;
    public function getTitle() : string;
    public function getSubtitle() : string;
    public function getAbstract() : string;
    public function getNavTitle() : string;
    public function getDefaultSubPageID() : string;
    protected function initSubPages() : void;
    protected function preRender() : void;
    protected function getURLParams() : array;
}

// Similar structure for:
// - ConstructionPlansPage
// - ViewPlanPage
// - CreateBackup
// - UnpackSave
```

---

### View Save Sub-Pages

All sub-pages extend `BaseViewSaveSubPage`:

```php
abstract class BaseViewSaveSubPage extends ViewerSubPage
{
    protected BaseSaveFile $save;
    protected SaveReader $reader;
    
    abstract public function getURLName() : string;
    abstract public function isInSubnav() : bool;
}

// Concrete sub-pages:
// - Home (properties overview)
// - Statistics (game stats)
// - BlueprintsPage (owned/unowned blueprints)
// - EventLogPage (event log with categories)
// - Factions (faction list)
// - FactionRelations (specific faction relations)
// - Inventory (player inventory)
// - KhaakOverviewPage (Khaa'k stations)
// - Losses (ship losses)
// - Backup (create backup)
// - DeleteArchivePage (delete archived save)
```

---

## Parser Components

### Fragment Parsers

```php
// BaseDOMFragment (abstract)
abstract class BaseDOMFragment extends BaseFragment
{
    abstract protected function parseDOM(DOMDocument $dom) : void;
    
    protected function parseElementAttributes(DOMElement $element) : array;
}

// Concrete fragments:
class ClusterConnectionFragment extends BaseDOMFragment
{
    public const TAG_PATH = 'savegame.universe.connections';
    protected function parseDOM(DOMDocument $dom) : void;
}

class EventLogFragment extends BaseDOMFragment
{
    public const TAG_PATH = 'savegame.log';
    public const SAVE_NAME = 'event-log';
    protected function parseDOM(DOMDocument $dom) : void;
}

class FactionsFragment extends BaseDOMFragment
{
    public const TAG_PATH = 'savegame.universe.factions';
    protected function parseDOM(DOMDocument $dom) : void;
}

class PlayerStatsFragment extends BaseDOMFragment
{
    public const TAG_PATH = 'savegame.stats';
    protected function parseDOM(DOMDocument $dom) : void;
}

class SaveInfoFragment extends BaseDOMFragment
{
    public const TAG_PATH = 'savegame';
    public const SAVE_NAME = 'info';
    protected function parseDOM(DOMDocument $dom) : void;
}
```

---

### Tag Parsers

```php
// Tag (abstract)
abstract class Tag
{
    abstract public function getTagPath() : string;
    abstract public function getSaveName() : string;
    
    public function tagOpened(string $line, int $number) : void;
    public function tagClosed(string $activePath, string $tagName, int $number) : void;
    public function childTagOpened(string $activePath, string $tagName, string $line, int $number) : void;
    protected function getAttributes(string $line) : array;
    public function getSavePath() : string;
}

// Concrete tags:
class LogTag extends Tag { /* Event log parsing */ }
class MessagesTag extends Tag { /* Messages parsing */ }
class FactionsTag extends Tag { /* Factions parsing */ }
class StatsTag extends Tag { /* Statistics parsing */ }
class PlayerComponentTag extends Tag { /* Player component parsing */ }
```

---

### Construction Plans

```php
class ConstructionPlansParser
{
    public function __construct(FileInfo $xmlFile);
    public static function createFromConfig() : ConstructionPlansParser;
    
    public function getPlans() : array; // Returns ConstructionPlan[]
    public function getPlan(string $id) : ?ConstructionPlan;
    public function getByRequest() : ?ConstructionPlan;
    public function save() : self;
    public function getURLList() : string;
}

class ConstructionPlan
{
    public function getID() : string;
    public function getLabel() : string;
    public function countElements() : int;
    public function countProductions() : int;
    public function getModules() : array; // Returns PlanModule[]
    public function getProductions() : array; // Returns PlanModule[]
    public function getHabitats() : array; // Returns PlanModule[]
    public function getByCategories(array $categoryIDs) : array;
    public function getURLView() : string;
}

class PlanModule
{
    public function getMacro() : string;
    public function getAmount() : int;
    public function getCategory() : string;
    public function isProduction() : bool;
}
```

---

## Interfaces & Traits

### Component Interfaces

```php
interface ComponentInterface
{
    public function getComponentID() : string;
    public function getConnectionID() : string;
    public function getTypeID() : string;
    public function getUniqueID() : string;
    public function getCollections() : Collections;
}

interface PersonContainerInterface extends ComponentInterface
{
    public const KEY_PERSONS = 'persons';
    public function addPerson(PersonType $person) : self;
}

interface ShipContainerInterface extends ComponentInterface
{
    public const KEY_SHIPS = 'ships';
    public function addShip(ShipType $ship) : self;
}

interface PlayerContainerInterface extends ComponentInterface
{
    public const KEY_NAME_PLAYER = 'player';
    public function registerPlayer(PlayerType $player) : self;
}
```

---

### Utility Interfaces

```php
interface DebuggableInterface
{
    public function getLogIdentifier() : string;
    public function enableLogging() : self;
    public function disableLogging() : self;
    public function isLoggingEnabled() : bool;
    public function setLoggingEnabled(bool $enabled) : self;
}
```

---

## Notes on External Dependencies

### From mistralys/x4-core

The following classes are **inherited** from the `mistralys/x4-core` package and are not defined in this codebase:

- `X4Application` - Base application class
- `UserInterface` - UI management and rendering
- `BasePage` - Page base class
- `BasePageWithNav` - Page with sub-navigation
- `Button`, `Icon`, `DataGrid`, `GridColumn` - UI components
- `NavItem` - Navigation item

These classes provide the foundational UI framework. Refer to `mistralys/x4-core` documentation for their full API.

---

## Summary

This API reference provides **signatures only** for all public classes. Key patterns:

1. **Factory Methods**: Most services offer `create()` and `createFromConfig()` static methods
2. **Fluent Interface**: Many methods return `self` for method chaining
3. **Lazy Loading**: Data readers instantiate only when accessed
4. **Type Safety**: Strict typing with PHPDoc annotations
5. **Collections Pattern**: Centralized entity management via Collections hub
6. **Inheritance**: UI extends `mistralys/x4-core` base classes

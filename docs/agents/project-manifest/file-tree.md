# File Tree

## Project Root Structure

```
x4-savegame-parser/
│
├── bin/                          # Executable scripts
│   ├── extract                   # CLI extraction tool (Unix)
│   ├── extract.bat               # CLI extraction tool (Windows)
│   ├── run-monitor               # Monitor daemon (Unix)
│   ├── run-monitor.bat           # Monitor daemon (Windows)
│   ├── run-ui                    # UI server (Unix)
│   ├── run-ui.bat                # UI server (Windows)
│   └── php/                      # PHP entry points
│       ├── extract.php           # Extraction CLI handler
│       ├── prepend.php           # Bootstrap file for all scripts
│       ├── run-monitor.php       # Monitor entry point
│       └── run-ui.php            # UI server entry point
│
├── src/                          # Application source code
│   └── X4/
│       └── SaveViewer/           # Main namespace
│           ├── BaseXMLParser.php           # Abstract XML parser base
│           ├── SaveParser.php              # Main savegame parser
│           ├── SaveViewer.php              # UI application entry point
│           ├── SaveViewerException.php     # Application exception
│           │
│           ├── CLI/                        # Command-line interface
│           │   └── CLIHandler.php          # CLI command processor
│           │
│           ├── Config/                     # Configuration management
│           │   └── Config.php              # Singleton config loader
│           │
│           ├── Data/                       # High-level data access
│           │   ├── SaveManager.php         # Savegame file manager
│           │   ├── SaveReader.php          # Parsed data reader facade
│           │   └── SaveManager/
│           │       ├── BaseSaveFile.php    # Abstract save file base
│           │       └── SaveTypes/
│           │           ├── MainSave.php    # Active save file
│           │           └── ArchivedSave.php # Archived save file
│           │   └── SaveReader/             # Specialized data readers
│           │       ├── Blueprints.php      # Blueprint data
│           │       ├── Factions.php        # Faction relations
│           │       ├── Statistics.php      # Game statistics
│           │       ├── Log.php             # Event log
│           │       ├── Inventory.php       # Player inventory
│           │       ├── PlayerInfo.php      # Player information
│           │       ├── SaveInfo.php        # Save metadata
│           │       ├── GameTime.php        # Game time data
│           │       ├── KhaakStationsReader.php  # Khaa'k stations
│           │       ├── ShipLossesReader.php     # Ship losses
│           │       ├── Ware.php            # Ware data model
│           │       ├── Info.php            # General info
│           │       ├── Factions/           # Faction sub-components
│           │       │   ├── FactionRelation.php
│           │       │   └── FactionDefs.php
│           │       └── Log/                # Log sub-components
│           │           ├── LogAnalysisCache.php
│           │           ├── LogAnalysisWriter.php
│           │           ├── LogCategories.php
│           │           └── LogCategory.php
│           │
│           ├── Monitor/                    # Monitoring daemons
│           │   ├── BaseMonitor.php         # Abstract monitor base
│           │   ├── X4Monitor.php           # Savegame folder monitor
│           │   ├── X4Server.php            # HTTP UI server
│           │   └── Output/                 # Monitor output formats
│           │       ├── MonitorOutputInterface.php
│           │       ├── ConsoleOutput.php   # Human-readable output
│           │       └── JsonOutput.php      # NDJSON machine output
│           │
│           ├── Parser/                     # XML parsing & data models
│           │   ├── BaseDOMFragment.php     # DOM-based fragment parser
│           │   ├── BaseFragment.php        # Abstract fragment base
│           │   ├── BaseXMLFragment.php     # Custom XML fragment parser
│           │   ├── Collections.php         # Collections hub/registry
│           │   ├── ConnectionComponent.php # Connection component model
│           │   ├── FileAnalysis.php        # Save file analysis metadata
│           │   ├── SaveSelector.php        # Save file selector/finder
│           │   ├── ConstructionPlansParser.php  # Construction plans parser
│           │   │
│           │   ├── Collections/            # Typed collections
│           │   │   ├── BaseCollection.php  # Abstract collection base
│           │   │   ├── CelestialsCollection.php
│           │   │   ├── ClustersCollection.php
│           │   │   ├── EventLogCollection.php
│           │   │   ├── PeopleCollection.php
│           │   │   ├── PlayerCollection.php
│           │   │   ├── RegionsCollection.php
│           │   │   ├── SectorsCollection.php
│           │   │   ├── ShipsCollection.php
│           │   │   ├── StationsCollection.php
│           │   │   └── ZonesCollection.php
│           │   │
│           │   ├── ConstructionPlans/      # Construction plan models
│           │   │   ├── ConstructionPlan.php
│           │   │   ├── ModuleCategories.php
│           │   │   ├── ModuleException.php
│           │   │   ├── PlanModule.php
│           │   │   └── ProductionModule.php
│           │   │
│           │   ├── DataProcessing/         # Post-parse data processing
│           │   │   ├── BaseDataProcessor.php
│           │   │   ├── DataProcessingHub.php
│           │   │   └── Processors/
│           │   │       ├── KhaakStationsList.php
│           │   │       └── [other processors]
│           │   │
│           │   ├── Fragment/               # XML fragment parsers
│           │   │   ├── ClusterConnectionFragment.php
│           │   │   ├── EventLogFragment.php
│           │   │   ├── FactionsFragment.php
│           │   │   ├── PlayerStatsFragment.php
│           │   │   └── SaveInfoFragment.php
│           │   │
│           │   ├── SaveSelector/           # Save file utilities
│           │   │   └── SaveGameFile.php    # Save file wrapper
│           │   │
│           │   ├── Tags/                   # Tag-based parsers
│           │   │   ├── Tag.php             # Abstract tag parser
│           │   │   ├── ComplexTag.php      # Complex tag parser
│           │   │   └── Tag/                # Specific tag parsers
│           │   │       ├── LogTag.php
│           │   │       ├── MessagesTag.php
│           │   │       ├── FactionsTag.php
│           │   │       ├── StatsTag.php
│           │   │       └── PlayerComponentTag.php
│           │   │
│           │   ├── Traits/                 # Parser traits
│           │   │   ├── ComponentInterface.php
│           │   │   ├── PersonContainerInterface.php
│           │   │   ├── PersonContainerTrait.php
│           │   │   ├── PlayerContainerInterface.php
│           │   │   ├── PlayerContainerTrait.php
│           │   │   ├── ShipContainerInterface.php
│           │   │   └── ShipContainerTrait.php
│           │   │
│           │   └── Types/                  # Data model types
│           │       ├── BaseComponentType.php   # Abstract type base
│           │       ├── CelestialBodyType.php
│           │       ├── ClusterType.php
│           │       ├── LogEntryType.php
│           │       ├── PersonType.php
│           │       ├── PlayerType.php
│           │       ├── RegionType.php
│           │       ├── SectorType.php
│           │       ├── ShipType.php
│           │       ├── StationType.php
│           │       └── ZoneType.php
│           │
│           ├── Traits/                     # Application-level traits
│           │   ├── DebuggableInterface.php
│           │   └── DebuggableTrait.php
│           │
│           └── UI/                         # User interface
│               ├── MainPage.php            # Main page base
│               ├── Page.php                # UI page base
│               ├── PageWithNav.php         # Page with sub-navigation
│               ├── RedirectException.php   # Redirect exception
│               ├── SavesGridRenderer.php   # Save list grid renderer
│               ├── ViewerSubPage.php       # Viewer sub-page base
│               └── Page/                   # Concrete pages
│                   ├── ArchivedSavesPage.php
│                   ├── ConstructionPlansPage.php
│                   ├── CreateBackup.php
│                   ├── SavesList.php
│                   ├── UnpackSave.php
│                   ├── ViewPlanPage.php
│                   ├── ViewSave.php
│                   ├── ConstructionPlans/  # Construction plan sub-pages
│                   │   ├── BasePlanSubPage.php
│                   │   ├── PlanOverviewPage.php
│                   │   └── PlanSettingsPage.php
│                   └── ViewSave/           # Save viewer sub-pages
│                       ├── BaseViewSaveSubPage.php
│                       ├── Backup.php
│                       ├── BlueprintsPage.php
│                       ├── DeleteArchivePage.php
│                       ├── EventLogPage.php
│                       ├── FactionRelations.php
│                       ├── Factions.php
│                       ├── Home.php
│                       ├── Inventory.php
│                       ├── KhaakOverviewPage.php
│                       ├── Losses.php
│                       └── Statistics.php
│
├── tests/                        # Test suite
│   ├── bootstrap.php             # Test bootstrap
│   ├── classes/                  # Test base classes
│   │   ├── X4LogCategoriesTestCase.php
│   │   └── X4ParserTestCase.php
│   ├── files/                    # Test fixtures
│   │   ├── save-files/           # Sample save files
│   │   └── test-saves/           # Test save data
│   ├── phpstan/                  # Static analysis
│   │   ├── config.neon           # PHPStan configuration
│   │   ├── level.txt             # Analysis level
│   │   ├── result.txt            # Analysis results
│   │   ├── run-analysis          # Analysis runner (Unix)
│   │   ├── run-analysis.bat      # Analysis runner (Windows)
│   │   └── clear-cache           # Cache clearer
│   └── testsuites/               # Test suites
│       ├── Parser/               # Parser tests
│       └── Reader/               # Reader tests
│
├── docs/                         # Documentation
│   ├── agents/                   # AI agent documentation
│   │   ├── implementation-archive/
│   │   ├── plans/
│   │   └── project-manifest/     # THIS DIRECTORY
│   │       ├── README.md
│   │       ├── cli-api-reference.md
│   │       ├── constraints-and-rules.md
│   │       ├── data-flows.md
│   │       ├── extracted-save-location.md
│   │       ├── file-tree.md
│   │       ├── ndjson-interface.md
│   │       ├── public-api-reference.md
│   │       └── tech-stack-and-patterns.md
│   └── screenshots/              # UI screenshots
│       ├── screen-archived-saves.png
│       ├── screen-event-log.png
│       ├── screen-khaak-stations-list.png
│       ├── screen-saves-overview.png
│       ├── screen-ship-losses.png
│       └── screen-unowned-blueprints-list.png
│
├── archived-saves/               # Archived save files directory
│   └── README.md
│
├── vendor/                       # Composer dependencies
│   ├── autoload.php              # Composer autoloader
│   ├── composer/                 # Composer metadata
│   ├── components/               # Frontend components
│   │   └── jquery/
│   ├── mistralys/                # Mistralys packages
│   │   ├── x4-core/              # Base application framework
│   │   ├── x4-data-extractor/    # Game data utilities
│   │   ├── application-utils/    # Utility library
│   │   ├── application-utils-collections/
│   │   ├── application-utils-core/
│   │   ├── application-utils-image/
│   │   ├── application-utils-result-handling/
│   │   ├── application-localization/
│   │   ├── changelog-parser/
│   │   ├── html_quickform2/
│   │   ├── php-sprintf-parser/
│   │   └── version-parser/
│   ├── react/                    # ReactPHP components
│   │   ├── http/
│   │   ├── async/
│   │   └── [other react packages]
│   ├── league/                   # League packages
│   │   └── climate/              # CLI framework
│   ├── phpunit/                  # PHPUnit testing framework
│   ├── phpstan/                  # Static analysis tool
│   └── [other vendor packages]
│
├── composer.json                 # Composer dependencies manifest
├── composer.lock                 # Locked dependency versions
├── config.dist.json              # Default configuration template
├── config.json                   # User configuration (gitignored)
├── phpunit.xml                   # PHPUnit configuration
├── prepend.php                   # Application bootstrap
├── LICENSE                       # MIT License
├── README.md                     # Project README
├── AGENTS.md                     # AI agents documentation index
├── changelog.md                  # Change log
└── VERSION                       # Version file (0.0.1)
```

## Key Directory Purposes

### `/bin/`
Executable entry points for all three application modes (CLI, UI, Monitor). Wrappers handle OS-specific execution, PHP files contain actual logic.

### `/src/X4/SaveViewer/`
Core application code organized by responsibility:
- **CLI/** - Command-line interface
- **Config/** - Configuration management
- **Data/** - High-level data access (SaveManager, SaveReader)
- **Monitor/** - Background monitoring daemons
- **Parser/** - XML parsing, data models (Types), Collections
- **UI/** - Web interface pages

### `/tests/`
PHPUnit test suite with fixtures and test base classes. Includes PHPStan static analysis configuration.

**Structure**:
```
tests/
├── bootstrap.php                           # Test suite bootstrap
├── classes/                                # Test base classes
│   ├── X4LogCategoriesTestCase.php
│   └── X4ParserTestCase.php
├── files/                                  # Test data files
│   ├── save-files/                         # Sample save files
│   └── test-saves/                         # Synthetic test data (git tracked)
│       └── unpack-20260206211435-quicksave/
│           ├── analysis.json
│           └── JSON/                       # 10 collection JSON files
│               ├── collection-ships.json
│               ├── collection-stations.json
│               ├── collection-sectors.json
│               ├── collection-people.json
│               ├── collection-zones.json
│               ├── collection-regions.json
│               ├── collection-clusters.json
│               ├── collection-celestial-bodies.json
│               ├── collection-player.json
│               └── collection-event-log.json
├── testsuites/                             # Test suites by component
│   ├── CLI/                                # CLI API tests (32 tests)
│   │   ├── JsonResponseBuilderTest.php
│   │   ├── QueryHandlerCollectionsTest.php
│   │   └── CommandExecutionTest.php
│   ├── Parser/                             # Parser tests
│   └── Reader/                             # Data reader tests
└── phpstan/                                # Static analysis
    ├── config.neon
    └── run-analysis
```

**Test Data**: Synthetic test save with minimal JSON data committed to git. Tests run without requiring game installation or save extraction.

**Coverage**: ~40% for CLI components, includes collection loading, filtering, pagination, caching, and error handling.

### `/docs/agents/project-manifest/`
AI agent "Source of Truth" documentation (this directory).

### `/archived-saves/`
Storage location for archived savegame backups (user-configurable).

### `/vendor/`
Composer-managed third-party dependencies. Key dependency is `mistralys/x4-core` which provides the UI framework base classes.

## Generated/Runtime Directories

These directories are created at runtime and typically gitignored:

### Extracted Savegame Data

**Location**: `{savesFolder}/unpack-{datetime}-{name}/`

**Important**: Extracted saves are stored in the **game's saves folder** (configured in `config.json`), NOT in the project directory.

**Example** (based on typical config):
```
C:\Users\{username}\Documents\Egosoft\X4\{playerID}\save\
  └── unpack-20260112062240-quicksave/
      ├── analysis.json          # Save metadata
      ├── backup.gz              # Original save backup
      ├── JSON/                  # Parsed data files
      │   ├── collection-ships.json
      │   ├── collection-stations.json
      │   ├── data-blueprints.json
      │   └── ...
      └── XML/                   # Temporary fragments (optional, deleted after parse)
```

**Note**: Test saves in `tests/files/test-saves/` use the same structure but are synthetic data committed to git.

### Archived Saves

**Location**: `{gameFolder}/archived-saves/` (user-configurable)

Stores older extracted save versions when saves are re-extracted.

## Configuration Files

- **config.json** - User configuration (created from config.dist.json)
- **composer.json** - PHP dependencies and autoloading
- **phpunit.xml** - Test suite configuration
- **prepend.php** - Application bootstrap (defines constants, loads autoloader)

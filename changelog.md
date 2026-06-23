# Changelog

## v0.2.0 - CLI API Release (Breaking-S)
- Config: Converted configuration format from PHP to JSON (`config.json`).
- Config: Removed `storageFolder` setting.
- Config: Updated config key names to camelCase.
- CLI: Implemented JSON query API with JMESPath filtering via `bin/query`.
- CLI: Added 21 query commands covering all savegame data.
- CLI: Added query caching for efficient pagination of large datasets.
- CLI: Added search capability via custom JMESPath functions.
- CLI: Added `list-saves` command to enumerate available saves.
- CLI: Added `list-paths` command to show configured paths.
- CLI: Added `queue-extraction` command for monitor-based extraction queuing.
- CLI: Standard JSON response envelope with success/error handling.
- Monitor: Now processes extraction queue before checking for new saves.
- Logbook: Added NDJSON progress streaming for long-running queries.
- Parser: Improved player detection in cluster connection fragments.
- Utilities: Added `bin/indent-xml` script for XML file formatting.
- Tests: Added comprehensive CLI and parser test suite.
- Composer: Added PHPStan static analysis support.
- Docs: Added AGENTS.md and agentic project manifest.
- UI: Deprecated Web UI in favour of the CLI API (`bin/query`).
- UI: Deprecated UI Server (`bin/run-ui`), to be removed in a future version.

### Breaking Changes

The configuration file has been converted from PHP to JSON. Copy `config.dist.json` 
as `config.json` and migrate your settings from the old `config.php`. The `storageFolder`
setting has been removed.

## v0.1.0 - Construction Plans
- Construction Plans: Added page to view all construction plans.
- Construction Plans: Added possibility to rename plans via the UI.
- Construction Plans: Added `setLabel()` and `save()` to plans.
- UI: Now handling POST variables for forms.
- Config: Added `X4_FOLDER` setting.
- Config: Removed `X4_SAVES_FOLDER` setting (still used if present).
- Dependencies: Updated X4 Core to [v0.0.6](https://github.com/Mistralys/x4-core/releases/tag/0.0.6).

## v0.0.3 - Minor features
- Database: Added the Erlking and Astrid blueprints.
- Dependencies: Updated X4 Core to [v0.0.4](https://github.com/Mistralys/x4-core/releases/tag/0.0.4).

## v0.0.2 - Minor features
- Extract: Added the `-la` command to show archived savegames.
- Extract: Added the `-rebuild` command to rebuild the JSON from XML files.
- Database: Added the Boron Art Academy blueprint.
- Parser: Now detecting the player in station build module rooms.
- Dependencies: Updated X4 Core to [v0.0.3](https://github.com/Mistralys/x4-core/releases/tag/0.0.3).

## v0.0.1 - Alpha release
- UI: Blueprints list (owned/unowned)
- UI: Khaa'k stations list
- UI: Ship losses list
- UI: Categorized event log
- Command line: Automatic backup
- Command line: Automatic data extraction
- Command line: Manual extraction
- Data: JSON file generation

# Changelog

## v1.0.0 - CLI API Release
- CLI API with JMESPath filtering for programmatic data access
- Query caching for efficient pagination of large datasets  
- Standard JSON response envelope with success/error handling
- 21 query commands covering all savegame data
- `list-saves` command to enumerate available saves
- `queue-extraction` command to queue saves for automatic extraction by monitor
- Monitor now processes extraction queue before checking for new saves
- Comprehensive API documentation for integration

### Deprecations
- Web UI (use CLI API via `bin/query` instead)
- UI Server (will be removed in a future version)

## v0.0.6 - Construction plans
- Construction Plans: Added possibility to rename plans via the UI.
- Construction Plans: Added `setLabel()` and `save()` to plans.
- UI: Now handling POST variables for forms.

## v0.0.5 - Construction plans viewer
- Construction Plans: Added a page to view all construction plans.
- Configuration: Added `X4_FOLDER`.
- Configuration: Removed `X4_SAVES_FOLDER` setting (still used if present).
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

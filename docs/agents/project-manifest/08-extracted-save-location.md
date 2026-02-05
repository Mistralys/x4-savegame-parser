# Extracted Savegame Storage Location

## Overview

When you extract a savegame using `./bin/extract`, the parsed data is stored in a dedicated folder structure. This document explains where to find your extracted savegame data.

## Storage Location

### Default Location

By default, extracted savegames are stored in:

```
{savesFolder}/../archived-saves/unpack-{datetime}-{savename}/
```

This places the extracted data alongside your save files in the user's X4 folder, **not** in the game installation directory.

**Example** (based on typical Windows installation):
```
C:\Users\{username}\Documents\Egosoft\X4\{player-id}\
  ├── save/                      # Your .gz savegame files
  └── archived-saves/            # Extracted savegame data
      └── unpack-20260112062240-quicksave/
          ├── analysis.json          # Save metadata
          ├── backup.gz              # Original save backup (if enabled)
          ├── .cache/                # Query result cache (hidden)
          │   └── query-{key}.json
          ├── JSON/                  # Parsed data files
          │   ├── collection-ships.json
          │   ├── collection-stations.json
          │   ├── collection-sectors.json
          │   ├── collection-people.json
          │   ├── data-blueprints.json
      │   ├── data-event-log.json
      │   ├── data-factions.json
      │   ├── data-statistics.json
      │   └── ...
      └── XML/                   # Temporary fragments (optional, deleted after parse)
```

### Custom Storage Location

You can override the default storage location by adding a `storageFolder` key to your `config.json`:

```json
{
  "gameFolder": "C:\\Steam\\steamapps\\common\\X4 Foundations",
  "savesFolder": "C:\\Users\\YourName\\Documents\\Egosoft\\X4\\12345678\\save",
  "storageFolder": "D:\\X4-Archives",
  "viewerHost": "localhost",
  "viewerPort": 9494,
  "autoBackupEnabled": true,
  "keepXMLFiles": false,
  "loggingEnabled": false
}
```

With the above configuration, extracted saves would be stored at:
```
D:\X4-Archives\unpack-20260112062240-quicksave\
```

## Folder Naming Convention

### Format

```
unpack-{YYYYMMDDHHMMSS}-{savename}
```

- **`unpack-`**: Prefix to identify extracted savegames
- **`{YYYYMMDDHHMMSS}`**: Date and time of extraction (Year, Month, Day, Hour, Minute, Second)
- **`{savename}`**: Original savegame filename without extension

### Examples

| Original Savegame File | Extraction Time | Resulting Folder Name |
|------------------------|-----------------|----------------------|
| `quicksave.xml.gz` | 2026-01-12 06:22:40 | `unpack-20260112062240-quicksave` |
| `autosave_01.xml.gz` | 2026-02-05 14:30:15 | `unpack-20260205143015-autosave_01` |
| `save_020.xml.gz` | 2025-06-25 19:12:08 | `unpack-20250625191208-save_020` |

### Multiple Extractions

The timestamped folder structure allows you to extract the same savegame multiple times, creating a history:

```
archived-saves/
  ├── unpack-20260112062240-quicksave/
  ├── unpack-20260115103045-quicksave/
  └── unpack-20260120185530-quicksave/
```

This is particularly useful when used with the **Monitor** feature, which automatically extracts saves when they're modified.

## Finding Your Extracted Data

### Method 1: Check Configuration

1. Open your `config.json` file
2. Check if `storageFolder` is set:
   - **If set**: Use that path directly
   - **If not set**: Look for the `savesFolder` value, then use its parent directory + `\archived-saves`
     - Example: If `savesFolder` is `C:\Users\{username}\Documents\Egosoft\X4\{player-id}\save`
     - Then storage is: `C:\Users\{username}\Documents\Egosoft\X4\{player-id}\archived-saves`
3. Look for folders starting with `unpack-` in that location

**Note**: The default behavior places extracted data in your user's X4 folder, **not** the game installation directory.

### Method 2: Run Extraction with Output

The extract command shows the output folder when you run it:

```bash
./bin/extract quicksave
```

Output:
```
Extracting the specified savegames.
Output folder: C:\Steam\steamapps\common\X4 Foundations\archived-saves

Savegame [quicksave] found.
- Processing the file.
- Unzipping...
...
```

### Method 3: List Archived Saves

Use the `-la` flag to list all archived (extracted) savegames:

```bash
./bin/extract -la
```

This shows:
- Savegame name
- Extraction date
- Whether the savegame is still present in your saves folder

## Folder Structure Details

### `/JSON/` Directory

Contains all parsed savegame data in JSON format:

**Collections** (entities from the savegame):
- `collection-ships.json` - All ships
- `collection-stations.json` - All stations
- `collection-sectors.json` - All sectors
- `collection-people.json` - All people/crew
- `collection-zones.json` - All zones
- `collection-regions.json` - All regions
- `collection-clusters.json` - All clusters
- `collection-celestials.json` - All celestial objects
- `collection-player.json` - Player information

**Data Files** (processed/derived data):
- `data-blueprints.json` - Blueprint ownership and unlock status
- `data-event-log.json` - Categorized event log
- `data-factions.json` - Faction information
- `data-statistics.json` - Game statistics
- `data-khaak-stations.json` - Kha'ak station locations
- `data-ship-losses.json` - Ships lost by the player
- `data-inventory.json` - Player inventory

### `/XML/` Directory (Optional)

Temporary XML fragments created during parsing. These are deleted after extraction unless you set `"keepXMLFiles": true` in your config.

### `/.cache/` Directory

Hidden directory containing cached query results for the CLI API. Used for efficient pagination of large datasets.

### `analysis.json`

Metadata about the savegame:
```json
{
  "saveID": "quicksave-20260112062240",
  "saveName": "quicksave",
  "dateModified": "2026-01-12T06:22:40+00:00",
  "extractionDuration": 45.23,
  "lastProcessed": "2026-01-12T06:23:25+00:00",
  "processDates": ["2026-01-12T06:23:25+00:00"]
}
```

### `backup.gz` (Optional)

Copy of the original `.xml.gz` savegame file. Created if `"autoBackupEnabled": true` in your config.

## Common Issues

### "Cannot find extracted data"

1. **Check the correct location**: Use Method 1 or 2 above to verify where data should be stored
2. **Verify extraction completed**: Run `./bin/extract -l` to see which saves show "YES" in the extracted column
3. **Check for errors**: The extraction may have failed - try running `./bin/extract {savename}` again

### "No archived-saves folder exists"

The folder is created automatically on first extraction. If it doesn't exist:
1. Make sure you've actually run an extraction: `./bin/extract quicksave`
2. Check file system permissions - the application needs write access to `{gameFolder}/`
3. Verify your `gameFolder` path is correct in `config.json`

### "Old bug: PATH_SEPARATOR issue"

**Fixed in current version**

Earlier versions had a bug where `Config::getStorageFolder()` used `PATH_SEPARATOR` (`;` on Windows) instead of `DIRECTORY_SEPARATOR` (`\`), resulting in malformed paths like:

```
C:\Steam\steamapps\common\X4 Foundations;/archived-saves
```

This has been fixed. If you're using an older version, you can work around it by explicitly setting `storageFolder` in your `config.json`.

## Configuration Reference

### Related Config Keys

```json
{
  "gameFolder": "...",      // Required: Game installation directory
  "savesFolder": "...",     // Required: Where .xml.gz saves are located
  "storageFolder": "...",   // Optional: Where to store extracted data (default: {gameFolder}/archived-saves)
  "autoBackupEnabled": true,  // Whether to create backup.gz
  "keepXMLFiles": false,      // Whether to keep temporary XML fragments
  "loggingEnabled": false     // Whether to enable debug logging
}
```

### Minimal Configuration

If you only set `gameFolder` and `savesFolder`, the system will automatically use `{gameFolder}/archived-saves` for storage.

## See Also

- [CLI API Reference](./agents/project-manifest/07-cli-api-reference.md) - Query extracted savegame data
- [Data Flows](./agents/project-manifest/04-data-flows.md) - How extraction works internally
- [File Tree](./agents/project-manifest/02-file-tree.md) - Project structure overview

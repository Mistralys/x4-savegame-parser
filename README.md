# X4 Savegame manager

Savegame manager for X4: Foundations. Used to automatically back up your savegames,
and to extract interesting information from them.

## Features

- Savegame folder monitoring and auto-backup.
- Extract useful information into JSON files.
- Process large savegames (1+ GB).
- Browser-based user interface:
  - Blueprints list (owned/unowned).
  - Khaa'k hives and nests list.
  - Ship losses list.
  - Categorized event log with search function.
  - Detailed list of user-added construction plans.
- JSON files can be used by third party apps.
- Via [X4 Core][], OOP access to game information.

## Screenshots

_List of archived savegames_
![Screenshot: Archived savegames](/docs/screenshots/screen-archived-saves.png?raw=true "Screenshot: Archived savegames")

_Event log_
![Screenshot: Event log](/docs/screenshots/screen-event-log.png?raw=true "Screenshot: Event log")

_Khaa'k stations list_
![Screenshot: Khaa'k stations list](/docs/screenshots/screen-khaak-stations-list.png?raw=true "Screenshot: Khaa'k stations list")

_Blueprint browser (unowned shown here)_
![Screenshot: Blueprint browser](/docs/screenshots/screen-unowned-blueprints-list.png?raw=true "Screenshot: Blueprint browser")

_List of ship losses_
![Screenshot: List of ship losses](/docs/screenshots/screen-ship-losses.png?raw=true "Screenshot: List of ship losses")

## Requirements

- [PHP][] 7.4+ 
  - ZLIB extension
  - DOM extension
  - XMLReader extension
- [Composer][]

## How does it work?

There are several tools which can be used together:

- **The extraction tool**  
  Command line tool to manually extract information
  from savegame files, for use in the UI or programmatically.
- **The manager UI**  
  Displays all savegames and available backups. Allows 
  easy access to the information on each save.
- **The savegame folder monitor**   
  Detects new savegames. Extracts information from them,
  and creates a backup.

## Installing

1. Clone the repository somewhere.
2. Run `composer install`
3. Copy `config.dist.json` to `config.json`
4. Edit `config.json` to adjust the settings

## Quick start

### 1: Extract save information

1. Execute `./bin/extract -l` to display a list of savegames.
2. Execute `./bin/extract -e "name"` where `name` is the save you wish to extract.

> NOTE: See also the _Extract tool command line_ section
> for more extraction options - extract multiple saves and
> more.

### 2: Access the interface (DEPRECATED)

> **⚠️ DEPRECATED**: The web interface will be removed in a future version.
> Please use the CLI API instead (see "CLI API" section above).

1. Execute `./bin/run-ui`.
2. You should see a message that the server is running.
3. Note the server URL shown.
4. Open a web browser, and go to the URL.

The interface initially shows a list of files available 
in the game's savegame folder. Any previously archived 
saves are listed in the _Saves Archive_ tab.

## Advanced usage

### Configuration

The application is configured via a `config.json` file in the root folder.
You can copy `config.dist.json` to `config.json` to get started.

Available keys:
- `gameFolder` (string): Path to X4 documents folder
- `storageFolder` (string): Path to store extracted data
- `viewerHost` (string): Hostname for UI server
- `viewerPort` (int): Port for UI server
- `autoBackupEnabled` (bool): Enable auto backups
- `keepXMLFiles` (bool): Keep extracted XML files
- `loggingEnabled` (bool): Enable verbose logging

### Extract tool command line

#### Display command help

```shell
./bin/extract
```

#### List all savegames

```shell
./bin/extract -l
```

#### Extract all savegames

This will only extract those that have not been extracted yet.

```shell
./bin/extract -all
```

#### Extract multiple savegames by name

```shell
./bin/extract -e "quicksave autosave_01 save_006"
```

#### Extraction options

- `-xml --keep-xml` _Keep the XML files_
- `--no-backup` _Do not create a savegame backup_

#### List all archived savegames

```shell
./bin/extract -la
```

#### Rebuild the JSON files

Regenerates a fresh version of the JSON files, using the extracted
XML fragments of an archived savegame.

> NOTE: Only available if the XML fragment files have been preserved.

```shell
./bin/extract -rebuild "unpack-20230528171642-quicksave"
```

Where the parameter is the folder name of an archived savegame. Use
the `-la` command to show a list.

### CLI API

The CLI API provides programmatic access to all savegame data via JSON output.
This is the recommended way to integrate with external applications.

#### Quick Start

```bash
# List available saves
./bin/query list-saves --pretty

# Query save information
./bin/query save-info --save=quicksave --pretty

# List all ships
./bin/query ships --save=quicksave

# Filter blueprints (owned only)
./bin/query blueprints --save=quicksave --filter="[?owned==\`true\`]"

# Paginate through ships
./bin/query ships --save=quicksave --limit=50 --offset=0 --cache-key=ships-1

# Clear cache
./bin/query clear-cache
```

#### Available Commands

**Summary Commands** (return objects):
- `save-info` - Save metadata (player name, money, date, etc.)
- `player` - Player information
- `stats` - Game statistics
- `factions` - Faction information and relations

**List Commands** (return arrays):
- `blueprints` - All blueprints with ownership status
- `inventory` - Player inventory items
- `log` - Event log entries
- `khaak-stations` - Khaa'k hive and nest locations
- `ship-losses` - Player ship losses

**Collection Commands** (return arrays):
- `ships` - All ships in the universe
- `stations` - All stations in the universe
- `people` - All NPCs and crew
- `sectors` - All sectors
- `zones` - All sector zones
- `regions` - All regions
- `clusters` - All clusters
- `celestials` - All celestial bodies
- `event-log` - Raw event log collection

**Special Commands**:
- `list-saves` - List all available saves (main and archived)
- `queue-extraction` - Queue saves for automatic extraction by monitor
- `clear-cache` - Remove all cached query results

#### Queuing Saves for Extraction

You can queue older saves to be automatically extracted by the monitor:

```bash
# Queue a single save
./bin/query queue-extraction --save=autosave_01

# Queue multiple saves at once
./bin/query queue-extraction --saves="autosave_01 autosave_02 save_020"

# View what's queued
./bin/query queue-extraction --list

# Clear the queue
./bin/query queue-extraction --clear
```

Once queued, the monitor will automatically extract these saves the next time
it runs (or during its next tick if already running). This is useful for
processing older saves without manually running the extract command.

**How it works**:
1. Use `list-saves` to see available saves
2. Queue the ones you want with `queue-extraction`
3. The monitor will process them automatically (before checking for new saves)
4. Already-extracted saves are skipped automatically

#### Filtering with JMESPath

The `--filter` flag accepts [JMESPath](https://jmespath.org/) expressions for powerful filtering:

```bash
# Ships owned by player
./bin/query ships --save=quicksave --filter="[?owner=='player']"

# Argon faction ships
./bin/query ships --save=quicksave --filter="[?faction=='argon']"

# Owned ship blueprints
./bin/query blueprints --save=quicksave --filter="[?owned==\`true\` && category=='ships']"

# Complex projection
./bin/query ships --save=quicksave --filter="[?faction=='argon'].{name: name, sector: sector}"
```

#### Pagination

Use `--limit` and `--offset` for pagination, with `--cache-key` for performance:

```bash
# First page (stores filtered results in cache)
./bin/query ships --save=quicksave --filter="[?faction=='argon']" \
  --limit=50 --offset=0 --cache-key=argon-ships

# Second page (reuses cached filtered results)
./bin/query ships --save=quicksave --limit=50 --offset=50 --cache-key=argon-ships
```

#### Complete Documentation

See [CLI API Reference](docs/agents/project-manifest/07-cli-api-reference.md) for:
- Complete command reference with schemas
- JMESPath filtering guide
- Pagination and caching workflows
- Error handling
- Rust/Tauri integration examples

### Running the savegame Monitor

#### Monitor command line

The Monitor runs in the background, and **observes the X4 savegame folder**.
When a new savegame is written to disk, it is **automatically unpacked and
backed up** to the storage folder to access its information in the UI.

This tool is especially useful if you leave the game running unattended. If
something bad happens ingame, it is easy to revert to a previous save. The big
advantage here is that it is not limited to the amount of autosave slots the 
game has.

Simply run the following in a terminal:

```shell
./bin/run-monitor
```

#### Machine-readable output

If you are wrapping the monitor in another application, you can enable
NDJSON (Newline Delimited JSON) output mode. This will output structured
JSON objects for events and status updates instead of human-readable text.

```shell
./bin/run-monitor --json
```

The monitor will periodically display a status message in the terminal to
explain what it's doing. If a new savegame is detected, it will say so
and unpack it as well as create a backup (if enabled in the config).

If you leave your game running unattended with autosave on, each new
autosave will automatically be processed as well.

> CAUTION: This can quickly fill your disk if you have the `keepXMLFiles`
> option enabled in `config.json`. More information on this in the _Monitor options_ section.
 
#### Windows usage

On Windows, it is possible to add the monitor to the task bar:

1. Right-click `run-monitor.bat`, select _Create shortcut_.
2. Right-click the shortcut, select _Properties_.
3. Add `cmd /c` at the beginning in the _Target_ field.
4. Change the icon if you like.
5. Drag the shortcut to the task bar.

> TIP: This also works with the UI batch file.

#### Monitor options

##### Keep XML files

Config key: `keepXMLFiles` (boolean)

Whether to keep the extracted XML fragment files after extraction.

By default, the individual XML files created during extraction (before
converting the data to JSON) are automatically deleted when done. They
can be useful if you wish to study the XML structure.

##### Auto-Backup

Config key: `autoBackupEnabled` (boolean) 

Whether to create a copy of the savegame `.gz` file.

If enabled, a copy of the savegame file will be stored in the archived
savegame folder, as `backup.gz`.

> NOTE: This only works when the compression of savegames is turned on
> in the game settings. For performance reasons, the Monitor does not
> compress uncompressed XML files.

##### Detailed log output

Config key: `loggingEnabled` (boolean)

Whether to display detailed logging messages in the Monitor's
command line. This is mainly used for debugging purposes when developing.

## Navigating savegame data

### Archive folders

The extraction process creates a folder for each savegame
in the storage folder configured in `config.json`,
which looks like this:

```
unpack-20230524194512-quicksave
       ^              ^
       Date + time    Save name
```

This structure means that the quicksave for example can be archived 
multiple times in the storage folder, to create a history of it - and
incidentally, make it possible to go back to a previous version.

> NOTE: This works best in combination with the savegame Monitor.

### JSON Files

The `JSON` folder under the savegame folder contains all extracted
information:

- `collection-celestial-bodies.json` _All celestial bodies_
- `collection-clusters.json` _All clusters_
- `collection-event-log.json` _Full event log_
- `collection-people.json` _All NPCs_
- `collection-player.json` _Player information_
- `collection-regions.json` _All regions_
- `collection-sectors.json` _All sectors_
- `collection-ships.json` _All ships (player and NPC)_
- `collection-stations.json` _All stations (player and NPC)_
- `collection-zones.json` _All sector zones_
- `data-khaak-stations.json` _Khaa'k stations list_
- `data-losses.json` _Player ship losses_
- `savegame-info.json` _Global savegame info_

### XML Files

If the option to keep XML files is enabled, the `XML` folder under
the savegame folder will retain the extracted XML fragment files. 
These files are all original XML fragments from the savegame that
were used to extract the information.

> NOTE: `{NR}` refers to a generated number assigned during extraction.

- `savegame.info-{NR}.xml`  
  The savegame's info tag.
- `savegame.log-{ID}.xml`  
  The full event log.
- `savegame.stats-{NR}.xml`  
  The player's statistics.
- `savegame.universe.component[galaxy].connections.connection[ID]-{NR}.xml`  
  Individual connection tags in the galaxy.
- `savegame.universe.factions-002`  
  Faction relations.

## The technology corner

### Introduction

After trying a number of technologies to parse the game's large XML 
files (1+ GB), I eventually settled on a mix of tools to access the 
relevant information. The result is a multi-tiered parsing process:

### The extraction steps

#### 1) Extract XML fragments

Using PHP's [XMLReader][], all the interesting parts of the XML are
extracted, and saved as separate XML files. This works well because
the XMLReader does not load the whole file into memory. The save parser
also skips as many parts as possible of the XML that is not used.

#### 2) Parse XML fragments

Now that the XML file sizes are manageable, they are read using
PHP's [DOMDocument][] to access their information. To make the data
easier to work with, all types (ships, stations, npcs) are collected
in global collections (see the [collection classes][]).

All this data is stored in prettified JSON files for easy access.

#### 3) Data processing and analysis

Once all the data has been collected, the [data processing classes][]
can use this to generate additional, specialized reports that also
get saved as JSON data files.


[PHP]:https://php.net
[Composer]:https://getcomposer.org
[X4 Core]:https://github.com/Mistralys/x4-core
[XMLReader]:https://www.php.net/manual/en/book.xmlreader.php
[DOMDocument]:https://www.php.net/manual/en/class.domdocument.php
[fragment parser classes]:https://github.com/Mistralys/x4-savegame-parser/tree/main/src/X4/SaveViewer/Parser/Fragment
[collection classes]:https://github.com/Mistralys/x4-savegame-parser/tree/main/src/X4/SaveViewer/Parser/Collections
[data processing classes]:https://github.com/Mistralys/x4-savegame-parser/tree/main/src/X4/SaveViewer/Parser/DataProcessing

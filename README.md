# X4 Savegame manager

Savegame manager for X4: Foundations. Used to automatically back up your savegames,
and to extract interesting information from them.

> NOTE: This is currently undergoing extensive refactoring
> to be more stable. 

## Features

- Savegame folder monitoring and auto-backup
- Extract useful information
- Process large savegames (1+ GB)
- Browser-based user interface
  - Blueprints list
  - Faction relations
  - Faction standings 
  - Categorized log messages (reputation changes, ship losses...)
- Storing information in JSON files
- On the PHP side, object-oriented access to information

## Requirements

- PHP 7.4+
  - ZLIB extension
  - DOM extension
  - XMLReader extension
- [Composer](https://getcomposer.org)


## How does it work?

There are two tools available, which can be used together:

- **The savegame folder monitor**   
  Detects new savegames. Extracts information from them,
  and creates a backup.
- **The manager UI**  
  Displays all savegames and available backups. Allows 
  easy access to all information about each save.

## Installing

1. Clone the repository somewhere.
2. Run `composer install`
3. Rename `config.dist.php` to `config.php`
4. Edit `config.php` to adjust the settings

## Quick start

1. Open a terminal in the project's `bin` folder.
2. Execute `./run-ui`.
3. You should see a message that the server is running.
4. Note the server URL shown.
5. Open a web browser, and go to the URL.

You should now see the manager UI and a list of savegames. 
The usage should be pretty self-explanatory from here - most
UI elements will have tooltips to explain their function.

## Advanced usage

### Running the savegame monitor

The monitor runs in the background, and **observes the X4 savegame folder**.
When a new savegame is written to disk, it is **automatically unpacked and
backed up** to the storage folder to access its information in the UI.

This tool is especially useful if you leave the game running unattended. If
something bad happens ingame, it is easy to go back to a previous save - not
limited to the amount of autosaves the game has.

Simply open a terminal in the project's `bin` folder, and start the monitor with:

```shell
./run-monitor
```

The monitor will periodically display a status message in the terminal to
explain what it's doing. If a new savegame is detected, it will say so
and unpack it as well as create a backup.

If you leave your game running unattended with autosave on, each new
autosave will automatically be processed as well.

## The technology corner

After trying a number of technologies to parse the game's large XML 
files (1+ GB), I eventually settled on a mix of tools to access the 
relevant information. The result is a multi-tiered parsing process:

### 1) Extract XML fragments

Using PHP's [XMLReader][], all the interesting parts of the XML are
extracted, and saved as separate XML files. This works well because
the XMLReader does not load the whole file into memory. The save parser
also skips as many parts as possible of the XML that is not used.

### 2) Parse XML fragments

Now that the XML file sizes are manageable, they are read using
PHP's [DOMDocument][] to access their information. To make the data
easier to work with, all types (ships, stations, npcs) are collected
in global collections (see the [collection classes][]).

All this data is stored in prettified JSON files for easy access.

### 3) Data processing and analysis

Once all the data has been collected, the [data processing classes][]
can use this to generate additional, specialized reports that also
get saved as JSON data files.


[XMLReader]:https://www.php.net/manual/en/book.xmlreader.php
[DOMDocument]:https://www.php.net/manual/en/class.domdocument.php
[fragment parser classes]:https://github.com/Mistralys/x4-savegame-parser/tree/main/src/X4/SaveViewer/Parser/Fragment
[collection classes]:https://github.com/Mistralys/x4-savegame-parser/tree/main/src/X4/SaveViewer/Parser/Collections
[data processing classes]:https://github.com/Mistralys/x4-savegame-parser/tree/main/src/X4/SaveViewer/Parser/DataProcessing

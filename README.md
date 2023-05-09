# X4 Savegame parser

Savegame parser for X4: Foundations. Convert relevant parts of the XML files to quickly access 
useful game information.

Background: I often let my game run overnight for constructions to finish, or simply to let my 
traders fill the coffers. In the morning, it was becoming a hassle to find the status information 
that I needed in the logs, like whether I lost any of my ships to pirate attacks.

> NOTE: This is currently undergoing extensive refactoring
> to be more stable. No working releases are available at
> this time.

## Features

- Browser-based user interface
- Blueprints list
- Faction relations
- Faction standings 
- Categorized log messages (reputation changes, ship losses...)
- Convert relevant info to JSON files
- On the PHP side, object-oriented access to information
- Process large savegames (1+ GB)

## Requirements

- PHP 7.4+
- ZLIB extension
- [Composer](https://getcomposer.org)

## Install

- Clone the repository somewhere in the webserver document root
- Run `composer install`
- Rename `config.dist.php` to `config.php`
- Edit the file to enter the relevant settings
- Start the server with `php start-server.php`
- Open the server in a browser

## Usage

The UI lists all available savegames with the `.xml` extension; the `.gz` files are ignored. 

## Automatic updating and alerts

### Introduction

The bundled server can automate the unpacking of the savegames: it runs in the
background, and periodically checks the savegames for changes. Modified saves
are unpacked automatically.

Additionally, the server can display alerts when there are new ship losses.

### Running the server

Open the command line and execute this to start the server:

```
php start-server.php
```

To access the server's output, point your browser to the local server
you configured in the `config.php` file.

## Technical details

After trying a number of technologies to parse the game's large XML 
files (1+ GB), I finally settled on a mix of technologies to access 
the relevant information. The result is a multi-tiered parsing process:

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

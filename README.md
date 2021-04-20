# X4 Savegame parser

Savegame parser for X4: Foundations. Convert relevant parts of the XML files to quickly access 
useful game information.

Background: I often let my game run overnight for constructions to finish, or simply to let my 
traders fill the coffers. In the morning, it was becoming a hassle to find the status information 
that I needed in the logs, like whether I lost any of my ships to pirate attacks.

## Features

- Browser-based user interface
- Blueprints list
- Faction relations
- Faction standings 
- Categorized log messages (reputation changes, ship losses...)
- Convert relevant info to JSON files
- On the PHP side, object oriented access to information
- Process large savegames (1+ GB)

## Requirements

- PHP 7.4+
- Local webserver
- [Composer](https://getcomposer.org)

## Install

- Clone the repository somewhere in the webserver document root
- Run `composer install`
- Rename `config.dist.php` to `config.php`
- Edit the file to enter the relevant settings
- Extract some savegame XML files (see below)
- Open the repository in a browser

## Extract XML files

By default, X4 compresses savegame files as `.gz` archives. To be able to use the
tool, the target savegames must first be uncompressed. This can be done in two
ways:

1. Extract the XML files using a tool like [7-zip](https://7-zip.org)
2. Turn off savegame compression in X4's game settings

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

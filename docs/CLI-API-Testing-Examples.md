# CLI API Testing Examples

This document provides example commands to test the CLI API using the `quicksave` save file.

## Getting Started

### List Available Saves
```bash
./bin/query list-saves --pretty
```
Returns: All available saves (main and archived) with their extraction status.

This is useful to:
- See which saves are available
- Check if a save has been extracted (`isUnpacked`)
- Find the correct save name/ID to use in other commands

---

## Prerequisites

Ensure the save has been extracted first:
```bash
./bin/extract -e quicksave
```

---

## Basic Query Commands

### Get Save Information
```bash
./bin/query save-info --save=quicksave --pretty
```
Returns: Player name, money, save date, game start time, etc.

---

### Get Player Information
```bash
./bin/query player --save=quicksave --pretty
```
Returns: Player name, code, blueprints list, inventory wares.

---

### Get Game Statistics
```bash
./bin/query stats --save=quicksave --pretty
```
Returns: All game statistics (kills, trades, jumps, etc.)

---

### Get Faction Information
```bash
./bin/query factions --save=quicksave --pretty
```
Returns: All factions with relations to player.

---

## List Query Commands

### List All Blueprints
```bash
./bin/query blueprints --save=quicksave --pretty
```
Returns: All blueprints with ownership status.

---

### List Player Inventory
```bash
./bin/query inventory --save=quicksave --pretty
```
Returns: All items in player inventory.

---

### List Event Log
```bash
./bin/query log --save=quicksave --pretty
```
Returns: All event log entries.

---

### List Khaa'k Stations
```bash
./bin/query khaak-stations --save=quicksave --pretty
```
Returns: All detected Khaa'k hives and nests.

---

### List Ship Losses
```bash
./bin/query ship-losses --save=quicksave --pretty
```
Returns: All player ship losses.

---

## Collection Query Commands

### List All Ships
```bash
./bin/query ships --save=quicksave --pretty
```
Returns: All ships in the universe (player and NPC).

---

### List All Stations
```bash
./bin/query stations --save=quicksave --pretty
```
Returns: All stations in the universe.

---

### List All People
```bash
./bin/query people --save=quicksave --pretty
```
Returns: All NPCs and crew members.

---

### List All Sectors
```bash
./bin/query sectors --save=quicksave --pretty
```
Returns: All sectors.

---

### List All Zones
```bash
./bin/query zones --save=quicksave --pretty
```
Returns: All sector zones.

---

## Filtering Examples

### Owned Blueprints Only
```bash
./bin/query blueprints --save=quicksave --filter="[?owned==\`true\`]" --pretty
```

---

### Unowned Blueprints Only
```bash
./bin/query blueprints --save=quicksave --filter="[?owned==\`false\`]" --pretty
```

---

### Ship Blueprints Only
```bash
./bin/query blueprints --save=quicksave --filter="[?category=='ships']" --pretty
```

---

### Player-Owned Ships
```bash
./bin/query ships --save=quicksave --filter="[?owner=='player']" --pretty
```

---

### Ships by Faction (Argon)
```bash
./bin/query ships --save=quicksave --filter="[?faction=='argon']" --pretty
```

---

### Ships in Specific Sector
```bash
./bin/query ships --save=quicksave --filter="[?sector=='Argon Prime']" --pretty
```

---

### Player Stations
```bash
./bin/query stations --save=quicksave --filter="[?owner=='player']" --pretty
```

---

### People by Race (Argon)
```bash
./bin/query people --save=quicksave --filter="[?race=='argon']" --pretty
```

---

### Complex Filter: Owned Ship Blueprints
```bash
./bin/query blueprints --save=quicksave --filter="[?owned==\`true\` && category=='ships']" --pretty
```

---

## Projection Examples

### Ships with Selected Fields Only
```bash
./bin/query ships --save=quicksave --filter="[*].{name: name, sector: sector, faction: faction}" --pretty
```

---

### Blueprint Names Only
```bash
./bin/query blueprints --save=quicksave --filter="[*].name" --pretty
```

---

## Pagination Examples

### First 10 Ships
```bash
./bin/query ships --save=quicksave --limit=10 --offset=0 --pretty
```

---

### Next 10 Ships
```bash
./bin/query ships --save=quicksave --limit=10 --offset=10 --pretty
```

---

### Paginate Argon Ships with Caching
```bash
# First page (creates cache)
./bin/query ships --save=quicksave --filter="[?faction=='argon']" --limit=10 --offset=0 --cache-key=argon-ships --pretty

# Second page (uses cache)
./bin/query ships --save=quicksave --limit=10 --offset=10 --cache-key=argon-ships --pretty

# Third page (uses cache)
./bin/query ships --save=quicksave --limit=10 --offset=20 --cache-key=argon-ships --pretty
```

---

## Utility Commands

### List Available Saves
```bash
./bin/query list-saves --pretty
```
Shows all available saves (main folder and archived) with extraction status.

---

### Clear All Caches
```bash
./bin/query clear-cache --pretty
```

---

## Testing Error Handling

### Non-existent Save
```bash
./bin/query save-info --save=nonexistent --pretty
```
Expected: Error with suggestion to run `bin/extract -l`

---

### Unextracted Save
```bash
./bin/query save-info --save=autosave_01 --pretty
```
Expected: Error with suggestion to run `bin/extract -e autosave_01` (if not extracted)

---

### Invalid JMESPath Syntax
```bash
./bin/query ships --save=quicksave --filter="[?invalid syntax" --pretty
```
Expected: JMESPath syntax error

---

### Invalid Pagination
```bash
./bin/query ships --save=quicksave --limit=-5 --pretty
```
Expected: Validation error about negative limit

---

## Quick Test Suite

Run these commands in sequence to test all major features:

```bash
# 0. List available saves
./bin/query list-saves --pretty

# 1. Extract the save
./bin/extract -e quicksave

# 2. Test basic queries
./bin/query save-info --save=quicksave --pretty
./bin/query player --save=quicksave --pretty
./bin/query factions --save=quicksave --pretty

# 3. Test list queries
./bin/query blueprints --save=quicksave --pretty
./bin/query ships --save=quicksave --limit=5 --pretty

# 4. Test filtering
./bin/query ships --save=quicksave --filter="[?owner=='player']" --pretty
./bin/query blueprints --save=quicksave --filter="[?owned==\`true\`]" --pretty

# 5. Test pagination with caching
./bin/query ships --save=quicksave --limit=10 --offset=0 --cache-key=test --pretty
./bin/query ships --save=quicksave --limit=10 --offset=10 --cache-key=test --pretty

# 6. Clear cache
./bin/query clear-cache --pretty
```

---

## Windows-Specific Examples

On Windows, use `bin\query.bat` instead of `./bin/query`:

```batch
REM Get save info
bin\query.bat save-info --save=quicksave --pretty

REM List player ships
bin\query.bat ships --save=quicksave --filter="[?owner=='player']" --pretty

REM Paginate blueprints
bin\query.bat blueprints --save=quicksave --limit=20 --offset=0 --pretty
```

---

## Non-Pretty Output (for programmatic use)

Remove `--pretty` flag for compact JSON suitable for parsing:

```bash
./bin/query save-info --save=quicksave
./bin/query ships --save=quicksave --filter="[?owner=='player']"
```

---

## Notes

- Replace `quicksave` with any other save name (e.g., `autosave_01`, `save_001`)
- Use `./bin/extract -l` to see available save names
- All commands output valid JSON (pretty or compact)
- Exit code 0 = success, exit code 1 = error
- Errors are returned as JSON with detailed information

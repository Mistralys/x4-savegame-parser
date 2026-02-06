# Test Save Files

This directory contains minimal test save data for automated testing of the CLI API.

## Structure

```
unpack-20260206211435-quicksave/
├── analysis.json          # Save metadata
└── JSON/
    ├── collection-ships.json
    ├── collection-stations.json
    ├── collection-sectors.json
    ├── collection-people.json
    ├── collection-player.json
    ├── collection-event-log.json
    ├── data-losses.json
    └── savegame-info.json
```

## Purpose

These files provide minimal test data that allows the test suite to run without requiring a full game save extraction. The data is intentionally small and synthetic to keep the repository size manageable.

## Test Save Details

- **Folder Name**: `unpack-20260206211435-quicksave`
- **Save Name**: `quicksave`
- **Save ID**: `63466219`
- **Format**: Standard X4 Savegame Parser JSON output
- **Contents**:
  - 2 test ships
  - 2 test stations
  - 2 test sectors
  - 2 test NPCs
  - 1 player record
  - Minimal event log
  - Empty losses data

## Usage in Tests

Tests use the `TEST_SAVE_NAME` constant to reference this save by its save name (not the folder name):

```php
private const TEST_SAVE_NAME = 'quicksave';
```

Tests automatically skip if this save is not available, but with these files committed to git, all tests should run successfully.

## Adding More Test Data

If additional test data is needed:

1. Extract a real save: ` php  .\tests\extract-test-save.php`
2. Copy needed JSON files from the extraction
3. Minimize the data (keep only essential records)
4. Add to this directory
5. Update tests as needed

## Notes

- These files are **tracked in git** (exception to the `/tests/files/unpack*` ignore rule)
- Keep file sizes minimal - use only the data needed for tests
- Synthetic data is preferred over real player data for privacy

# X4 Savegame Parser Tests

Because of the size of X4 savegames and the amount of data to process,
the tests require a little preparation and take some time to run. 
I took the decision to work with a real savegame instead of working 
with a redacted version that may not be accurate enough.

## The Savegame Archive

The savegames are installed via Composer as a dependency: They are
stored in the package `mistralys/x4-savegame`. This keeps them separate
from the project's codebase.

They can be found in the folder:

- [X4 saves](/vendor/mistralys/x4-savegame/saves)

## Setting up tests

### 1. Extract the test saves

The bundled test saves are zipped, and must be extracted so it can be
used. (it is too large in its extracted form to be bundled). This can
be easily done with the command:

```bash
php ./tests/extract-test-saves.php
```


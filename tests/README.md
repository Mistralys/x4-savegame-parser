# X4 Savegame Parser Tests

Because of the size of X4 savegames and the amount of data to process,
the tests require a little preparation and take some time to run. 
I took the decision to work with a real savegame instead of working 
with a redacted version that may not be accurate enough.

## Setting up tests

### 1. Extract the test save

The bundled test save is zipped, and must be extracted so it can be
used. (it is too large in its extracted form to be bundled). This can
be easily done with the command:

```bash
php ./tests/extract-test-save.php
```


# NDJSON Monitor Interface

This document describes the machine-readable interface for the X4 Savegame Monitor. 
This interface is designed for "Launcher" applications that wrap the monitor process and need to display its status in a UI.

## Invocation

To enable the machine-readable output, pass the `--json` flag to the monitor script. A typical invocation execution flow:

```bash
php bin/php/run-monitor.php --json
# OR using the wrapper script on Linux/Mac
./bin/run-monitor --json
```

## Protocol

The output stream is **NDJSON** (Newline Delimited JSON).
- Each line printed to `STDOUT` is a valid, self-contained JSON object.
- Lines are separated by the system's `PHP_EOL` (usually `\n` or `\r\n`).
- The stream remains open as long as the monitor is running.

## Message Schema

Every message has a common structure. The `type` field determines how to handle the rest of the object.

```json
{
  "type": "event|tick|log|error",
  "timestamp": "2026-01-29T10:30:45+00:00", // ISO 8601 timestamp (UTC), present in all messages
  "name": "EVENT_NAME",    // Only for type="event"
  "payload": {},           // Only for type="event", optional
  "message": "Some text",  // Only for type="log" or "error"
  "level": "info|warn|error", // Only for type="log"
  "counter": 123           // Only for type="tick"
}
```

### Common Fields

- **timestamp**: ISO 8601 formatted timestamp in UTC timezone. Present in **all** messages for log synchronization.

## Message Types

### 1. Heartbeat (`tick`)

Sent periodically (configured by tick interval) to indicate the process is alive.

```json
{ 
  "type": "tick", 
  "counter": 42,
  "timestamp": "2026-01-29T10:30:45+00:00"
}
```

> **Recommendation**: These should be filtered out by the UI unless visualizing a "heartbeat" indicator.

### 2. Events (`event`)

Structured notifications of state changes. These are the most important messages for updating the UI state.

#### `MONITOR_STARTED`
Emitted immediately when the monitor initializes. Includes the monitor version for compatibility checking.

```json
{ 
  "type": "event", 
  "name": "MONITOR_STARTED", 
  "payload": {
    "version": "0.0.1"
  },
  "timestamp": "2026-01-29T10:30:45+00:00"
}
```

#### `SAVE_DETECTED`
Emitted when the monitor finds the most recent savegame file.

```json
{ 
  "type": "event", 
  "name": "SAVE_DETECTED", 
  "payload": {
    "name": "quicksave",
    "path": "/path/to/quicksave.xml.gz"
  },
  "timestamp": "2026-01-29T10:30:50+00:00"
}
```

#### `SAVE_PARSING_STARTED`
Emitted when the monitor decides to process a save (it hasn't been parsed before).

```json
{ 
  "type": "event", 
  "name": "SAVE_PARSING_STARTED", 
  "payload": {
    "name": "quicksave"
  },
  "timestamp": "2026-01-29T10:30:51+00:00"
}
```

#### `SAVE_UNZIPPING`
Emitted just before the unzipping process begins.

```json
{ 
  "type": "event", 
  "name": "SAVE_UNZIPPING", 
  "payload": [],
  "timestamp": "2026-01-29T10:30:52+00:00"
}
```

#### `SAVE_EXTRACTING`
Emitted when unzipping is done and XML extraction begins.

```json
{ 
  "type": "event", 
  "name": "SAVE_EXTRACTING", 
  "payload": [],
  "timestamp": "2026-01-29T10:31:05+00:00"
}
```

#### `SAVE_PARSING_COMPLETE`
Emitted when the entire process is finished for the current save.

```json
{ 
  "type": "event", 
  "name": "SAVE_PARSING_COMPLETE", 
  "payload": [],
  "timestamp": "2026-01-29T10:32:30+00:00"
}
```

### 3. Logging (`log`)

Detailed text logs. Only emitted if `loggingEnabled` configuration is enabled.

```json
{ 
  "type": "log",
  "level": "info",
  "message": "Updates are run every [5s].",
  "header": false, // Optional, true if it's a section header
  "timestamp": "2026-01-29T10:30:45+00:00"
}
```

#### Log Levels

- **info**: General informational messages (default)
- **warn**: Warning messages (future use)
- **error**: Error messages that don't halt execution (future use)

> **Note**: Currently all log messages use level `info`. Future versions may introduce `warn` and `error` levels.

### 4. Errors (`error`)

Emitted when a fatal exception occurs during monitor execution.

**Important**: Errors can occur at two points:
1. **During startup**: Caught by the wrapper script before the event loop starts
2. **During runtime**: Caught within the event loop (e.g., during save processing)

In both cases, an error message will be sent before the process terminates.

#### Error Structure

```json
{
  "type": "error",
  "message": "Top-level exception message",
  "code": 12345,
  "timestamp": "2026-01-29T10:30:45+00:00",
  "errors": [
    {
      "message": "Main exception message",
      "code": 12345,
      "class": "Mistralys\\X4\\SaveViewer\\SaveViewerException",
      "details": "Additional debug information",
      "trace": "#0 /path/to/file.php(10): ..."
    },
    {
      "message": "Previous exception message",
      "code": 67890,
      "class": "AppUtils\\FileHelper_Exception",
      "details": "More context about the underlying error",
      "trace": "#0 /other/file.php(20): ..."
    }
  ]
}
```

#### Fields

- **message**: Top-level exception message (same as `errors[0].message`)
- **code**: Top-level exception code (same as `errors[0].code`)
- **timestamp**: ISO 8601 timestamp when the error occurred
- **errors**: Array of exception objects in the chain, from top to bottom (most recent to root cause)

#### Exception Object Fields

Each object in the `errors` array contains:
- **message**: The exception message
- **code**: The exception code
- **class**: Fully qualified class name of the exception
- **trace**: Stack trace as a string
- **details**: *(Optional)* Additional debug information, only present for `AppUtils\BaseException` instances

#### Example: Simple Exception

```json
{
  "type": "error",
  "message": "File not found",
  "code": 404,
  "timestamp": "2026-01-29T10:30:45+00:00",
  "errors": [
    {
      "message": "File not found",
      "code": 404,
      "class": "RuntimeException",
      "trace": "#0 /path/to/file.php(10): ..."
    }
  ]
}
```

#### Example: Chained Exceptions

```json
{
  "type": "error",
  "message": "Cannot read savegame files from the saves folder.",
  "code": 136003,
  "timestamp": "2026-01-29T10:30:45+00:00",
  "errors": [
    {
      "message": "Cannot read savegame files from the saves folder.",
      "code": 136003,
      "class": "Mistralys\\X4\\SaveViewer\\SaveViewerException",
      "trace": "#0 /path/to/SaveSelector.php(239): ..."
    },
    {
      "message": "Permission denied",
      "code": 13,
      "class": "AppUtils\\FileHelper_Exception",
      "details": "Failed to open directory: /path/to/saves",
      "trace": "#0 /path/to/FileHelper.php(150): ..."
    }
  ]
}
```

**After sending an error message**:
- The event loop will be stopped
- The process will exit with code `1`
- No further messages will be sent

## Example Stream

Here is an example of what the stream looks like when a new savegame is detected and processed:

```json
{"type":"event","name":"MONITOR_STARTED","payload":{"version":"0.0.1"},"timestamp":"2026-01-29T10:30:00+00:00"}
{"type":"log","level":"info","message":"--------------------------------------------","header":true,"timestamp":"2026-01-29T10:30:00+00:00"}
{"type":"log","level":"info","message":"X4 Savegame unpacker","header":true,"timestamp":"2026-01-29T10:30:00+00:00"}
{"type":"log","level":"info","message":"--------------------------------------------","header":true,"timestamp":"2026-01-29T10:30:00+00:00"}
{"type":"tick","counter":1,"timestamp":"2026-01-29T10:30:05+00:00"}
{"type":"log","level":"info","message":"--------------------------------------------","header":true,"timestamp":"2026-01-29T10:30:05+00:00"}
{"type":"log","level":"info","message":"Handling tick [1]","header":true,"timestamp":"2026-01-29T10:30:05+00:00"}
{"type":"log","level":"info","message":"--------------------------------------------","header":true,"timestamp":"2026-01-29T10:30:05+00:00"}
{"type":"log","level":"info","message":"Latest savegame is [save_001].","timestamp":"2026-01-29T10:30:05+00:00"}
{"type":"event","name":"SAVE_DETECTED","payload":{"name":"save_001","path":"..."},"timestamp":"2026-01-29T10:30:05+00:00"}
{"type":"log","level":"info","message":"> Parsing.","timestamp":"2026-01-29T10:30:05+00:00"}
{"type":"event","name":"SAVE_PARSING_STARTED","payload":{"name":"save_001"},"timestamp":"2026-01-29T10:30:05+00:00"}
{"type":"log","level":"info","message":"...Unzipping.","timestamp":"2026-01-29T10:30:05+00:00"}
{"type":"event","name":"SAVE_UNZIPPING","payload":[],"timestamp":"2026-01-29T10:30:05+00:00"}
{"type":"log","level":"info","message":"...Extracting and writing files.","timestamp":"2026-01-29T10:30:10+00:00"}
{"type":"event","name":"SAVE_EXTRACTING","payload":[],"timestamp":"2026-01-29T10:30:10+00:00"}
{"type":"log","level":"info","message":"...Done.","timestamp":"2026-01-29T10:31:45+00:00"}
{"type":"event","name":"SAVE_PARSING_COMPLETE","payload":[],"timestamp":"2026-01-29T10:31:45+00:00"}
{"type":"tick","counter":2,"timestamp":"2026-01-29T10:31:45+00:00"}
```

## Version Compatibility

The `version` field in the `MONITOR_STARTED` event allows the launcher to verify compatibility with the monitor. 

- **Current Version**: `0.0.1`
- Future versions may add new event types or fields
- Launchers should gracefully handle unknown event names or unexpected fields

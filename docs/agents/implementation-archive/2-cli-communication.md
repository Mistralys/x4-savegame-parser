# CLI Monitor Communication Protocol

## Objective
Formalize the communication channel between the CLI monitor script (`run-monitor.php`) and external launcher applications. The goal is to provide a reliable, machine-parseable stream of events and status updates without relying on screen scraping.

## Protocol: NDJSON
The communication will use **NDJSON (Newline Delimited JSON)** over `STDOUT`.
Each line of output will be a valid JSON object representing a distinct message.

### Message Structure
```json
{
    "type": "event|tick|log|error",
    "name": "EVENT_NAME", // Optional, for specific events
    "message": "Human readable message", // Optional
    "payload": { ... } // Optional data payload
}
```

### Message Types
1.  **tick**: Heartbeat events sent every monitoring cycle.
    *   Useful for confirming the process is alive.
    *   Can be filtered out by the launcher to avoid log noise.
2.  **event**: Structured notifications of state changes.
    *   Examples: `SAVE_DETECTED`, `PARSING_STARTED`, `PARSING_COMPLETE`.
3.  **log**: Informational text messages.
    *   Sent only if `X4_MONITOR_LOGGING` is enabled.
4.  **error**: Fatal errors or exceptions.

## Architecture

### 1. Output Abstraction
A new `MonitorOutputInterface` will decouple the monitor logic from the output mechanism.

```php
interface MonitorOutputInterface {
    public function log(string $message, ...$args): void;
    public function logHeader(string $title, ...$args): void;
    public function tick(int $counter): void;
    public function notify(string $eventName, array $payload = []): void;
}
```

### 2. Output Strategies
*   **ConsoleOutput**: Default implementation using `League\CLImate` for styled, human-friendly terminal text.
*   **JsonOutput**: Implementation that streams NDJSON objects to `STDOUT`.

## Implementation Plan

1.  **Create Interface**: Define `MonitorOutputInterface` in `src/X4/SaveViewer/Monitor/Output`.
2.  **Implement Strategies**:
    *   `ConsoleOutput`: Wraps `CLImate`.
    *   `JsonOutput`: Uses `json_encode` on `STDOUT`.
3.  **Refactor BaseMonitor**:
    *   Inject `MonitorOutputInterface`.
    *   Replace direct `echo` calls with interface methods.
    *   Add `notify(string $event, array $data)` helper.
4.  **Update X4Monitor**:
    *   Emit structured events for key lifecycle steps (Found Save, Unzipping, Done).
5.  **Update Wrapper Script (`run-monitor.php`)**:
    *   Detect `--json` command line argument.
    *   Select appropriate output strategy.
6.  **Error Handling (`prepend.php`)**:
    *   Ensure fatal exceptions produce a JSON error object when running in JSON mode.

## Future Considerations
*   **Input Channel**: Listen to `STDIN` for commands from the launcher (e.g., "Force Update", "Shutdown").

## Implementation Status

### Completed Features

#### WP1: Output Infrastructure ✅
- Created `MonitorOutputInterface` with methods: `log()`, `logHeader()`, `tick()`, `notify()`, and `error()`
- Implemented `ConsoleOutput` using `League\CLImate` for styled terminal output
- Implemented `JsonOutput` for NDJSON streaming with:
  - ISO 8601 timestamps (UTC) on all messages
  - Monitor version injection in `MONITOR_STARTED` event
  - Log level field (`info`) in log messages

#### WP2: Monitor Integration ✅
- Refactored `BaseMonitor` to use `MonitorOutputInterface`
- Added `setOutput()` method for dependency injection
- Added `notify()` method for structured events
- Added `notifyError()` method for error handling
- Updated `handleTick()` with try-catch to capture runtime exceptions
- Updated `X4Monitor` to emit lifecycle events:
  - `MONITOR_STARTED` (with version)
  - `SAVE_DETECTED` (with name and path)
  - `SAVE_PARSING_STARTED`
  - `SAVE_UNZIPPING`
  - `SAVE_EXTRACTING`
  - `SAVE_PARSING_COMPLETE`
- Added error handling within Promise to catch parsing exceptions

#### WP3: CLI Entry Point & Error Handling ✅
- Updated `run-monitor.php` to detect `--json` flag
- Instantiates appropriate output strategy based on flag
- Updated `prepend.php` error handler to output JSON errors with timestamps
- Error handling now works at two levels:
  - Startup errors (before event loop)
  - Runtime errors (within event loop)

### Error Handling Implementation

The monitor now properly notifies the launcher of errors through:

1. **Interface Method**: `MonitorOutputInterface::error(\Throwable $e)`
   - `JsonOutput`: Sends structured JSON error with hierarchical exception chain
   - `ConsoleOutput`: Displays formatted error with full chain and indentation

2. **Hierarchical Exception Structure**:
   - Top-level fields: `message`, `code`, `timestamp`
   - `errors` array containing full exception chain
   - Each exception includes: `message`, `code`, `class`, `trace`, and optional `details` (for BaseException)
   - Walks the chain using `getPrevious()` to capture root causes

3. **Runtime Error Catching**:
   - `BaseMonitor::handleTick()` wraps tick processing in try-catch
   - Calls `notifyError()` before stopping the loop and exiting
   - `X4Monitor` Promise has try-catch to handle parsing exceptions

4. **Startup Error Catching**:
   - `prepend.php` catches exceptions during `monitor->start()`
   - Sends JSON error with full chain if `--json` flag is present

5. **BaseException Integration**:
   - Automatically detects `AppUtils\BaseException` instances
   - Includes `getDetails()` output in the `details` field
   - Works for all exceptions in the chain

### Example Error Output

**Simple Exception**:
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

**Chained Exception with BaseException**:
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

This ensures the launcher receives complete error context including the full exception chain and detailed debug information, making it easy to diagnose issues and display meaningful error messages to users.

## Work Packages

### WP1: Output Infrastructure
Create the foundation for the new output system.

1.  **Create Namespace**: `src/X4/SaveViewer/Monitor/Output`
2.  **Define Interface**: `MonitorOutputInterface.php`
    *   Methods: `log`, `logHeader`, `tick`, `notify`
3.  **Implement Console Strategy**: `ConsoleOutput.php`
    *   Use `League\CLImate` for formatting.
    *   Implement fallback if CLImate is missing (optional).
4.  **Implement JSON Strategy**: `JsonOutput.php`
    *   Implement NDJSON formatting using `json_encode`.
    *   Ensure unescaped slashes and unicode for readability where possible.

### WP2: Monitor Integration
Connect the monitor logic to the new output system.

1.  **Refactor BaseMonitor**:
    *   Add `protected MonitorOutputInterface $output` property.
    *   Add `setOutput(MonitorOutputInterface $output)` method.
    *   Update `log()` and `logHeader()` to use `$this->output`.
    *   Update `handleTick()` to call `$this->output->tick()`.
    *   Add `notify()` method delegating to `$this->output`.
2.  **Update X4Monitor**:
    *   Identify key lifecycle events in the code.
    *   Replace/Augment `log()` calls with `notify()` for:
        *   Monitoring started
        *   Savegame detected (with filename)
        *   Unpacking started
        *   Parsing started
        *   Process completed

### WP3: CLI Entry Point & Error Handling
Enable the new mode via command line arguments.

1.  **Update `run-monitor.php`**:
    *   Parse internal arguments (check for `--json`).
    *   User `in_array('--json', $argv)` for dependency-free checking.
    *   Instantiate `JsonOutput` or `ConsoleOutput` based on flag.
    *   Configure the monitor with the created output instance.
2.  **Update `prepend.php`**:
    *   Detect mode in global scope or pass state to error handler.
    *   Modify `runMonitor` error catching block.
    *   If in JSON mode, catch `Throwable`, serialize to JSON error object, and output to STDOUT.

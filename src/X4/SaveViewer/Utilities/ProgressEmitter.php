<?php
declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Utilities;

use DateTime;
use DateTimeZone;

/**
 * Utility for emitting progress events during long-running operations.
 * 
 * When JSON_OUTPUT_MODE constant is defined and true, emits NDJSON events
 * to STDOUT for consumption by external tools (e.g., Tauri launcher).
 * 
 * Event Format:
 * {
 *   "type": "progress",
 *   "name": "OPERATION_NAME",
 *   "status": "started|progress|complete",
 *   "payload": {...},  // optional
 *   "timestamp": "2026-02-08T10:30:00+00:00"
 * }
 */
class ProgressEmitter
{
    /**
     * Check if JSON output mode is enabled
     */
    private static function isJsonMode(): bool
    {
        return defined('JSON_OUTPUT_MODE') && JSON_OUTPUT_MODE === true;
    }

    /**
     * Emit a progress event to STDOUT (JSON mode) or do nothing
     */
    private static function emit(string $name, string $status, array $payload = []): void
    {
        if (!self::isJsonMode()) {
            return;
        }

        $event = [
            'type' => 'progress',
            'name' => $name,
            'status' => $status,
            'timestamp' => (new DateTime('now', new DateTimeZone('UTC')))->format('c')
        ];

        if (!empty($payload)) {
            $event['payload'] = $payload;
        }

        echo json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        flush();
    }

    /**
     * Emit operation started event
     */
    public static function emitStarted(string $operation, array $payload = []): void
    {
        self::emit($operation, 'started', $payload);
    }

    /**
     * Emit operation progress event
     */
    public static function emitProgress(string $operation, array $payload = []): void
    {
        self::emit($operation, 'progress', $payload);
    }

    /**
     * Emit operation complete event
     */
    public static function emitComplete(string $operation, array $payload = []): void
    {
        self::emit($operation, 'complete', $payload);
    }
}

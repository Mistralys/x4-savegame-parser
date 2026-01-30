<?php
/**
 * @package X4SaveViewer
 * @subpackage CLI
 * @see \Mistralys\X4\SaveViewer\CLI\JsonResponseBuilder
 */

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\CLI;

use AppUtils\BaseException;
use AppUtils\FileHelper;
use DateTime;
use DateTimeZone;
use Throwable;

/**
 * Helper class for building standardized JSON responses for the CLI API.
 *
 * Provides consistent response envelopes for both success and error cases,
 * matching the format used by the monitor's NDJSON interface.
 *
 * @package X4SaveViewer
 * @subpackage CLI
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */
class JsonResponseBuilder
{
    /**
     * Build a success response with the standard envelope.
     *
     * @param string $command The command that was executed
     * @param mixed $data The response data (array or object)
     * @param array|null $pagination Optional pagination metadata
     * @param bool $pretty Whether to pretty-print the JSON
     * @return string JSON-encoded response
     */
    public static function success(
        string $command,
        mixed $data,
        ?array $pagination = null,
        bool $pretty = false
    ): string
    {
        $response = [
            'success' => true,
            'version' => self::getVersion(),
            'command' => $command,
            'timestamp' => self::getTimestamp(),
            'data' => $data
        ];

        if ($pagination !== null) {
            $response['pagination'] = $pagination;
        }

        return self::encode($response, $pretty);
    }

    /**
     * Build an error response with the standard envelope and exception chain.
     *
     * @param Throwable $e The exception that occurred
     * @param string|null $command The command that was being executed (if known)
     * @param bool $pretty Whether to pretty-print the JSON
     * @return string JSON-encoded error response
     */
    public static function error(
        Throwable $e,
        ?string $command = null,
        bool $pretty = false
    ): string
    {
        $response = [
            'success' => false,
            'version' => self::getVersion(),
            'timestamp' => self::getTimestamp(),
            'type' => 'error',
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'errors' => self::buildErrorChain($e)
        ];

        if ($command !== null) {
            $response['command'] = $command;
        }

        // Add actionable suggestions if available (from QueryValidationException)
        if (method_exists($e, 'getActions')) {
            $actions = $e->getActions();
            if (!empty($actions)) {
                $response['actions'] = $actions;
            }
        }

        return self::encode($response, $pretty);
    }

    /**
     * Read the project version from the VERSION file.
     *
     * @return string The version string (e.g., "0.1.0")
     */
    private static function getVersion(): string
    {
        static $version = null;

        if ($version === null) {
            $versionFile = __DIR__ . '/../../../../VERSION';
            $version = trim(FileHelper::readContents($versionFile));
        }

        return $version;
    }

    /**
     * Get the current timestamp in ISO 8601 format (UTC).
     *
     * @return string ISO 8601 timestamp (e.g., "2026-01-30T14:23:45+00:00")
     */
    private static function getTimestamp(): string
    {
        $dt = new DateTime('now', new DateTimeZone('UTC'));
        return $dt->format('c');
    }

    /**
     * Build the exception chain with details for all nested exceptions.
     *
     * @param Throwable $e The exception to process
     * @return array<int,array> Array of exception details
     */
    private static function buildErrorChain(Throwable $e): array
    {
        $errors = [];
        $current = $e;

        while ($current !== null) {
            $errorData = [
                'message' => $current->getMessage(),
                'code' => $current->getCode(),
                'class' => get_class($current),
                'trace' => $current->getTraceAsString()
            ];

            // Add detailed debug information for BaseException
            if ($current instanceof BaseException) {
                $details = $current->getDetails();
                if (!empty($details)) {
                    $errorData['details'] = $details;
                }
            }

            $errors[] = $errorData;
            $current = $current->getPrevious();
        }

        return $errors;
    }

    /**
     * Encode the response array as JSON with appropriate flags.
     *
     * @param array $response The response data to encode
     * @param bool $pretty Whether to pretty-print the JSON
     * @return string JSON-encoded string
     */
    private static function encode(array $response, bool $pretty): string
    {
        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return json_encode($response, $flags);
    }
}

<?php
/**
 * @package X4SaveViewer
 * @subpackage CLI
 * @see \Mistralys\X4\SaveViewer\CLI\OutputManager
 */

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\CLI;

use AppUtils\BaseException;
use DateTime;
use DateTimeZone;
use Mistralys\X4\SaveViewer\Monitor\BaseMonitor;
use Throwable;

/**
 * Manages output formatting for CLI and JSON modes.
 *
 * Handles exception output in either JSON or CLI format based on
 * command-line arguments.
 *
 * @package X4SaveViewer
 * @subpackage CLI
 */
class OutputManager
{
    /**
     * Handles an exception by outputting it in either JSON or CLI format
     * based on the presence of the JSON output flag in the command-line arguments.
     *
     * @param Throwable $e The exception to handle
     * @return never
     */
    public static function handleException(Throwable $e) : never
    {
        global $argv;

        if (in_array(BaseMonitor::ARG_JSON_OUTPUT, $argv ?? [], true)) {
            self::outputJsonError($e);
        }

        self::outputCliError($e);
    }

    /**
     * Outputs an exception as JSON and exits.
     *
     * @param Throwable $e The exception to output
     * @return never
     */
    public static function outputJsonError(Throwable $e) : never
    {
        $dt = new DateTime('now', new DateTimeZone('UTC'));

        $errors = [];
        $current = $e;

        // Build the exception chain
        while ($current !== null) {
            $errorData = [
                'message' => $current->getMessage(),
                'code' => $current->getCode(),
                'class' => get_class($current),
                'trace' => $current->getTraceAsString()
            ];

            if ($current instanceof BaseException) {
                $errorData['details'] = $current->getDetails();
            }

            $errors[] = $errorData;
            $current = $current->getPrevious();
        }

        echo json_encode([
            'type' => 'error',
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'errors' => $errors,
            'timestamp' => $dt->format('c')
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        exit(1);
    }

    /**
     * Outputs an exception as CLI text and exits.
     *
     * @param Throwable $e The exception to output
     * @return never
     */
    public static function outputCliError(Throwable $e) : never
    {
        die(
            'An exception occurred. '.PHP_EOL.
            'Message: ['.$e->getMessage().'] '.PHP_EOL.
            'Code: ['.$e->getCode().'] '.PHP_EOL
        );
    }
}

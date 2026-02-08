<?php
/**
 * CLI Query entry point for the X4 Savegame Parser.
 *
 * Provides programmatic access to all savegame data via JSON output.
 *
 * @package SaveViewer
 */

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Bin;

// Check for --json flag early (before classes load)
$jsonMode = in_array('--json', $argv) || in_array('-j', $argv);

if ($jsonMode) {
    define('JSON_OUTPUT_MODE', true);
}

use Mistralys\X4\SaveViewer\CLI\QueryHandler;
use Mistralys\X4\SaveViewer\CLI\JsonResponseBuilder;

require_once __DIR__.'/prepend.php';

try {
    QueryHandler::createFromConfig()->handle();
    exit(0);
} catch (\Throwable $e) {
    echo JsonResponseBuilder::error($e, null, false);
    exit(1);
}

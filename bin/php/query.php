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

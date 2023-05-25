<?php
/**
 * Server script: starts the savegame management server
 * in the background.
 */

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Bin;

use Mistralys\X4\SaveViewer\Monitor\X4Server;

require_once __DIR__.'/prepend.php';

runMonitor(new X4Server());

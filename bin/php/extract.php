<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Bin;

use Mistralys\X4\SaveViewer\CLI\CLIHandler;

require_once __DIR__.'/prepend.php';

CLIHandler::createFromConfig()
    ->handle();

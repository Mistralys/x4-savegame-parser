<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Bin;

use Mistralys\X4\SaveViewer\CLI\OutputManager;
use Mistralys\X4\SaveViewer\Monitor\BaseMonitor;
use Throwable;

require_once __DIR__.'/../../prepend.php';

function runMonitor(BaseMonitor $monitor) : void
{
    try
    {
        $monitor->start();
    }
    catch (Throwable $e)
    {
        OutputManager::handleException($e);
    }
}

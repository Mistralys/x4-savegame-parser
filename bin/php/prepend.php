<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Bin;

use Mistralys\X4\SaveViewer\Monitor\BaseMonitor;
use Throwable;

require_once __DIR__.'/../../vendor/autoload.php';
require_once __DIR__.'/../../config.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');

function runMonitor(BaseMonitor $monitor) : void
{
    try
    {
        $monitor->start();
    }
    catch (Throwable $e)
    {
        die(
            'An exception occurred. '.PHP_EOL.
            'Message: ['.$e->getMessage().'] '.PHP_EOL.
            'Code: ['.$e->getCode().'] '.PHP_EOL
        );
    }
}

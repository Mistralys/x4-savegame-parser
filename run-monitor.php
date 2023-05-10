<?php
/**
 * Savegame monitoring script: Observes the savegame list
 * and automatically unpacks the data of the most recent
 * savegame in the list.
 *
 * @package SaveViewer
 */

declare(strict_types=1);

use Mistralys\X4\SaveViewer\Monitor\X4Monitor;

require_once 'vendor/autoload.php';
require_once 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');

try
{
    $monitor = new X4Monitor();
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

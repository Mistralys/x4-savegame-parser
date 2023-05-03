<?php
/**
 * Server script: starts the savegame management server
 * in the background.
 */

declare(strict_types=1);

use Mistralys\X4\SaveViewer\Monitor\X4Server;

require_once 'vendor/autoload.php';
require_once 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');

$server = new X4Server();
$server->start();

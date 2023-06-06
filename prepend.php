<?php
/**
 * Main application entry point: Loads the configuration
 * and sets up the autoloader.
 *
 * @package SaveViewer
 * @subpackage Configuration
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer;

$autoloader = __DIR__.'/../../vendor/autoload.php';
if(!file_exists($autoloader)) {
    die('Autoloader not found. Please run `composer install` first.');
}

require_once $autoloader;

$configFile = __DIR__.'/../../config.php';
if(!file_exists($configFile)) {
    die(sprintf('Configuration not found. Please create the `%s` file first.', basename($configFile)));
}

require_once $configFile;

$configKeys = array(
    'X4_FOLDER' => null,
    'X4_STORAGE_FOLDER' => null,
    'X4_SERVER_HOST' => 'localhost',
    'X4_SERVER_PORT' => 9494,
    'X4_MONITOR_AUTO_BACKUP' => true,
    'X4_MONITOR_KEEP_XML' => false,
    'X4_MONITOR_LOGGING' => false
);

foreach($configKeys as $key => $default)
{
    if(defined($key)) {
        continue;
    }

    if($default === null) {
        die(sprintf(
            'The configuration setting `%s` has not been set. Please add it in `%s`.',
            $key,
            basename($configFile)
        ));
    }

    define($key, $default);
}

if(!defined('X4_SAVES_FOLDER')) {
    define('X4_SAVES_FOLDER', X4_FOLDER.'\save');
}

error_reporting(E_ALL);
ini_set('display_errors', '1');

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

$autoloader = __DIR__.'/vendor/autoload.php';
if(!file_exists($autoloader)) {
    die('Autoloader not found. Please run `composer install` first.');
}

require_once $autoloader;

// Load JSON-backed configuration via the new Config class
use Mistralys\X4\SaveViewer\Config\Config;

try {
    // Falls back to config.dist.json if config.json is missing
    Config::ensureLoaded();
} catch (\Throwable $e) {
    die('Configuration error: '.$e->getMessage());
}

error_reporting(E_ALL);
ini_set('display_errors', '1');

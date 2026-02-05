<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Mistralys\X4\SaveViewer\Config\Config;

// Provide test-specific config values
Config::setForTests(array());

Config::setGameFolder(__DIR__.'/files');
Config::setSavesFolder(__DIR__.'/files');
Config::setStorageFolder(__DIR__.'/files/test-saves');
Config::setTestSuiteEnabled(true);

// For the AppUtils library test suite
const APP_UTILS_TESTSUITE = true;

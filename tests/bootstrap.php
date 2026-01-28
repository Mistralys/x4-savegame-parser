<?php

require_once __DIR__.'/../vendor/autoload.php';

use Mistralys\X4\SaveViewer\Config\Config;

// Provide test-specific config values
Config::setForTests(array());

Config::setSavesFolder(__DIR__.'/files');
Config::setStorageFolder(__DIR__.'/files');
Config::setTestSuiteEnabled(true);

// For the AppUtils library test suite
const APP_UTILS_TESTSUITE = true;

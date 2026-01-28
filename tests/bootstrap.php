<?php

require_once __DIR__.'/../vendor/autoload.php';

use Mistralys\X4\SaveViewer\Config\Config;

// Provide test-specific config values
Config::setForTests(array(
    'X4_SAVES_FOLDER' => __DIR__.'/files',
    'X4_STORAGE_FOLDER' => __DIR__.'/files',
    'X4_TESTSUITE' => true,
    'APP_UTILS_TESTSUITE' => true
));

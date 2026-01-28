<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Mistralys\X4\SaveViewer\Config\Config;

Config::setForTests(['X4_FOLDER' => 'test/folder']);

$folder = Config::getGameFolder();

if ($folder === 'test/folder') {
    echo "SUCCESS: Legacy key X4_FOLDER mapped to gameFolder correctly.\n";
} else {
    echo "FAILURE: Expected 'test/folder', got '$folder'.\n";
    exit(1);
}

Config::setForTests(['gameFolder' => 'new/folder', 'X4_FOLDER' => 'old/folder']);
$folder = Config::getGameFolder();

if ($folder === 'new/folder') {
    echo "SUCCESS: New key gameFolder takes precedence.\n";
} else {
    echo "FAILURE: Expected 'new/folder', got '$folder'.\n";
    exit(1);
}

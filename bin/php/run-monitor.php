<?php
/**
 * Savegame monitoring script: Observes the savegame list
 * and automatically unpacks the data of the most recent
 * savegame in the list.
 *
 * @package SaveViewer
 */

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Bin;

use Mistralys\X4\SaveViewer\Monitor\BaseMonitor;
use Mistralys\X4\SaveViewer\Monitor\X4Monitor;
use Mistralys\X4\SaveViewer\Config\Config;
use Mistralys\X4\SaveViewer\Monitor\Output\ConsoleOutput;
use Mistralys\X4\SaveViewer\Monitor\Output\JsonOutput;

require_once __DIR__.'/prepend.php';

$output = new ConsoleOutput();
if(in_array(BaseMonitor::ARG_JSON_OUTPUT, $argv)) {
    $output = new JsonOutput();
}

runMonitor(new X4Monitor()
    ->setOutput($output)
    ->optionKeepXML(Config::isKeepXMLFiles())
    ->optionAutoBackup(Config::isAutoBackupEnabled())
    ->optionLogging(Config::isLoggingEnabled())
);

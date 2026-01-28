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

use Mistralys\X4\SaveViewer\Monitor\X4Monitor;
use Mistralys\X4\SaveViewer\Config\Config;

require_once __DIR__.'/prepend.php';

runMonitor((new X4Monitor())
    ->optionKeepXML(Config::getBool('X4_MONITOR_KEEP_XML', false))
    ->optionAutoBackup(Config::getBool('X4_MONITOR_AUTO_BACKUP', true))
    ->optionLogging(Config::getBool('X4_MONITOR_LOGGING', false))
);

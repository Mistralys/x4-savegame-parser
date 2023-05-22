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

require_once __DIR__.'/prepend.php';

runMonitor((new X4Monitor())
    ->optionKeepXML(X4_MONITOR_KEEP_XML)
    ->optionAutoBackup(X4_MONITOR_AUTO_BACKUP)
    ->optionLogging(X4_MONITOR_LOGGING)
);

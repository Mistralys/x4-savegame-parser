<?php

/**
 * Absolute path to the folder in which the X4 savegames are stored.
 */
const X4_SAVES_FOLDER = 'C:\Users\Someone\Documents\Egosoft\X4\70814229\save';

/**
 * Absolute path to where unpacked savegame data and backups are stored.
 *
 * Note: Will be created if it does not exist.
 */
const X4_STORAGE_FOLDER = __DIR__.'/archived-saves';

/**
 * Server host from which to run the UI.
 * This is typically "localhost" or "127.0.0.1".
 *
 * When in doubt, leave it as it is.
 */
const X4_SERVER_HOST = 'localhost';

/**
 * Server port to use to access the UI.
 * You can change this to any open port on your machine.
 *
 * When in doubt, leave it as it is.
 */
const X4_SERVER_PORT = 9494;

/**
 * Whether to include a backup of the target savegame
 * when extracting the data.
 *
 * Creates a "backup.gz" file in the unpacked save folder.
 */
const X4_MONITOR_AUTO_BACKUP = true;

/**
 * Whether to keep the extracted XML fragment files once
 * the data has been extracted, when using the save monitor.
 *
 * NOTE: These files can take up a lot of space, especially
 * when letting the game run unsupervised, and depending on
 * the delay configured in game between auto-saves.
 */
const X4_MONITOR_KEEP_XML = false;

/**
 * Whether to display log messages from the savegame parsing
 * process in the monitor.
 */
const X4_MONITOR_LOGGING = false;

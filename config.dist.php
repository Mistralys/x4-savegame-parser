<?php

/**
 * Absolute path to the folder in which the X4 savegames are stored.
 */
const X4_SAVES_FOLDER = 'C:\Users\Argh\Documents\Egosoft\X4\70814229\save';

/**
 * Absolute path to where unpacked savegame data and backups are stored.
 * Note: Will be created if it does not exist.
 */
const X4_STORAGE_FOLDER = __DIR__.'/archived-saves';

/**
 * Server host from which to run the UI.
 * This is typically "localhost" or "127.0.0.1".
 */
const X4_SERVER_HOST = 'localhost';

/**
 * Server port to use to access the UI.
 * You can change this to any open port on your machine.
 */
const X4_SERVER_PORT = 9494;

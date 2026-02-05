<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Config;

use AppUtils\FileHelper\FolderInfo;
use AppUtils\FileHelper\JSONFile;
use AppUtils\FileHelper_Exception;

class Config
{
    // Configuration key constants
    public const string KEY_SAVES_FOLDER = 'savesFolder';
    public const string KEY_GAME_FOLDER = 'gameFolder';
    public const string KEY_STORAGE_FOLDER = 'storageFolder';
    public const string KEY_VIEWER_HOST = 'viewerHost';
    public const string KEY_VIEWER_PORT = 'viewerPort';
    public const string KEY_AUTO_BACKUP_ENABLED = 'autoBackupEnabled';
    public const string KEY_KEEP_XML_FILES = 'keepXMLFiles';
    public const string KEY_TEST_SUITE_ENABLED = 'testSuiteEnabled';
    public const string KEY_LOGGING_ENABLED = 'loggingEnabled';

    private static ?Config $instance = null;

    private array $data = [];

    private function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function loadFromFile(string $path) : void
    {
        $file = JSONFile::factory($path);

        if(!$file->exists()) {
            throw new ConfigException('Config file not found.');
        }

        try
        {
            $json = $file->parse();
        }
        catch (FileHelper_Exception $e)
        {
            throw new ConfigException(sprintf('Invalid JSON in config file: %s', $path), null, $e->getCode(), $e);
        }

        self::$instance = new self($json);
    }

    public static function ensureLoaded() : void
    {
        if(self::$instance !== null) {
            return;
        }

        self::loadFromFile(__DIR__ . '/../../../../config.json');
    }

    public static function get(string $key, $default = null)
    {
        self::ensureLoaded();

        if (array_key_exists($key, self::$instance->data)) {
            return self::$instance->data[$key];
        }

        return $default;
    }

    public static function getString(string $key, string $default = '') : string
    {
        $val = self::get($key, $default);
        return is_scalar($val) ? (string)$val : $default;
    }

    public static function getInt(string $key, int $default = 0) : int
    {
        $val = self::get($key, $default);
        return is_numeric($val) ? (int)$val : $default;
    }

    public static function getBool(string $key, bool $default = false) : bool
    {
        $val = self::get($key, $default);
        if(is_bool($val)) {
            return $val;
        }

        if(is_string($val)) {
            $lower = strtolower($val);
            if($lower === 'true' || $lower === '1') {
                return true;
            }
            if($lower === 'false' || $lower === '0') {
                return false;
            }
        }

        return (bool)$val;
    }

    public static function has(string $key) : bool
    {
        self::ensureLoaded();

        return array_key_exists($key, self::$instance->data);
    }

    /**
     * Test helper: override config with provided data (in memory)
     */
    public static function setForTests(array $data) : void
    {
        self::$instance = new self($data);
    }

    public static function getSavesFolder() : FolderInfo
    {
        $path = self::getString(self::KEY_SAVES_FOLDER);

        if(empty($path)) {
            throw new ConfigException('Configuration error: savesFolder is not set or is empty.');
        }

        return FolderInfo::factory($path);
    }

    public static function getGameFolder() : FolderInfo
    {
        $path = self::getString(self::KEY_GAME_FOLDER);

        if(empty($path)) {
            throw new ConfigException('Configuration error: gameFolder is not set or is empty.');
        }

        return FolderInfo::factory($path);
    }

    public static function getStorageFolder() : FolderInfo
    {
        // Check if storageFolder is explicitly set in config
        $path = self::getString(self::KEY_STORAGE_FOLDER);

        if(!empty($path)) {
            return FolderInfo::factory($path);
        }

        // Default: create archived-saves folder in the same directory as the saves folder
        // This places it alongside the user's savegame files, not in the game installation
        return FolderInfo::factory(self::getSavesFolder() . DIRECTORY_SEPARATOR . 'archived-saves');
    }

    public static function getViewerHost() : string
    {
        return self::getString(self::KEY_VIEWER_HOST, 'localhost');
    }

    public static function getViewerPort() : int
    {
        return self::getInt(self::KEY_VIEWER_PORT, 9494);
    }

    public static function isAutoBackupEnabled() : bool
    {
        return self::getBool(self::KEY_AUTO_BACKUP_ENABLED, true);
    }

    public static function isKeepXMLFiles() : bool
    {
        return self::getBool(self::KEY_KEEP_XML_FILES, false);
    }

    public static function isTestSuiteEnabled() : bool
    {
        return self::getBool(self::KEY_TEST_SUITE_ENABLED, false);
    }

    public static function isLoggingEnabled() : bool
    {
        return self::getBool(self::KEY_LOGGING_ENABLED, false);
    }

    public static function set(string $key, $value) : void
    {
        self::ensureLoaded();
        self::$instance->data[$key] = $value;
    }

    public static function setGameFolder(string $value) : void
    {
        self::set(self::KEY_GAME_FOLDER, $value);
    }

    public static function setStorageFolder(string $value) : void
    {
        self::set(self::KEY_STORAGE_FOLDER, $value);
    }

    public static function setViewerHost(string $value) : void
    {
        self::set(self::KEY_VIEWER_HOST, $value);
    }

    public static function setViewerPort(int $value) : void
    {
        self::set(self::KEY_VIEWER_PORT, $value);
    }

    public static function setAutoBackupEnabled(bool $value) : void
    {
        self::set(self::KEY_AUTO_BACKUP_ENABLED, $value);
    }

    public static function setKeepXMLFiles(bool $value) : void
    {
        self::set(self::KEY_KEEP_XML_FILES, $value);
    }

    public static function setLoggingEnabled(bool $value) : void
    {
        self::set(self::KEY_LOGGING_ENABLED, $value);
    }

    public static function setSavesFolder(string $value) : void
    {
        self::set(self::KEY_SAVES_FOLDER, $value);
    }

    public static function setTestSuiteEnabled(bool $value) : void
    {
        self::set(self::KEY_TEST_SUITE_ENABLED, $value);
    }
}

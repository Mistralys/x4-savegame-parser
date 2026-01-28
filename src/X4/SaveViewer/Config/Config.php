<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Config;

use AppUtils\FileHelper\JSONFile;
use AppUtils\FileHelper_Exception;
use RuntimeException;

class Config
{
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
            throw new RuntimeException(sprintf('Config file not found: %s', $path));
        }

        try
        {
            $json = $file->parse();
        }
        catch (FileHelper_Exception $e)
        {
            throw new RuntimeException(sprintf('Invalid JSON in config file: %s', $path), $e->getCode(), $e);
        }

        self::$instance = new self($json);
    }

    public static function ensureLoaded(?string $path = null) : void
    {
        if(self::$instance !== null) {
            return;
        }

        if($path === null) {
            $path = __DIR__ . '/../../../../config.json';
            if(!file_exists($path)) {
                // fall back to dist
                $path = __DIR__ . '/../../../../config.dist.json';
            }
        }

        self::loadFromFile($path);
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

    public static function getSavesFolder() : string
    {
        if(self::has('savesFolder')) {
            return self::getString('savesFolder');
        }

        $folder = self::getGameFolder();
        return $folder . DIRECTORY_SEPARATOR . 'save';
    }

    public static function getGameFolder() : string
    {
        return self::getString('gameFolder');
    }

    public static function getStorageFolder() : string
    {
        return self::getString('storageFolder');
    }

    public static function getViewerHost() : string
    {
        return self::getString('viewerHost', 'localhost');
    }

    public static function getViewerPort() : int
    {
        return self::getInt('viewerPort', 9494);
    }

    public static function isAutoBackupEnabled() : bool
    {
        return self::getBool('autoBackupEnabled', true);
    }

    public static function isKeepXMLFiles() : bool
    {
        return self::getBool('keepXMLFiles', false);
    }

    public static function isTestSuiteEnabled() : bool
    {
        return self::getBool('testSuiteEnabled', false);
    }

    public static function isLoggingEnabled() : bool
    {
        return self::getBool('loggingEnabled', false);
    }

    public static function set(string $key, $value) : void
    {
        self::ensureLoaded();
        self::$instance->data[$key] = $value;
    }

    public static function setGameFolder(string $value) : void
    {
        self::set('gameFolder', $value);
    }

    public static function setStorageFolder(string $value) : void
    {
        self::set('storageFolder', $value);
    }

    public static function setViewerHost(string $value) : void
    {
        self::set('viewerHost', $value);
    }

    public static function setViewerPort(int $value) : void
    {
        self::set('viewerPort', $value);
    }

    public static function setAutoBackupEnabled(bool $value) : void
    {
        self::set('autoBackupEnabled', $value);
    }

    public static function setKeepXMLFiles(bool $value) : void
    {
        self::set('keepXMLFiles', $value);
    }

    public static function setLoggingEnabled(bool $value) : void
    {
        self::set('loggingEnabled', $value);
    }

    public static function setSavesFolder(string $value) : void
    {
        self::set('savesFolder', $value);
    }

    public static function setTestSuiteEnabled(bool $value) : void
    {
        self::set('testSuiteEnabled', $value);
    }
}

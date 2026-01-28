<?php

// ...existing code...

namespace Mistralys\X4\SaveViewer\Config;

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
        if(!file_exists($path)) {
            throw new RuntimeException(sprintf('Config file not found: %s', $path));
        }

        $contents = file_get_contents($path);
        $json = json_decode($contents, true);
        if($json === null || !is_array($json)) {
            throw new RuntimeException(sprintf('Invalid JSON in config file: %s', $path));
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

        return array_key_exists($key, self::$instance->data) ? self::$instance->data[$key] : $default;
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
     * Bootstrap compatibility: define legacy constants from config values if they are not already defined.
     * Call this early in the application's bootstrap (e.g. in prepend.php) to keep existing code working.
     */
    public static function bootstrapCompatibility(?string $path = null) : void
    {
        self::ensureLoaded($path);

        $map = array(
            'X4_FOLDER' => null,
            'X4_STORAGE_FOLDER' => null,
            'X4_SERVER_HOST' => 'localhost',
            'X4_SERVER_PORT' => 9494,
            'X4_MONITOR_AUTO_BACKUP' => true,
            'X4_MONITOR_KEEP_XML' => false,
            'X4_MONITOR_LOGGING' => false
        );

        foreach($map as $key => $default) {
            if(defined($key)) {
                continue;
            }

            $value = self::get($key, $default);

            // If the value is a string, quote properly for define
            if(is_string($value)) {
                define($key, $value);
            } elseif(is_bool($value) || is_int($value) || is_float($value)) {
                define($key, $value);
            } else {
                // For arrays or objects, serialize to JSON string
                define($key, json_encode($value));
            }
        }

        if(!defined('X4_SAVES_FOLDER')) {
            // X4_SAVES_FOLDER was previously defined as X4_FOLDER.'\save'
            $folder = self::getString('X4_FOLDER', '');
            if($folder !== '') {
                define('X4_SAVES_FOLDER', $folder . DIRECTORY_SEPARATOR . 'save');
            }
        }
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
        if(self::has('X4_SAVES_FOLDER')) {
            return self::getString('X4_SAVES_FOLDER');
        }

        $folder = self::getString('X4_FOLDER');
        return $folder . DIRECTORY_SEPARATOR . 'save';
    }
}

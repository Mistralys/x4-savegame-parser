<?php
/**
 * @package X4SaveViewer
 * @subpackage CLI
 * @see \Mistralys\X4\SaveViewer\CLI\QueryCache
 */

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\CLI;

use AppUtils\FileHelper;
use AppUtils\FileHelper\FolderInfo;
use AppUtils\FileHelper_Exception;
use Mistralys\X4\SaveViewer\Data\BaseSaveFile;
use Mistralys\X4\SaveViewer\Data\SaveManager;

/**
 * Manages result caching for CLI queries to enable efficient pagination.
 *
 * Caches are stored per-save in hidden `.cache` directories and are
 * automatically invalidated when the save file is modified.
 *
 * @package X4SaveViewer
 * @subpackage CLI
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */
class QueryCache
{
    private SaveManager $manager;

    public function __construct(SaveManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Store filtered query results in the cache.
     *
     * @param BaseSaveFile $save The save file to cache results for
     * @param string $cacheKey Unique identifier for this cache entry
     * @param array $data The data to cache
     * @return void
     * @throws FileHelper_Exception
     */
    public function store(BaseSaveFile $save, string $cacheKey, array $data): void
    {
        $cachePath = $this->getCachePath($save, $cacheKey);
        $cacheDir = $this->getCacheDir($save);

        // Ensure cache directory exists
        if (!$cacheDir->exists()) {
            mkdir($cacheDir->getPath(), 0755, true);
        }

        // Store the data as JSON
        FileHelper::saveAsJSON($data, $cachePath, true);
    }

    /**
     * Retrieve cached query results if they exist and are still valid.
     *
     * @param BaseSaveFile $save The save file to retrieve cache for
     * @param string $cacheKey The cache entry identifier
     * @return array|null The cached data, or null if not found or invalid
     */
    public function retrieve(BaseSaveFile $save, string $cacheKey): ?array
    {
        if (!$this->isValid($save, $cacheKey)) {
            return null;
        }

        $cachePath = $this->getCachePath($save, $cacheKey);

        try {
            return FileHelper::parseJSONFile($cachePath);
        } catch (FileHelper_Exception $e) {
            // Cache file corrupted or unreadable
            return null;
        }
    }

    /**
     * Check if a cache entry exists and is still valid.
     *
     * A cache is valid if:
     * - The cache file exists
     * - The save file has not been modified since the cache was created
     *
     * @param BaseSaveFile $save The save file to check
     * @param string $cacheKey The cache entry identifier
     * @return bool True if cache is valid, false otherwise
     */
    public function isValid(BaseSaveFile $save, string $cacheKey): bool
    {
        $cachePath = $this->getCachePath($save, $cacheKey);

        // Check if cache file exists
        if (!file_exists($cachePath)) {
            return false;
        }

        // Get modification times
        $cacheTime = filemtime($cachePath);
        $saveTime = $this->getSaveModifiedTime($save);

        // Cache is invalid if save was modified after cache was created
        return $saveTime <= $cacheTime;
    }

    /**
     * Clear all caches across all saves.
     *
     * Removes all `.cache` directories found in the storage folder.
     *
     * @return int Number of cache directories removed
     * @throws FileHelper_Exception
     */
    public function clearAll(): int
    {
        $storageFolder = $this->manager->getStorageFolder();
        $count = 0;

        // Find all subdirectories in storage folder
        $items = FileHelper::createFileFinder($storageFolder->getPath())
            ->setPathmodeAbsolute()
            ->getAll();

        foreach ($items as $item) {
            // Only process directories
            if (!is_dir($item)) {
                continue;
            }

            $cacheDir = $item . '/.cache';

            if (file_exists($cacheDir) && is_dir($cacheDir)) {
                FileHelper::deleteTree($cacheDir);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Remove cache directories for saves that no longer exist.
     *
     * Scans the storage folder for extracted save directories and removes
     * `.cache` directories for saves that have been deleted.
     *
     * @return int Number of cache directories removed
     */
    public function cleanupObsoleteCaches(): int
    {
        $storageFolder = $this->manager->getStorageFolder();

        if (!$storageFolder->exists()) {
            return 0;
        }

        // Get all current save IDs from both main and archived saves
        $currentSaveIDs = [];

        foreach ($this->manager->getSaves() as $save) {
            $currentSaveIDs[] = $save->getSaveID();
        }

        foreach ($this->manager->getArchivedSaves() as $save) {
            $currentSaveIDs[] = $save->getSaveID();
        }

        // Scan storage folder for save directories
        $removed = 0;
        $saveDirs = glob($storageFolder->getPath() . '/unpack-*', GLOB_ONLYDIR);

        if ($saveDirs === false) {
            return 0;
        }

        foreach ($saveDirs as $saveDir) {
            $saveDirName = basename($saveDir);

            // Check if this save still exists
            $saveExists = false;
            foreach ($currentSaveIDs as $saveID) {
                if (strpos($saveDirName, $saveID) !== false) {
                    $saveExists = true;
                    break;
                }
            }

            // Remove cache directory if save no longer exists
            if (!$saveExists) {
                $cacheDir = $saveDir . '/.cache';
                if (is_dir($cacheDir)) {
                    $this->removeCacheDirectory($cacheDir);
                    $removed++;
                }
            }
        }

        return $removed;
    }

    /**
     * Recursively remove a directory and its contents.
     *
     * @param string $dir Directory path to remove
     * @return void
     */
    private function removeCacheDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;

            if (is_dir($path)) {
                $this->removeCacheDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    /**
     * Get the full path to a cache file.
     *
     * @param BaseSaveFile $save The save file
     * @param string $cacheKey The cache entry identifier
     * @return string Full path to the cache file
     */
    private function getCachePath(BaseSaveFile $save, string $cacheKey): string
    {
        return sprintf(
            '%s/query-%s.json',
            $this->getCacheDir($save)->getPath(),
            $cacheKey
        );
    }

    /**
     * Get the cache directory for a specific save.
     *
     * Returns a FolderInfo instance for the `.cache` directory within
     * the save's storage folder.
     *
     * @param BaseSaveFile $save The save file
     * @return FolderInfo The cache directory (may not exist yet)
     */
    private function getCacheDir(BaseSaveFile $save): FolderInfo
    {
        $storageFolder = $save->getStorageFolder();
        return FolderInfo::factory($storageFolder->getPath() . '/.cache');
    }

    /**
     * Get the modification timestamp of the save file.
     *
     * @param BaseSaveFile $save The save file
     * @return int Unix timestamp of last modification
     */
    private function getSaveModifiedTime(BaseSaveFile $save): int
    {
        return $save->getDateModified()->getTimestamp();
    }
}

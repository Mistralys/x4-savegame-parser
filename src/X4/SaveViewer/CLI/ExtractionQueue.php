<?php
/**
 * @package X4SaveViewer
 * @subpackage CLI
 * @see \Mistralys\X4\SaveViewer\CLI\ExtractionQueue
 */

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\CLI;

use AppUtils\FileHelper;
use Mistralys\X4\SaveViewer\Data\SaveManager;

/**
 * Manages a queue of savegames to be extracted by the monitor.
 *
 * The queue is stored as a JSON file that the monitor checks periodically.
 * When saves are queued, the monitor will process them automatically.
 *
 * @package X4SaveViewer
 * @subpackage CLI
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */
class ExtractionQueue
{
    private const QUEUE_FILE = 'extraction-queue.json';

    private SaveManager $manager;
    private string $queuePath;

    public function __construct(SaveManager $manager)
    {
        $this->manager = $manager;
        $this->queuePath = $manager->getStorageFolder()->getPath() . '/' . self::QUEUE_FILE;
    }

    /**
     * Add a save to the extraction queue.
     *
     * @param string $saveIdentifier Save name or ID
     * @return void
     */
    public function add(string $saveIdentifier): void
    {
        $queue = $this->load();

        // Avoid duplicates
        if (!in_array($saveIdentifier, $queue, true)) {
            $queue[] = $saveIdentifier;
            $this->save($queue);
        }
    }

    /**
     * Add multiple saves to the extraction queue.
     *
     * @param string[] $saveIdentifiers Array of save names or IDs
     * @return void
     */
    public function addMultiple(array $saveIdentifiers): void
    {
        $queue = $this->load();

        foreach ($saveIdentifiers as $identifier) {
            if (!in_array($identifier, $queue, true)) {
                $queue[] = $identifier;
            }
        }

        $this->save($queue);
    }

    /**
     * Remove a save from the extraction queue.
     *
     * @param string $saveIdentifier Save name or ID
     * @return void
     */
    public function remove(string $saveIdentifier): void
    {
        $queue = $this->load();
        $queue = array_values(array_filter($queue, fn($id) => $id !== $saveIdentifier));
        $this->save($queue);
    }

    /**
     * Get the next save from the queue without removing it.
     *
     * @return string|null The next save identifier, or null if queue is empty
     */
    public function peek(): ?string
    {
        $queue = $this->load();
        return !empty($queue) ? $queue[0] : null;
    }

    /**
     * Get and remove the next save from the queue.
     *
     * @return string|null The next save identifier, or null if queue is empty
     */
    public function pop(): ?string
    {
        $queue = $this->load();

        if (empty($queue)) {
            return null;
        }

        $next = array_shift($queue);
        $this->save($queue);

        return $next;
    }

    /**
     * Get all saves in the queue.
     *
     * @return string[] Array of save identifiers
     */
    public function getAll(): array
    {
        return $this->load();
    }

    /**
     * Clear the entire queue.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->save([]);
    }

    /**
     * Check if the queue has any items.
     *
     * @return bool True if queue has items
     */
    public function hasItems(): bool
    {
        return !empty($this->load());
    }

    /**
     * Get the number of items in the queue.
     *
     * @return int Number of queued saves
     */
    public function count(): int
    {
        return count($this->load());
    }

    /**
     * Load the queue from the JSON file.
     *
     * @return string[] Array of save identifiers
     */
    private function load(): array
    {
        if (!file_exists($this->queuePath)) {
            return [];
        }

        try {
            $data = FileHelper::parseJSONFile($this->queuePath);
            return is_array($data) ? $data : [];
        } catch (\Throwable $e) {
            // If queue file is corrupted, start fresh
            return [];
        }
    }

    /**
     * Save the queue to the JSON file.
     *
     * @param string[] $queue Array of save identifiers
     * @return void
     */
    private function save(array $queue): void
    {
        FileHelper::saveAsJSON($queue, $this->queuePath, true);
    }
}

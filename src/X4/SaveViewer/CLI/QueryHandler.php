<?php
/**
 * @package X4SaveViewer
 * @subpackage CLI
 * @see \Mistralys\X4\SaveViewer\CLI\QueryHandler
 */

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\CLI;

use JmesPath\AstRuntime;
use JmesPath\Env as JmesPath;
use League\CLImate\CLImate;
use Mistralys\X4\SaveViewer\CLI\JMESPath\CustomFnDispatcher;
use Mistralys\X4\SaveViewer\Data\BaseSaveFile;
use Mistralys\X4\SaveViewer\Data\SaveManager;

/**
 * Main CLI query handler for the X4 Savegame Parser.
 *
 * Handles command-line arguments, executes queries, applies filtering,
 * pagination, and caching.
 *
 * @package X4SaveViewer
 * @subpackage CLI
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */
class QueryHandler
{
    // Command constants
    public const string COMMAND_SAVE_INFO = 'save-info';
    public const string COMMAND_PLAYER = 'player';
    public const string COMMAND_STATS = 'stats';
    public const string COMMAND_FACTIONS = 'factions';
    public const string COMMAND_BLUEPRINTS = 'blueprints';
    public const string COMMAND_INVENTORY = 'inventory';
    public const string COMMAND_LOG = 'log';
    public const string COMMAND_KHAAK_STATIONS = 'khaak-stations';
    public const string COMMAND_SHIP_LOSSES = 'ship-losses';
    public const string COMMAND_SHIPS = 'ships';
    public const string COMMAND_STATIONS = 'stations';
    public const string COMMAND_PEOPLE = 'people';
    public const string COMMAND_SECTORS = 'sectors';
    public const string COMMAND_ZONES = 'zones';
    public const string COMMAND_REGIONS = 'regions';
    public const string COMMAND_CLUSTERS = 'clusters';
    public const string COMMAND_CELESTIALS = 'celestials';
    public const string COMMAND_EVENT_LOG = 'event-log';
    public const string COMMAND_CLEAR_CACHE = 'clear-cache';
    public const string COMMAND_LIST_SAVES = 'list-saves';
    public const string COMMAND_QUEUE_EXTRACTION = 'queue-extraction';
    public const string COMMAND_LIST_PATHS = 'list-paths';

    private SaveManager $manager;
    private QueryCache $cache;
    private QueryValidator $validator;
    private CLImate $cli;

    public function __construct(SaveManager $manager)
    {
        $this->manager = $manager;
        $this->cache = new QueryCache($manager);
        $this->validator = new QueryValidator($manager);
        $this->cli = new CLImate();

        $this->registerArguments();
    }

    public static function createFromConfig(): self
    {
        return new self(SaveManager::createFromConfig());
    }

    /**
     * Handle the CLI request by parsing arguments and executing the command.
     */
    public function handle(): void
    {
        $this->cli->arguments->parse();

        // Get the command (first non-flag argument)
        $command = $this->getCommand();

        if ($command === null) {
            $this->cli->usage();
            return;
        }

        try {
            $this->executeCommand($command);
        } catch (QueryValidationException $e) {
            echo JsonResponseBuilder::error($e, $command, $this->isPretty());
            exit(1);
        }
    }

    /**
     * Register CLI arguments with league/climate.
     */
    private function registerArguments(): void
    {
        $this->cli->arguments->add([
            'save' => [
                'prefix' => 's',
                'longPrefix' => 'save',
                'description' => 'Save name or ID (required for most commands)',
                'defaultValue' => ''
            ],
            'filter' => [
                'prefix' => 'f',
                'longPrefix' => 'filter',
                'description' => 'JMESPath filter expression',
                'defaultValue' => ''
            ],
            'limit' => [
                'prefix' => 'l',
                'longPrefix' => 'limit',
                'description' => 'Maximum number of results to return',
                'castTo' => 'int',
                'defaultValue' => 0
            ],
            'offset' => [
                'prefix' => 'o',
                'longPrefix' => 'offset',
                'description' => 'Number of results to skip',
                'castTo' => 'int',
                'defaultValue' => 0
            ],
            'cache-key' => [
                'longPrefix' => 'cache-key',
                'description' => 'Cache key for result reuse',
                'defaultValue' => ''
            ],
            'pretty' => [
                'prefix' => 'p',
                'longPrefix' => 'pretty',
                'description' => 'Pretty-print JSON output',
                'noValue' => true
            ],
            'saves' => [
                'longPrefix' => 'saves',
                'description' => 'Space-separated list of save names/IDs (for queue-extraction)',
                'defaultValue' => ''
            ],
            'list' => [
                'longPrefix' => 'list',
                'description' => 'List queue contents (for queue-extraction)',
                'noValue' => true
            ],
            'clear' => [
                'longPrefix' => 'clear',
                'description' => 'Clear the queue (for queue-extraction)',
                'noValue' => true
            ]
        ]);
    }

    /**
     * Get the command from the CLI arguments.
     */
    private function getCommand(): ?string
    {
        $args = $_SERVER['argv'] ?? [];

        // Skip the script name
        array_shift($args);

        // Find the first non-flag argument
        foreach ($args as $arg) {
            if (!str_starts_with($arg, '-')) {
                return $arg;
            }
        }

        return null;
    }

    /**
     * Execute a specific command.
     */
    private function executeCommand(string $command): void
    {
        // Special commands that don't require a save
        if ($command === self::COMMAND_CLEAR_CACHE) {
            $this->execute_clearCache();
            return;
        }

        if ($command === self::COMMAND_LIST_SAVES) {
            $this->execute_listSaves();
            return;
        }

        if ($command === self::COMMAND_QUEUE_EXTRACTION) {
            $this->execute_queueExtraction();
            return;
        }

        if ($command === self::COMMAND_LIST_PATHS) {
            $this->execute_listPaths();
            return;
        }

        // All other commands require a save
        $saveIdentifier = $this->cli->arguments->get('save');

        if (empty($saveIdentifier)) {
            throw new QueryValidationException(
                'The --save parameter is required',
                0,
                ['Example: bin/query ' . $command . ' --save=quicksave']
            );
        }

        // Validate the save
        $save = $this->validator->validateSave($saveIdentifier);

        // Validate other parameters
        $limit = $this->cli->arguments->get('limit');
        $offset = $this->cli->arguments->get('offset');
        $cacheKey = $this->cli->arguments->get('cache-key');

        $this->validator->validatePagination($limit > 0 ? $limit : null, $offset > 0 ? $offset : null);
        $this->validator->validateCacheKey($cacheKey);

        // Execute the appropriate command
        match ($command) {
            self::COMMAND_SAVE_INFO => $this->execute_saveInfo($save),
            self::COMMAND_PLAYER => $this->execute_player($save),
            self::COMMAND_STATS => $this->execute_stats($save),
            self::COMMAND_FACTIONS => $this->execute_factions($save),
            self::COMMAND_BLUEPRINTS => $this->execute_blueprints($save),
            self::COMMAND_INVENTORY => $this->execute_inventory($save),
            self::COMMAND_LOG => $this->execute_log($save),
            self::COMMAND_KHAAK_STATIONS => $this->execute_khaakStations($save),
            self::COMMAND_SHIP_LOSSES => $this->execute_shipLosses($save),
            self::COMMAND_SHIPS => $this->execute_ships($save),
            self::COMMAND_STATIONS => $this->execute_stations($save),
            self::COMMAND_PEOPLE => $this->execute_people($save),
            self::COMMAND_SECTORS => $this->execute_sectors($save),
            self::COMMAND_ZONES => $this->execute_zones($save),
            self::COMMAND_REGIONS => $this->execute_regions($save),
            self::COMMAND_CLUSTERS => $this->execute_clusters($save),
            self::COMMAND_CELESTIALS => $this->execute_celestials($save),
            self::COMMAND_EVENT_LOG => $this->execute_eventLog($save),
            default => throw new QueryValidationException(
                sprintf('Unknown command: %s', $command),
                0,
                ['Run: bin/query --help']
            )
        };
    }

    // region: Command implementations

    private function execute_saveInfo(BaseSaveFile $save): void
    {
        $reader = $save->getDataReader();
        $data = $reader->getSaveInfo()->toArrayForAPI();

        $this->outputSuccess(self::COMMAND_SAVE_INFO, $data);
    }

    private function execute_player(BaseSaveFile $save): void
    {
        $reader = $save->getDataReader();
        $data = $reader->getPlayer()->toArrayForAPI();

        $this->outputSuccess(self::COMMAND_PLAYER, $data);
    }

    private function execute_stats(BaseSaveFile $save): void
    {
        $reader = $save->getDataReader();
        $data = $reader->getStatistics()->toArrayForAPI();

        $this->outputSuccess(self::COMMAND_STATS, $data);
    }

    private function execute_factions(BaseSaveFile $save): void
    {
        $reader = $save->getDataReader();
        $data = $reader->getFactions()->toArrayForAPI();

        $data = $this->applyFilteringAndPagination($save, $data);
        $this->outputSuccess(self::COMMAND_FACTIONS, $data['data'], $data['pagination']);
    }

    private function execute_blueprints(BaseSaveFile $save): void
    {
        $reader = $save->getDataReader();
        $data = $reader->getBlueprints()->toArrayForAPI();

        $data = $this->applyFilteringAndPagination($save, $data);
        $this->outputSuccess(self::COMMAND_BLUEPRINTS, $data['data'], $data['pagination']);
    }

    private function execute_inventory(BaseSaveFile $save): void
    {
        $reader = $save->getDataReader();
        $data = $reader->getInventory()->toArrayForAPI();

        $data = $this->applyFilteringAndPagination($save, $data);
        $this->outputSuccess(self::COMMAND_INVENTORY, $data['data'], $data['pagination']);
    }

    private function execute_log(BaseSaveFile $save): void
    {
        $reader = $save->getDataReader();
        $data = $reader->getLog()->toArrayForAPI();

        // Auto-cache for unfiltered queries (WP3: Logbook Performance Optimization)
        $filter = $this->cli->arguments->get('filter');
        $cacheKey = $this->cli->arguments->get('cache-key');

        // Use auto-cache key if no filter and no manual cache key
        $effectiveCacheKey = $cacheKey;
        if (empty($filter) && empty($cacheKey)) {
            $effectiveCacheKey = '_log_unfiltered_' . $save->getSaveID();
        }

        $data = $this->applyFilteringAndPagination($save, $data, $effectiveCacheKey);
        $this->outputSuccess(self::COMMAND_LOG, $data['data'], $data['pagination']);
    }

    private function execute_khaakStations(BaseSaveFile $save): void
    {
        $reader = $save->getDataReader();
        $data = $reader->getKhaakStations()->toArrayForAPI();

        $data = $this->applyFilteringAndPagination($save, $data);
        $this->outputSuccess(self::COMMAND_KHAAK_STATIONS, $data['data'], $data['pagination']);
    }

    private function execute_shipLosses(BaseSaveFile $save): void
    {
        $reader = $save->getDataReader();
        $data = $reader->getShipLosses()->toArrayForAPI();

        $data = $this->applyFilteringAndPagination($save, $data);
        $this->outputSuccess(self::COMMAND_SHIP_LOSSES, $data['data'], $data['pagination']);
    }

    private function execute_ships(BaseSaveFile $save): void
    {
        $data = $this->flattenCollectionArray($save->getDataReader()->getCollections()->ships()->loadData());

        $data = $this->applyFilteringAndPagination($save, $data);
        $this->outputSuccess(self::COMMAND_SHIPS, $data['data'], $data['pagination']);
    }

    private function execute_stations(BaseSaveFile $save): void
    {
        $data = $this->flattenCollectionArray($save->getDataReader()->getCollections()->stations()->loadData());

        $data = $this->applyFilteringAndPagination($save, $data);
        $this->outputSuccess(self::COMMAND_STATIONS, $data['data'], $data['pagination']);
    }

    private function execute_people(BaseSaveFile $save): void
    {
        $data = $this->flattenCollectionArray($save->getDataReader()->getCollections()->people()->loadData());

        $data = $this->applyFilteringAndPagination($save, $data);
        $this->outputSuccess(self::COMMAND_PEOPLE, $data['data'], $data['pagination']);
    }

    private function execute_sectors(BaseSaveFile $save): void
    {
        $data = $this->flattenCollectionArray($save->getDataReader()->getCollections()->sectors()->loadData());

        $data = $this->applyFilteringAndPagination($save, $data);
        $this->outputSuccess(self::COMMAND_SECTORS, $data['data'], $data['pagination']);
    }

    private function execute_zones(BaseSaveFile $save): void
    {
        $data = $this->flattenCollectionArray($save->getDataReader()->getCollections()->zones()->loadData());

        $data = $this->applyFilteringAndPagination($save, $data);
        $this->outputSuccess(self::COMMAND_ZONES, $data['data'], $data['pagination']);
    }

    private function execute_regions(BaseSaveFile $save): void
    {
        $data = $this->flattenCollectionArray($save->getDataReader()->getCollections()->regions()->loadData());

        $data = $this->applyFilteringAndPagination($save, $data);
        $this->outputSuccess(self::COMMAND_REGIONS, $data['data'], $data['pagination']);
    }

    private function execute_clusters(BaseSaveFile $save): void
    {
        $data = $this->flattenCollectionArray($save->getDataReader()->getCollections()->clusters()->loadData());

        $data = $this->applyFilteringAndPagination($save, $data);
        $this->outputSuccess(self::COMMAND_CLUSTERS, $data['data'], $data['pagination']);
    }

    private function execute_celestials(BaseSaveFile $save): void
    {
        $data = $this->flattenCollectionArray($save->getDataReader()->getCollections()->celestials()->loadData());

        $data = $this->applyFilteringAndPagination($save, $data);
        $this->outputSuccess(self::COMMAND_CELESTIALS, $data['data'], $data['pagination']);
    }

    private function execute_eventLog(BaseSaveFile $save): void
    {
        $data = $this->flattenCollectionArray($save->getDataReader()->getCollections()->eventLog()->loadData());

        $data = $this->applyFilteringAndPagination($save, $data);
        $this->outputSuccess(self::COMMAND_EVENT_LOG, $data['data'], $data['pagination']);
    }

    private function execute_clearCache(): void
    {
        $count = $this->cache->clearAll();

        $this->outputSuccess(self::COMMAND_CLEAR_CACHE, [
            'cleared' => $count,
            'message' => sprintf('Cleared %d cache director%s', $count, $count === 1 ? 'y' : 'ies')
        ]);
    }

    private function execute_listSaves(): void
    {
        $saves = $this->manager->getSaves();
        $archivedSaves = $this->manager->getArchivedSaves();

        $result = [
            'main' => [],
            'archived' => []
        ];

        // Main saves (from game folder)
        foreach ($saves as $save) {
            $result['main'][] = [
                'id' => $save->getSaveID(),
                'name' => $save->getSaveName(),
                'dateModified' => $save->getDateModified()->format('c'),
                'isUnpacked' => $save->isUnpacked(),
                'hasBackup' => $save->hasBackup()
            ];
        }

        // Archived saves (extracted saves in storage)
        foreach ($archivedSaves as $save) {
            $result['archived'][] = [
                'id' => $save->getSaveID(),
                'name' => $save->getSaveName(),
                'dateModified' => $save->getDateModified()->format('c'),
                'isUnpacked' => $save->isUnpacked(),
                'hasBackup' => $save->hasBackup(),
                'storageFolder' => $save->getStorageFolder()->getName()
            ];
        }

        $this->outputSuccess(self::COMMAND_LIST_SAVES, $result);
    }

    private function execute_queueExtraction(): void
    {
        $queue = new ExtractionQueue($this->manager);

        // Check for --list flag
        if ($this->cli->arguments->defined('list')) {
            $items = $queue->getAll();
            $this->outputSuccess(self::COMMAND_QUEUE_EXTRACTION, [
                'queue' => $items,
                'count' => count($items)
            ]);
            return;
        }

        // Check for --clear flag
        if ($this->cli->arguments->defined('clear')) {
            $count = $queue->count();
            $queue->clear();
            $this->outputSuccess(self::COMMAND_QUEUE_EXTRACTION, [
                'cleared' => $count,
                'message' => sprintf('Cleared %d item%s from queue', $count, $count === 1 ? '' : 's')
            ]);
            return;
        }

        // Get saves to queue
        $savesList = $this->cli->arguments->get('saves');
        $singleSave = $this->cli->arguments->get('save');

        $toQueue = [];

        // Handle --saves flag (space-separated list)
        if (!empty($savesList)) {
            $toQueue = array_merge($toQueue, preg_split('/\s+/', trim($savesList)));
        }

        // Handle --save flag (single save)
        if (!empty($singleSave)) {
            $toQueue[] = $singleSave;
        }

        if (empty($toQueue)) {
            throw new QueryValidationException(
                'No saves specified. Use --save or --saves to queue saves, --list to view queue, or --clear to empty queue.',
                0,
                [
                    'Example: bin/query queue-extraction --save=autosave_01',
                    'Example: bin/query queue-extraction --saves="autosave_01 autosave_02"',
                    'Example: bin/query queue-extraction --list',
                    'Example: bin/query queue-extraction --clear'
                ]
            );
        }

        // Validate all saves exist
        $validated = [];
        $errors = [];
        foreach ($toQueue as $identifier) {
            try {
                $this->validator->validateSaveExists($identifier);
                $validated[] = $identifier;
            } catch (QueryValidationException $e) {
                // Track which saves were not found
                $errors[] = $identifier;
            }
        }

        if (empty($validated)) {
            $message = 'None of the specified saves were found';
            if (!empty($errors)) {
                $message .= ': ' . implode(', ', $errors);
            }
            throw new QueryValidationException(
                $message,
                0,
                ['Run: bin/query list-saves  (to see available saves)']
            );
        }

        // Add to queue
        $queue->addMultiple($validated);

        $result = [
            'queued' => $validated,
            'count' => count($validated),
            'message' => sprintf('Queued %d save%s for extraction', count($validated), count($validated) === 1 ? '' : 's'),
            'totalInQueue' => $queue->count()
        ];

        // Include warnings about skipped saves
        if (!empty($errors)) {
            $result['skipped'] = $errors;
            $result['warning'] = sprintf('%d save%s not found and skipped', count($errors), count($errors) === 1 ? '' : 's');
        }

        $this->outputSuccess(self::COMMAND_QUEUE_EXTRACTION, $result);
    }

    private function execute_listPaths(): void
    {
        $savesFolder = $this->manager->getSavesFolder();
        $storageFolder = $this->manager->getStorageFolder();

        $result = [
            'savesFolder' => [
                'path' => $savesFolder->getPath(),
                'exists' => $savesFolder->exists(),
                'description' => 'X4 savegame folder (where .gz files are stored)'
            ],
            'storageFolder' => [
                'path' => $storageFolder->getPath(),
                'exists' => $storageFolder->exists(),
                'description' => 'Extraction storage folder (where unpacked saves are stored)'
            ],
            'extractionPattern' => 'unpack-{datetime}-{savename}',
            'message' => 'Current path configuration'
        ];

        $this->outputSuccess(self::COMMAND_LIST_PATHS, $result);
    }

    // endregion

    // region: Helper methods

    /**
     * Flatten a collection's nested array structure into a single flat array.
     *
     * Collections return arrays in the format: {"typeID": [items]}.
     * This method merges all items from all type IDs into a single flat array.
     *
     * @param array $collectionData The nested collection data
     * @return array Flattened array of all items
     */
    private function flattenCollectionArray(array $collectionData): array
    {
        $result = [];

        foreach ($collectionData as $typeItems) {
            if (is_array($typeItems)) {
                $result = array_merge($result, array_values($typeItems));
            }
        }

        return $result;
    }

    /**
     * Apply filtering and pagination to data.
     *
     * @param BaseSaveFile $save The save file (for caching)
     * @param array $data The data to process
     * @param string|null $overrideCacheKey Override cache key (for auto-cache, WP3)
     * @return array{data: array, pagination: array|null} Processed data and pagination metadata
     */
    private function applyFilteringAndPagination(BaseSaveFile $save, array $data, ?string $overrideCacheKey = null): array
    {
        $filter = $this->cli->arguments->get('filter');
        $limit = $this->cli->arguments->get('limit');
        $offset = $this->cli->arguments->get('offset');

        // Use override cache key if provided, otherwise use CLI argument
        $cacheKey = $overrideCacheKey ?? $this->cli->arguments->get('cache-key');

        // Try to use cache if cache key provided
        if (!empty($cacheKey) && $this->cache->isValid($save, $cacheKey)) {
            $data = $this->cache->retrieve($save, $cacheKey) ?? $data;
        } else {
            // Apply filter if provided
            if (!empty($filter)) {
                $data = $this->applyFilter($data, $filter);
            }

            // Store in cache if cache key provided (WP3: Cache both filtered and unfiltered data)
            if (!empty($cacheKey)) {
                $this->cache->store($save, $cacheKey, $data);
            }
        }

        // Apply pagination
        $pagination = null;
        if ($limit > 0) {
            $pagination = $this->buildPaginationMetadata(count($data), $limit, $offset);
            $data = array_slice($data, $offset, $limit);
        }

        return [
            'data' => $data,
            'pagination' => $pagination
        ];
    }

    /**
     * Apply a JMESPath filter to data using custom function dispatcher.
     *
     * Uses a custom AstRuntime with extended functions for case-insensitive
     * searching (to_lower, to_upper, contains_i, starts_with_i, ends_with_i).
     *
     * Note: JmesPath\SyntaxErrorException is allowed to bubble up
     * and will be caught by the global exception handler.
     *
     * @param array $data The data to filter
     * @param string $filter The JMESPath expression
     * @return array Filtered data
     */
    private function applyFilter(array $data, string $filter): array
    {
        $runtime = new AstRuntime(null, new CustomFnDispatcher());
        $result = $runtime($filter, $data);

        // Ensure we always return an array
        if (!is_array($result)) {
            return [$result];
        }

        return $result;
    }

    /**
     * Build pagination metadata.
     */
    private function buildPaginationMetadata(int $total, int $limit, int $offset): array
    {
        return [
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'hasMore' => ($offset + $limit) < $total
        ];
    }

    /**
     * Check if pretty printing is enabled.
     */
    private function isPretty(): bool
    {
        return $this->cli->arguments->defined('pretty');
    }

    /**
     * Output a success response.
     */
    private function outputSuccess(string $command, mixed $data, ?array $pagination = null): void
    {
        echo JsonResponseBuilder::success($command, $data, $pagination, $this->isPretty());
    }

    // endregion
}

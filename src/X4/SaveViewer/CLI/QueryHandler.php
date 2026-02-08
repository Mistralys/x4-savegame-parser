<?php
/**
 * @package X4SaveViewer
 * @subpackage CLI
 * @see \Mistralys\X4\SaveViewer\CLI\QueryHandler
 */

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\CLI;

use JmesPath\AstRuntime;
use League\CLImate\CLImate;
use Mistralys\X4\SaveViewer\CLI\JMESPath\CustomFnDispatcher;
use Mistralys\X4\SaveViewer\Config\Config;
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
            $params = QueryParameters::fromCLImate($this->cli);
            $output = $this->executeCommand($command, $params);
            echo $output;
        } catch (QueryValidationException $e) {
            $params = QueryParameters::fromCLImate($this->cli);
            echo JsonResponseBuilder::error($e, $command, $params->isPretty);

            // Don't call exit() during test suite execution to avoid killing PHPUnit
            if (!Config::isTestSuiteEnabled()) {
                exit(1);
            }
        }
    }

    /**
     * Execute a command with the given parameters and return JSON output.
     *
     * This method contains all business logic (validation, execution, filtering,
     * pagination, caching) and returns JSON string instead of echoing it.
     * Designed to be testable without CLI argument parsing.
     *
     * @param string $command The command to execute
     * @param QueryParameters $params The query parameters
     * @return string JSON output
     * @throws QueryValidationException If validation fails
     */
    public function executeCommand(string $command, QueryParameters $params): string
    {
        // Special commands that don't require a save
        if ($command === self::COMMAND_CLEAR_CACHE) {
            return $this->execute_clearCache($params);
        }

        if ($command === self::COMMAND_LIST_SAVES) {
            return $this->execute_listSaves($params);
        }

        if ($command === self::COMMAND_QUEUE_EXTRACTION) {
            return $this->execute_queueExtraction($params);
        }

        if ($command === self::COMMAND_LIST_PATHS) {
            return $this->execute_listPaths($params);
        }

        // All other commands require a save
        $saveIdentifier = $params->saveIdentifier;

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
        $this->validator->validatePagination($params->limit > 0 ? $params->limit : null, $params->offset > 0 ? $params->offset : null);
        $this->validator->validateCacheKey($params->cacheKey);

        // Execute the appropriate command
        return match ($command) {
            self::COMMAND_SAVE_INFO => $this->execute_saveInfo($save, $params),
            self::COMMAND_PLAYER => $this->execute_player($save, $params),
            self::COMMAND_STATS => $this->execute_stats($save, $params),
            self::COMMAND_FACTIONS => $this->execute_factions($save, $params),
            self::COMMAND_BLUEPRINTS => $this->execute_blueprints($save, $params),
            self::COMMAND_INVENTORY => $this->execute_inventory($save, $params),
            self::COMMAND_LOG => $this->execute_log($save, $params),
            self::COMMAND_KHAAK_STATIONS => $this->execute_khaakStations($save, $params),
            self::COMMAND_SHIP_LOSSES => $this->execute_shipLosses($save, $params),
            self::COMMAND_SHIPS => $this->execute_ships($save, $params),
            self::COMMAND_STATIONS => $this->execute_stations($save, $params),
            self::COMMAND_PEOPLE => $this->execute_people($save, $params),
            self::COMMAND_SECTORS => $this->execute_sectors($save, $params),
            self::COMMAND_ZONES => $this->execute_zones($save, $params),
            self::COMMAND_REGIONS => $this->execute_regions($save, $params),
            self::COMMAND_CLUSTERS => $this->execute_clusters($save, $params),
            self::COMMAND_CELESTIALS => $this->execute_celestials($save, $params),
            self::COMMAND_EVENT_LOG => $this->execute_eventLog($save, $params),
            default => throw new QueryValidationException(
                sprintf('Unknown command: %s', $command),
                0,
                ['Run: bin/query --help']
            )
        };
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
            'json' => [
                'prefix' => 'j',
                'longPrefix' => 'json',
                'description' => 'Enable JSON event streaming mode (NDJSON progress + result wrapper)',
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



    // region: Command implementations

    private function execute_saveInfo(BaseSaveFile $save, QueryParameters $params): string
    {
        $reader = $save->getDataReader();
        $data = $reader->getSaveInfo()->toArrayForAPI();

        return $this->outputSuccess(self::COMMAND_SAVE_INFO, $data, null, $params);
    }

    private function execute_player(BaseSaveFile $save, QueryParameters $params): string
    {
        $reader = $save->getDataReader();
        $data = $reader->getPlayer()->toArrayForAPI();

        return $this->outputSuccess(self::COMMAND_PLAYER, $data, null, $params);
    }

    private function execute_stats(BaseSaveFile $save, QueryParameters $params): string
    {
        $reader = $save->getDataReader();
        $data = $reader->getStatistics()->toArrayForAPI();

        return $this->outputSuccess(self::COMMAND_STATS, $data, null, $params);
    }

    private function execute_factions(BaseSaveFile $save, QueryParameters $params): string
    {
        $reader = $save->getDataReader();

        $data = $this->applyFilteringAndPaginationLazy($save, $params, null, function() use ($reader) : array {
            return $reader->getFactions()->toArrayForAPI();
        });
        return $this->outputSuccess(self::COMMAND_FACTIONS, $data['data'], $data['pagination'], $params);
    }

    private function execute_blueprints(BaseSaveFile $save, QueryParameters $params): string
    {
        $reader = $save->getDataReader();

        $data = $this->applyFilteringAndPaginationLazy($save, $params, null, function() use ($reader) : array {
            return $reader->getBlueprints()->toArrayForAPI();
        });
        return $this->outputSuccess(self::COMMAND_BLUEPRINTS, $data['data'], $data['pagination'], $params);
    }

    private function execute_inventory(BaseSaveFile $save, QueryParameters $params): string
    {
        $reader = $save->getDataReader();

        $data = $this->applyFilteringAndPaginationLazy($save, $params, null, function() use ($reader) : array {
            return $reader->getInventory()->toArrayForAPI();
        });
        return $this->outputSuccess(self::COMMAND_INVENTORY, $data['data'], $data['pagination'], $params);
    }

    private function execute_log(BaseSaveFile $save, QueryParameters $params): string
    {
        // Auto-cache for unfiltered queries (WP3: Logbook Performance Optimization)
        $filter = $params->filter;
        $cacheKey = $params->cacheKey;

        // Use auto-cache key if no filter and no manual cache key
        $effectiveCacheKey = $cacheKey;
        if (empty($filter) && empty($cacheKey)) {
            $effectiveCacheKey = '_log_unfiltered_' . $save->getSaveID();
        }

        $fullLogCacheKey = $this->getFullLogCacheKey($save);

        $data = $this->applyFilteringAndPaginationLazy($save, $params, $effectiveCacheKey, function() use ($save, $fullLogCacheKey) : array {
            if ($this->cache->isValid($save, $fullLogCacheKey)) {
                $cached = $this->cache->retrieve($save, $fullLogCacheKey);

                if (is_array($cached)) {
                    return $cached;
                }
            }

            $data = $save->getDataReader()->getLog()->toArrayForAPI();
            $this->cache->store($save, $fullLogCacheKey, $data);

            return $data;
        });
        return $this->outputSuccess(self::COMMAND_LOG, $data['data'], $data['pagination'], $params);
    }

    private function execute_khaakStations(BaseSaveFile $save, QueryParameters $params): string
    {
        $reader = $save->getDataReader();

        $data = $this->applyFilteringAndPaginationLazy($save, $params, null, function() use ($reader) : array {
            return $reader->getKhaakStations()->toArrayForAPI();
        });
        return $this->outputSuccess(self::COMMAND_KHAAK_STATIONS, $data['data'], $data['pagination'], $params);
    }

    private function execute_shipLosses(BaseSaveFile $save, QueryParameters $params): string
    {
        $reader = $save->getDataReader();

        $data = $this->applyFilteringAndPaginationLazy($save, $params, null, function() use ($reader) : array {
            return $reader->getShipLosses()->toArrayForAPI();
        });
        return $this->outputSuccess(self::COMMAND_SHIP_LOSSES, $data['data'], $data['pagination'], $params);
    }

    private function execute_ships(BaseSaveFile $save, QueryParameters $params): string
    {
        $data = $this->applyFilteringAndPaginationLazy($save, $params, null, function() use ($save) : array {
            return $this->flattenCollectionArray($save->getDataReader()->getCollections()->ships()->loadData());
        });
        return $this->outputSuccess(self::COMMAND_SHIPS, $data['data'], $data['pagination'], $params);
    }

    private function execute_stations(BaseSaveFile $save, QueryParameters $params): string
    {
        $data = $this->applyFilteringAndPaginationLazy($save, $params, null, function() use ($save) : array {
            return $this->flattenCollectionArray($save->getDataReader()->getCollections()->stations()->loadData());
        });
        return $this->outputSuccess(self::COMMAND_STATIONS, $data['data'], $data['pagination'], $params);
    }

    private function execute_people(BaseSaveFile $save, QueryParameters $params): string
    {
        $data = $this->applyFilteringAndPaginationLazy($save, $params, null, function() use ($save) : array {
            return $this->flattenCollectionArray($save->getDataReader()->getCollections()->people()->loadData());
        });
        return $this->outputSuccess(self::COMMAND_PEOPLE, $data['data'], $data['pagination'], $params);
    }

    private function execute_sectors(BaseSaveFile $save, QueryParameters $params): string
    {
        $data = $this->applyFilteringAndPaginationLazy($save, $params, null, function() use ($save) : array {
            return $this->flattenCollectionArray($save->getDataReader()->getCollections()->sectors()->loadData());
        });
        return $this->outputSuccess(self::COMMAND_SECTORS, $data['data'], $data['pagination'], $params);
    }

    private function execute_zones(BaseSaveFile $save, QueryParameters $params): string
    {
        $data = $this->applyFilteringAndPaginationLazy($save, $params, null, function() use ($save) : array {
            return $this->flattenCollectionArray($save->getDataReader()->getCollections()->zones()->loadData());
        });
        return $this->outputSuccess(self::COMMAND_ZONES, $data['data'], $data['pagination'], $params);
    }

    private function execute_regions(BaseSaveFile $save, QueryParameters $params): string
    {
        $data = $this->applyFilteringAndPaginationLazy($save, $params, null, function() use ($save) : array {
            return $this->flattenCollectionArray($save->getDataReader()->getCollections()->regions()->loadData());
        });
        return $this->outputSuccess(self::COMMAND_REGIONS, $data['data'], $data['pagination'], $params);
    }

    private function execute_clusters(BaseSaveFile $save, QueryParameters $params): string
    {
        $data = $this->applyFilteringAndPaginationLazy($save, $params, null, function() use ($save) : array {
            return $this->flattenCollectionArray($save->getDataReader()->getCollections()->clusters()->loadData());
        });
        return $this->outputSuccess(self::COMMAND_CLUSTERS, $data['data'], $data['pagination'], $params);
    }

    private function execute_celestials(BaseSaveFile $save, QueryParameters $params): string
    {
        $data = $this->applyFilteringAndPaginationLazy($save, $params, null, function() use ($save) : array {
            return $this->flattenCollectionArray($save->getDataReader()->getCollections()->celestials()->loadData());
        });
        return $this->outputSuccess(self::COMMAND_CELESTIALS, $data['data'], $data['pagination'], $params);
    }

    private function execute_eventLog(BaseSaveFile $save, QueryParameters $params): string
    {
        $data = $this->applyFilteringAndPaginationLazy($save, $params, null, function() use ($save) : array {
            return $this->flattenCollectionArray($save->getDataReader()->getCollections()->eventLog()->loadData());
        });
        return $this->outputSuccess(self::COMMAND_EVENT_LOG, $data['data'], $data['pagination'], $params);
    }

    private function execute_clearCache(QueryParameters $params): string
    {
        $count = $this->cache->clearAll();

        return $this->outputSuccess(self::COMMAND_CLEAR_CACHE, [
            'cleared' => $count,
            'message' => sprintf('Cleared %d cache director%s', $count, $count === 1 ? 'y' : 'ies')
        ], null, $params);
    }

    private function execute_listSaves(QueryParameters $params): string
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

        return $this->outputSuccess(self::COMMAND_LIST_SAVES, $result, null, $params);
    }

    private function execute_queueExtraction(QueryParameters $params): string
    {
        $queue = new ExtractionQueue($this->manager);

        // Check for --list flag
        if ($params->listFlag) {
            $items = $queue->getAll();
            return $this->outputSuccess(self::COMMAND_QUEUE_EXTRACTION, [
                'queue' => $items,
                'count' => count($items)
            ], null, $params);
        }

        // Check for --clear flag
        if ($params->clearFlag) {
            $count = $queue->count();
            $queue->clear();
            return $this->outputSuccess(self::COMMAND_QUEUE_EXTRACTION, [
                'cleared' => $count,
                'message' => sprintf('Cleared %d item%s from queue', $count, $count === 1 ? '' : 's')
            ], null, $params);
        }

        // Get saves to queue
        $savesList = $params->saves;
        $singleSave = $params->saveIdentifier;

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

        return $this->outputSuccess(self::COMMAND_QUEUE_EXTRACTION, $result, null, $params);
    }

    private function execute_listPaths(QueryParameters $params): string
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

        return $this->outputSuccess(self::COMMAND_LIST_PATHS, $result, null, $params);
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
     * @param QueryParameters $params The query parameters
     * @param string|null $overrideCacheKey Override cache key (for auto-cache, WP3)
     * @return array{data: array, pagination: array|null} Processed data and pagination metadata
     */
    /**
     * Apply filtering and pagination to data, with lazy data generation.
     *
     * @param BaseSaveFile $save The save file (for caching)
     * @param QueryParameters $params The query parameters
     * @param string|null $overrideCacheKey Override cache key (for auto-cache, WP3)
     * @param callable(): array $dataProvider Lazy data provider
     * @return array{data: array, pagination: array|null} Processed data and pagination metadata
     */
    private function applyFilteringAndPaginationLazy(BaseSaveFile $save, QueryParameters $params, ?string $overrideCacheKey, callable $dataProvider): array
    {
        $filter = $params->filter;
        $limit = $params->limit;
        $offset = $params->offset;

        // Use override cache key if provided, otherwise use CLI argument
        $cacheKey = $overrideCacheKey ?? $params->cacheKey;

        $data = null;
        $cacheHit = false;

        if (!empty($cacheKey) && $this->cache->isValid($save, $cacheKey)) {
            $data = $this->cache->retrieve($save, $cacheKey);
            $cacheHit = is_array($data);
        }

        if (!$cacheHit) {
            $data = $dataProvider();

            if (!empty($filter)) {
                $data = $this->applyFilter($data, $filter);
            }

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

    private function getFullLogCacheKey(BaseSaveFile $save): string
    {
        return '_log_full_' . $save->getSaveID();
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
     * Output a success response.
     */
    private function outputSuccess(string $command, mixed $data, ?array $pagination, QueryParameters $params): string
    {
        $response = JsonResponseBuilder::success($command, $data, $pagination, $params->isPretty);
        
        // In JSON event mode, wrap result to distinguish from progress events
        if ($params->isJson) {
            $decoded = json_decode($response, true);
            $wrapped = [
                'type' => 'result',
                'data' => $decoded
            ];
            return json_encode($wrapped, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        }
        
        return $response;
    }

    // endregion
}

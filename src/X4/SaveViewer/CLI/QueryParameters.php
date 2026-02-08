<?php
/**
 * @package X4SaveViewer
 * @subpackage CLI
 * @see \Mistralys\X4\SaveViewer\CLI\QueryParameters
 */

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\CLI;

use League\CLImate\CLImate;

/**
 * Immutable value object holding CLI query parameters.
 *
 * This class decouples CLI argument parsing from business logic,
 * allowing tests to inject parameters directly without relying on
 * league/climate's argument parser.
 *
 * @package X4SaveViewer
 * @subpackage CLI
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */
class QueryParameters
{
    /**
     * @param string $saveIdentifier Save name or ID (empty string if not provided)
     * @param string $filter JMESPath filter expression (empty string if not provided)
     * @param int $limit Maximum number of results (0 if not provided)
     * @param int $offset Number of results to skip (0 if not provided)
     * @param string $cacheKey Cache key for result reuse (empty string if not provided)
     * @param bool $isPretty Pretty-print JSON output flag
     * @param bool $isJson Enable JSON event streaming mode (NDJSON progress + result wrapper)
     * @param string $saves Space-separated list of save names/IDs for queue-extraction
     * @param bool $listFlag List queue contents flag (for queue-extraction)
     * @param bool $clearFlag Clear the queue flag (for queue-extraction)
     */
    public function __construct(
        public readonly string $saveIdentifier = '',
        public readonly string $filter = '',
        public readonly int $limit = 0,
        public readonly int $offset = 0,
        public readonly string $cacheKey = '',
        public readonly bool $isPretty = false,
        public readonly bool $isJson = false,
        public readonly string $saves = '',
        public readonly bool $listFlag = false,
        public readonly bool $clearFlag = false
    ) {
    }

    /**
     * Create QueryParameters from CLImate's parsed arguments.
     *
     * Extracts all parameter values from CLImate's argument parser.
     * Used by QueryHandler::handle() for real CLI usage.
     *
     * @param CLImate $cli The CLImate instance with parsed arguments
     * @return self
     */
    public static function fromCLImate(CLImate $cli): self
    {
        return new self(
            saveIdentifier: (string)($cli->arguments->get('save') ?? ''),
            filter: (string)($cli->arguments->get('filter') ?? ''),
            limit: (int)($cli->arguments->get('limit') ?? 0),
            offset: (int)($cli->arguments->get('offset') ?? 0),
            cacheKey: (string)($cli->arguments->get('cache-key') ?? ''),
            isPretty: $cli->arguments->defined('pretty'),
            isJson: $cli->arguments->defined('json'),
            saves: (string)($cli->arguments->get('saves') ?? ''),
            listFlag: $cli->arguments->defined('list'),
            clearFlag: $cli->arguments->defined('clear')
        );
    }

    /**
     * Create QueryParameters for testing with explicit values.
     *
     * Accepts an associative array with optional parameter overrides.
     * Provides sensible defaults (empty strings, 0, false) for all parameters.
     *
     * Example:
     * ```php
     * $params = QueryParameters::forTest([
     *     'saveIdentifier' => 'quicksave',
     *     'limit' => 10,
     *     'isPretty' => true
     * ]);
     * ```
     *
     * @param array<string, mixed> $params Optional parameter overrides
     * @return self
     */
    public static function forTest(array $params = []): self
    {
        return new self(
            saveIdentifier: (string)($params['saveIdentifier'] ?? ''),
            filter: (string)($params['filter'] ?? ''),
            limit: (int)($params['limit'] ?? 0),
            offset: (int)($params['offset'] ?? 0),
            cacheKey: (string)($params['cacheKey'] ?? ''),
            isPretty: (bool)($params['isPretty'] ?? false),
            isJson: (bool)($params['isJson'] ?? false),
            saves: (string)($params['saves'] ?? ''),
            listFlag: (bool)($params['listFlag'] ?? false),
            clearFlag: (bool)($params['clearFlag'] ?? false)
        );
    }
}


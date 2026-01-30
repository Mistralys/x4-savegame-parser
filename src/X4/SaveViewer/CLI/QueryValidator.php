<?php
/**
 * @package X4SaveViewer
 * @subpackage CLI
 * @see \Mistralys\X4\SaveViewer\CLI\QueryValidator
 */

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\CLI;

use Mistralys\X4\SaveViewer\Data\BaseSaveFile;
use Mistralys\X4\SaveViewer\Data\SaveManager;

/**
 * Validates query parameters and provides actionable error messages.
 *
 * @package X4SaveViewer
 * @subpackage CLI
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */
class QueryValidator
{
    public const int ERROR_INVALID_PAGINATION_LIMIT = 95001;
    public const int ERROR_INVALID_PAGINATION_OFFSET = 95002;
    public const int ERROR_INVALID_CACHE_KEY = 95003;
    public const int ERROR_SAVE_NOT_EXTRACTED = 95004;

    private SaveManager $manager;

    public function __construct(SaveManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Validate a save identifier and return the save file if valid.
     *
     * Checks if the save exists and has been extracted.
     *
     * @param string $saveIdentifier Save name or ID
     * @return BaseSaveFile The validated save file
     * @throws QueryValidationException If validation fails
     */
    public function validateSave(string $saveIdentifier): BaseSaveFile
    {
        // Try by name first
        if ($this->manager->nameExists($saveIdentifier)) {
            $save = $this->manager->getSaveByName($saveIdentifier);
        }
        // Try by ID
        elseif ($this->manager->idExists($saveIdentifier)) {
            $save = $this->manager->getByID($saveIdentifier);
        }
        // Not found
        else {
            $this->throwValidationError(
                sprintf('Save "%s" not found', $saveIdentifier),
                SaveManager::ERROR_CANNOT_FIND_BY_NAME,
                ['Run: bin/extract -l  (to list available saves)']
            );
        }

        // Check if extracted
        if (!$save->isUnpacked()) {
            $this->throwValidationError(
                sprintf('Save "%s" is not extracted', $saveIdentifier),
                self::ERROR_SAVE_NOT_EXTRACTED,
                [sprintf('Run: bin/extract -e %s', $saveIdentifier)]
            );
        }

        return $save;
    }

    /**
     * Validate pagination parameters.
     *
     * @param int|null $limit Maximum number of results
     * @param int|null $offset Number of results to skip
     * @return void
     * @throws QueryValidationException If validation fails
     */
    public function validatePagination(?int $limit, ?int $offset): void
    {
        if ($limit !== null && $limit <= 0) {
            $this->throwValidationError(
                sprintf('Limit must be positive, got: %d', $limit),
                self::ERROR_INVALID_PAGINATION_LIMIT
            );
        }

        if ($offset !== null && $offset < 0) {
            $this->throwValidationError(
                sprintf('Offset must be non-negative, got: %d', $offset),
                self::ERROR_INVALID_PAGINATION_OFFSET
            );
        }
    }

    /**
     * Validate a cache key format.
     *
     * Cache keys should contain only alphanumeric characters, hyphens, and underscores.
     *
     * @param string|null $cacheKey The cache key to validate
     * @return void
     * @throws QueryValidationException If validation fails
     */
    public function validateCacheKey(?string $cacheKey): void
    {
        if ($cacheKey === null || $cacheKey === '') {
            return;
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $cacheKey)) {
            $this->throwValidationError(
                'Cache key contains invalid characters (only alphanumeric, hyphens, and underscores allowed)',
                self::ERROR_INVALID_CACHE_KEY
            );
        }
    }

    /**
     * Throw a validation error with optional actionable suggestions.
     *
     * @param string $message Error message
     * @param int $code Error code
     * @param string[] $actions Suggested actions
     * @return never
     * @throws QueryValidationException
     */
    private function throwValidationError(
        string $message,
        int $code,
        array $actions = []
    ): never
    {
        throw new QueryValidationException($message, $code, $actions);
    }
}

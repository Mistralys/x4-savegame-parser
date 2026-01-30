<?php
/**
 * @package X4SaveViewer
 * @subpackage CLI
 * @see \Mistralys\X4\SaveViewer\CLI\QueryValidationException
 */

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\CLI;

use Mistralys\X4\SaveViewer\SaveViewerException;

/**
 * Exception thrown when query validation fails.
 *
 * Includes actionable suggestions to help the user resolve the issue.
 *
 * @package X4SaveViewer
 * @subpackage CLI
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */
class QueryValidationException extends SaveViewerException
{
    /**
     * @var string[]
     */
    private array $actions;

    /**
     * @param string $message Error message
     * @param int $code Error code
     * @param string[] $actions Suggested actions to resolve the error
     */
    public function __construct(string $message, int $code, array $actions = [])
    {
        parent::__construct($message, '', $code);
        $this->actions = $actions;
    }

    /**
     * Get suggested actions to resolve this validation error.
     *
     * @return string[]
     */
    public function getActions(): array
    {
        return $this->actions;
    }
}

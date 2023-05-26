<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader\Log\Categories;

use Mistralys\X4\SaveViewer\Data\SaveReader\Log\LogCategory;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log\LogEntry;

class DetectionCategory extends LogCategory
{
    /**
     * @var callable
     */
    private $detectCallback;

    public function __construct(string $id, string $label, float $startTime, callable $detectCallback)
    {
        parent::__construct($id, $label, $startTime);

        $this->detectCallback = $detectCallback;
    }

    /**
     * Checks whether the entry should be assigned to
     * this category.
     *
     * @param LogEntry $entry
     * @return bool
     */
    public function matchesEntry(LogEntry $entry) : bool
    {
        return call_user_func($this->detectCallback, $entry) === true;
    }
}

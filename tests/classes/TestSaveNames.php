<?php

declare(strict_types=1);

namespace X4\SaveGameParserTests\TestClasses;

/**
 * Central source of truth for test savegame names.
 * Used by both the extraction script and all test cases.
 */
class TestSaveNames
{
    /**
     * Advanced creative mode save with comprehensive data.
     * - Full logbook with many entries
     * - Ship losses data populated
     * - Complete player assets
     * - Used by most tests as the default save
     */
    public const SAVE_ADVANCED_CREATIVE = 'advanced-creative-v8';

    /**
     * Start scientist save with minimal player data.
     * - Empty data-losses.json array
     * - Only 1 entry in event log
     * - Sparse player-specific data
     * - Full NPC universe data present
     * - Used for edge case testing
     */
    public const SAVE_START_SCIENTIST = 'start-scientist-v8';

    /**
     * Get all test save names for iteration.
     *
     * @return string[]
     */
    public static function getAllSaveNames(): array
    {
        return [
            self::SAVE_ADVANCED_CREATIVE,
            self::SAVE_START_SCIENTIST
        ];
    }
}


<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\Data\SaveReader;

use Mistralys\X4Saves\SaveParser\Tags\Tag\StatsTag;

class Statistics extends Info
{
    /**
     * @var array<string,string>
     */
    private array $stats = array();

    protected function getAutoDataName(): string
    {
        return StatsTag::SAVE_NAME;
    }

    public function getStats() : array
    {
        return $this->data;
    }
}

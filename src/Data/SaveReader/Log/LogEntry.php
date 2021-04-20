<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\Data\SaveReader\Log;

use AppUtils\ConvertHelper;

class LogEntry
{
    private string $category;
    private float $time;
    private string $title;
    private string $text;
    private string $faction;

    public function __construct(string $category, float $time, string $title, string $text, string $faction)
    {
        $this->category = $category;
        $this->time = $time;
        $this->title = $title;
        $this->text = $text;
        $this->faction = $faction;
    }

    /**
     * @return string
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * @return string
     */
    public function getFaction(): string
    {
        return $this->faction;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @return float
     */
    public function getTime(): float
    {
        return $this->time;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    public function getInterval() : \DateInterval
    {
        return ConvertHelper::seconds2interval(intval($this->getTime()));
    }

    public function getHours() : int
    {
        return ConvertHelper::interval2hours($this->getInterval());
    }
}

<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader;

use AppUtils\ConvertHelper;
use DateInterval;

class GameTime
{
    private float $time;
    private float $startTime;

    public function __construct(float $time, float $startTime)
    {
        $this->time = $time;
        $this->startTime = $startTime;
    }

    public function getValue() : float
    {
        return $this->startTime - $this->time;
    }

    public function getInterval() : DateInterval
    {
        return ConvertHelper::seconds2interval((int)$this->getValue());
    }

    public function getIntervalStr() : string
    {
        return ConvertHelper::interval2string($this->getInterval());
    }

    public function getHours() : int
    {
        return ConvertHelper::interval2hours($this->getInterval());
    }

    /**
     * @param string|float $time
     * @param float $startTime
     */
    public static function create($time, float $startTime) : GameTime
    {
        return new GameTime((float)$time, $startTime);
    }

    public function getStartValue() : float
    {
        return $this->startTime;
    }
}

<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader;

use AppUtils\ArrayDataCollection;
use AppUtils\ConvertHelper;
use DateInterval;

class GameTime
{
    public const string SERIALIZED_VALUE = 'value';
    public const string SERIALIZED_START_TIME = 'start';
    private float $time;
    private float $startTime;

    public function __construct(float $time, float $startTime)
    {
        $this->time = $time;
        $this->startTime = $startTime;
    }

    public function getValue() : float
    {
        return $this->time;
    }

    public function getDuration() : float
    {
        return $this->startTime - $this->time;
    }

    public function getInterval() : DateInterval
    {
        return ConvertHelper::seconds2interval((int)$this->getDuration());
    }

    public function getIntervalStr() : string
    {
        $interval = $this->getInterval();

        if($interval->days > 0) {
            return $interval->format('%dd %Hh %Is');
        }

        return $interval->format('%Hh %Is');
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

    public static function createFromArray(array $serializedData) : GameTime
    {
        $data = ArrayDataCollection::create($serializedData);

        return new GameTime(
            $data->getFloat(self::SERIALIZED_VALUE),
            $data->getFloat(self::SERIALIZED_START_TIME)
        );
    }

    public function toArray() : array
    {
        return array(
            self::SERIALIZED_VALUE => $this->getValue(),
            self::SERIALIZED_START_TIME => $this->startTime
        );
    }
}

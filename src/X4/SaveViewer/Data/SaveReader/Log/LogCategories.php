<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader\Log;

use Mistralys\X4\SaveViewer\SaveViewerException;

abstract class LogCategories
{
    public const int ERROR_CATEGORY_ID_DOES_NOT_EXIST = 137101;

    public const string CATEGORY_TIPS = 'tips';
    public const string CATEGORY_STATION_FINANCE = 'station-finance';
    public const string CATEGORY_PROMOTION = 'promotion';
    public const string CATEGORY_ALERT = 'alert';
    public const string CATEGORY_STATION_BUILDING = 'station-building';
    public const string CATEGORY_REWARD = 'reward';
    public const string CATEGORY_TRADE = 'trade';
    public const string CATEGORY_EMERGENCY = 'emergency';
    public const string CATEGORY_LOCKBOX = 'lockbox';
    public const string CATEGORY_REPUTATION = 'reputation';
    public const string CATEGORY_ATTACKED = 'attacked';
    public const string CATEGORY_SHIP_CONSTRUCTION = 'ship-construction';
    public const string CATEGORY_DESTROYED = 'destroyed';
    public const string CATEGORY_SHIP_SUPPLY = 'ship-supply';
    public const string CATEGORY_MISSIONS = 'missions';
    public const string CATEGORY_WAR = 'war';
    public const string CATEGORY_CREW_ASSIGNMENT = 'crew-assignment';
    public const string CATEGORY_MISCELLANEOUS = 'misc';

    /**
     * @var array<string,LogCategory>
     */
    protected array $categories = array();
    private float $startTime;

    public function __construct(float $startTime)
    {
        $this->startTime = $startTime;

        $this->categories[self::CATEGORY_MISCELLANEOUS] = new MiscLogCategory($startTime);

        $this->initCategories();
    }

    protected function initCategories() : void
    {

    }

    public function getStartTime() : float
    {
        return $this->startTime;
    }

    /**
     * @return LogCategory[]
     */
    public function getAll() : array
    {
        return array_values($this->categories);
    }

    /**
     * @param string $id
     * @return LogCategory
     * @throws SaveViewerException {@see self::ERROR_CATEGORY_ID_DOES_NOT_EXIST}
     */
    public function getByID(string $id) : LogCategory
    {
        if(isset($this->categories[$id])) {
            return $this->categories[$id];
        }

        throw new SaveViewerException(
            'Category ID not found.',
            sprintf(
                'The ID [%s] does not exist. Available IDs are: [%s].',
                $id,
                implode(', ', $this->getIDs())
            ),
            self::ERROR_CATEGORY_ID_DOES_NOT_EXIST
        );
    }

    /**
     * @return string[]
     */
    public function getIDs() : array
    {
        return array_keys($this->categories);
    }

    /**
     * @var LogEntry[]|null
     */
    private ?array $cachedEntries = null;

    public function getEntries() : array
    {
        if(isset($this->cachedEntries)) {
            return $this->cachedEntries;
        }

        $result = array();

        foreach($this->categories as $category)
        {
            $result += $category->getEntries();
        }

        usort($result, static function(LogEntry $a, LogEntry $b) : float {
            return $a->getTime()->getDuration() - $b->getTime()->getDuration();
        });

        $this->cachedEntries = $result;

        return $result;
    }
}

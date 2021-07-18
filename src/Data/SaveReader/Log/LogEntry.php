<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\Data\SaveReader\Log;

use AppUtils\ConvertHelper;
use DateInterval;
use Mistralys\X4Saves\SaveParser\Tags\Tag\LogTag;

class LogEntry
{
    const CATEGORY_EVENT = 'event';
    const CATEGORY_REPUTATION = 'reputation';
    const CATEGORY_PROMOTION = 'promotion';
    const CATEGORY_DISCOUNT = 'discount';
    const CATEGORY_REWARD = 'reward';
    const CATEGORY_LOCKBOX = 'lockbox';
    const CATEGORY_EMERGENCY = 'emergency';
    const CATEGORY_IGNORE = '__ignore';
    const CATEGORY_PIRATE_HARASSMENT = 'pirates';
    const CATEGORY_SHIP_SUPPLY = 'ship-supply';
    const CATEGORY_WAR = 'war';
    const CATEGORY_TRADE = 'trade';
    const CATEGORY_STATION_FINANCE = 'station-finance';
    const CATEGORY_STATION_BUILDING = 'station-building';
    const CATEGORY_DESTROYED = 'destroyed';
    const CATEGORY_ATTACKED = 'attacked';
    const CATEGORY_CREW_ASSIGNMENT = 'crew-assignment';
    const CATEGORY_SHIP_CONSTRUCTION = 'ship-construction';

    /**
     * @var array<string,string>
     */
    private array $data;

    protected array $terms = array(
        'emergency alert' => self::CATEGORY_EMERGENCY,
        'reputation gained' => self::CATEGORY_REPUTATION,
        'reputation lost' => self::CATEGORY_REPUTATION,
        'promotion' => self::CATEGORY_PROMOTION,
        'discount' => self::CATEGORY_DISCOUNT,
        'rewarded' => self::CATEGORY_REWARD,
        'task complete' => self::CATEGORY_IGNORE,
        'police interdiction' => self::CATEGORY_IGNORE,
        'found lockbox' => self::CATEGORY_LOCKBOX,
        'pirate harassment' => self::CATEGORY_PIRATE_HARASSMENT,
        'ship resupplied' => self::CATEGORY_SHIP_SUPPLY,
        'finished repairing' => self::CATEGORY_SHIP_SUPPLY,
        'finished construction' => self::CATEGORY_SHIP_CONSTRUCTION,
        'assigned individual' => self::CATEGORY_CREW_ASSIGNMENT,
        'forced to flee' => self::CATEGORY_ATTACKED,
        'is under attack' => self::CATEGORY_ATTACKED,
        'was destroyed' => self::CATEGORY_DESTROYED,
        'station completed' => self::CATEGORY_STATION_BUILDING,
        'station under construction' => self::CATEGORY_STATION_BUILDING,
        'has dropped to' => self::CATEGORY_STATION_FINANCE,
        'received surplus' => self::CATEGORY_STATION_FINANCE,
        'mounting defence' => self::CATEGORY_WAR,
        'reconnaissance in' => self::CATEGORY_WAR,
        'trade completed' => self::CATEGORY_TRADE,
        'war update' => self::CATEGORY_WAR,
    );

    private string $category = '';

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getCategory(): string
    {
        if($this->category === '')
        {
            $this->category = $this->detectCategory();
        }

        return $this->category;
    }

    /**
     * @return string
     */
    public function getFaction(): string
    {
        return $this->data[LogTag::KEY_FACTION];
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->data[LogTag::KEY_TEXT];
    }

    /**
     * @return float
     */
    public function getTime(): float
    {
        return floatval($this->data[LogTag::KEY_TIME]);
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->data[LogTag::KEY_TITLE];
    }

    public function getInterval() : DateInterval
    {
        return ConvertHelper::seconds2interval(intval($this->getTime()));
    }

    public function getHours() : int
    {
        return ConvertHelper::interval2hours($this->getInterval());
    }

    private function detectCategory() : string
    {
        $result = self::CATEGORY_EVENT;

        if(isset($this->data[LogTag::KEY_CATEGORY]))
        {
            $result = $this->data[LogTag::KEY_CATEGORY];
        }

        $title = $this->getTitle();
        $text = $this->getText();

        foreach($this->terms as $term => $termCategory)
        {
            if (stristr($title, $term) !== false || stristr($text, $term) !== false) {
                $result = $termCategory;
                break;
            }
        }

        return $result;
    }
}

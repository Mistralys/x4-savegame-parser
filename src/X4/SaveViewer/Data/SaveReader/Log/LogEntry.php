<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader\Log;

use AppUtils\ConvertHelper;
use DateInterval;
use Mistralys\X4\SaveViewer\Parser\Tags\Tag\LogTag;

class LogEntry
{
    public const CATEGORY_MISSIONS = 'missions';
    public const CATEGORY_TIPS = 'tips';
    public const CATEGORY_EVENT = 'event';
    public const CATEGORY_REPUTATION = 'reputation';
    public const CATEGORY_PROMOTION = 'promotion';
    public const CATEGORY_DISCOUNT = 'discount';
    public const CATEGORY_REWARD = 'reward';
    public const CATEGORY_LOCKBOX = 'lockbox';
    public const CATEGORY_EMERGENCY = 'emergency';
    public const CATEGORY_IGNORE = '__ignore';
    public const CATEGORY_PIRATE_HARASSMENT = 'pirates';
    public const CATEGORY_SHIP_SUPPLY = 'ship-supply';
    public const CATEGORY_WAR = 'war';
    public const CATEGORY_TRADE = 'trade';
    public const CATEGORY_STATION_FINANCE = 'station-finance';
    public const CATEGORY_STATION_BUILDING = 'station-building';
    public const CATEGORY_DESTROYED = 'destroyed';
    public const CATEGORY_ATTACKED = 'attacked';
    public const CATEGORY_CREW_ASSIGNMENT = 'crew-assignment';
    public const CATEGORY_SHIP_CONSTRUCTION = 'ship-construction';

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
        $result = $this->data[LogTag::KEY_CATEGORY] ?? self::CATEGORY_EVENT;

        $title = $this->getTitle();
        $text = $this->getText();

        foreach($this->terms as $term => $termCategory)
        {
            if (stripos($title, $term) !== false || stripos($text, $term) !== false) {
                $result = $termCategory;
                break;
            }
        }

        return $result;
    }
}

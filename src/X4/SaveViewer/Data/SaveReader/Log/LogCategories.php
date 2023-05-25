<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader\Log;

use Mistralys\X4\SaveViewer\Data\SaveReader\Log;
use Mistralys\X4\SaveViewer\Parser\Types\LogEntryType;
use function AppLocalize\t;

class LogCategories
{
    public const CATEGORY_TIPS = 'tips';
    public const CATEGORY_STATION_FINANCE = 'station-finance';
    public const CATEGORY_PROMOTION = 'promotion';
    public const CATEGORY_IGNORE = '__ignore';
    public const CATEGORY_STATION_BUILDING = 'station-building';
    public const CATEGORY_REWARD = 'reward';
    public const CATEGORY_TRADE = 'trade';
    public const CATEGORY_EMERGENCY = 'emergency';
    public const CATEGORY_EVENT = 'event';
    public const CATEGORY_LOCKBOX = 'lockbox';
    public const CATEGORY_REPUTATION = 'reputation';
    public const CATEGORY_PIRATE_HARASSMENT = 'pirates';
    public const CATEGORY_ATTACKED = 'attacked';
    public const CATEGORY_SHIP_CONSTRUCTION = 'ship-construction';
    public const CATEGORY_DESTROYED = 'destroyed';
    public const CATEGORY_SHIP_SUPPLY = 'ship-supply';
    public const CATEGORY_MISSIONS = 'missions';
    public const CATEGORY_WAR = 'war';
    public const CATEGORY_CREW_ASSIGNMENT = 'crew-assignment';
    public const CATEGORY_MISCELLANEOUS = 'misc';

    private Log $log;

    /**
     * @var array<string,LogCategory>
     */
    private array $categories = array();

    public function __construct(Log $log)
    {
        $this->log = $log;

        $this->registerCategories();
    }

    private function registerCategories() : void
    {
        $this->categories[] = new MiscLogCategory();

        $this->registerTermMatch(
            self::CATEGORY_EMERGENCY,
            t('Emergency'),
            array('emergency alert')
        );

        $this->registerTermMatch(
            self::CATEGORY_REPUTATION,
            t('Reputation'),
            array(
                'reputation gained',
                'reputation lost'
            )
        );

        $this->registerTermMatch(
            self::CATEGORY_PROMOTION,
            t('Promotion'),
            array(
                'promotion',
                'discount'
            )
        );

        $this->registerTermMatch(
            self::CATEGORY_LOCKBOX,
            t('Lockbox'),
            array('found lockbox')
        );

        $this->registerTermMatch(
            self::CATEGORY_DESTROYED,
            t('Destroyed'),
            array('destroyed')
        );

        $this->registerTermMatch(
            self::CATEGORY_ATTACKED,
            t('Ship defense'),
            array(
                'forced to flee',
                'is under attack',
                'pirate harassment',
                'police interdiction'
            )
        );

        $this->registerTermMatch(
            self::CATEGORY_SHIP_SUPPLY,
            t('Ship supply'),
            array('ship resupplied', 'finished repairing')
        );

        $this->registerTermMatch(
            self::CATEGORY_STATION_BUILDING,
            t('Station building'),
            array(
                'station completed',
                'station under construction'
            )
        );

        $this->registerTermMatch(
            self::CATEGORY_STATION_FINANCE,
            t('Station finance'),
            array(
                'has dropped to',
                'received surplus'
            )
        );

        $this->registerTermMatch(
            self::CATEGORY_REWARD,
            t('Rewards'),
            array('reward')
        );

        $this->registerTermMatch(
            self::CATEGORY_SHIP_CONSTRUCTION,
            t('Ship construction'),
            array('finished construction')
        );

        $this->registerTermMatch(
            self::CATEGORY_CREW_ASSIGNMENT,
            t('Crew assignment'),
            array('assigned individual')
        );

        $this->registerTermMatch(
            self::CATEGORY_WAR,
            t('War updates'),
            array(
                'mounting defence',
                'reconnaissance in',
                'war update'
            )
        );

        $this->registerTermMatch(
            self::CATEGORY_TRADE,
            t('Trade'),
            array('trade completed')
        );

        $this->registerCategoryNameMatch(
            self::CATEGORY_TIPS,
            t('Tips'),
            'tips'
        );

        $this->registerCategoryNameMatch(
            self::CATEGORY_MISSIONS,
            t('Missions'),
            'missions'
        );
    }

    public function registerCategoryNameMatch(string $id, string $label, string $categoryName) : LogCategory
    {
        return $this->registerCategory(
            $id,
            $label,
            static function(LogEntry $entry) use($categoryName) : bool
            {
                return $entry->getCategoryName() === $categoryName;
            }
        );
    }

    /**
     * Registers a category that will be automatically assigned
     * to log entries whose title or text contain the specified
     * search term.
     *
     * @param string $id
     * @param string $label
     * @param string[] $terms
     * @return LogCategory
     */
    public function registerTermMatch(string $id, string $label, array $terms) : LogCategory
    {
        return $this->registerCategory(
            $id,
            $label,
            static function(LogEntry $entry) use($terms) : bool
            {
                $title = $entry->getTitle().' '.$entry->getText();

                foreach($terms as $term) {
                    if( stripos($title, $term) !== false) {
                        return true;
                    }
                }

                return false;
            }
        );
    }

    public function registerCategory(string $id, string $label, callable $detectCallback) : LogCategory
    {
        $this->categories[$id] = new LogCategory($id, $label, $detectCallback);

        return $this->categories[$id];
    }

    /**
     * @return LogCategory[]
     */
    public function getAll() : array
    {
        return array_values($this->categories);
    }

    public function getByID(string $id) : LogCategory
    {
        return $this->categories[$id];
    }
}

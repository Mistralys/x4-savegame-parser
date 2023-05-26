<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader\Log\Categories;

use Mistralys\X4\SaveViewer\Data\SaveReader\Log\LogCategories;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log\LogCategory;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log\LogEntry;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log\MiscLogCategory;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log\X4CategoriesEnum;
use function AppLocalize\t;

/**
 * @property array<string,DetectionCategory|MiscLogCategory> $categories
 * @method MiscLogCategory|DetectionCategory getByID(string $id)
 */
class DetectionCategories extends LogCategories
{
    protected function initCategories() : void
    {
        $this->registerTermMatch(
            self::CATEGORY_EMERGENCY,
            t('Emergency'),
            array('emergency alert')
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

        $this->registerTermMatch(
            self::CATEGORY_REPUTATION,
            t('Reputation'),
            array(
                'reputation gained',
                'reputation lost'
            )
        );

        $labels = X4CategoriesEnum::getCategoryLabels();
        $matching = array(
            X4CategoriesEnum::MISSIONS => self::CATEGORY_MISSIONS,
            X4CategoriesEnum::ALERTS => self::CATEGORY_ALERT,
            X4CategoriesEnum::TIPS => self::CATEGORY_TIPS
        );

        foreach($matching as $x4Category => $categoryID)
        {
            $this->registerCategoryNameMatch(
                $categoryID,
                $labels[$x4Category],
                $x4Category
            );
        }
    }

    public function registerDetectionCategory(string $id, string $label, callable $detectCallback) : DetectionCategory
    {
        $this->categories[$id] = new DetectionCategory(
            $id,
            $label,
            $this->getStartTime(),
            $detectCallback
        );

        return $this->categories[$id];
    }

    /**
     * Registers a category that will be automatically assigned
     * to log entries whose category (given by the game, or indirectly
     * by a mod) matches the specified name.
     *
     * @param string $id
     * @param string $label
     * @param string $categoryName
     * @return LogCategory
     */
    public function registerCategoryNameMatch(string $id, string $label, string $categoryName) : LogCategory
    {
        return $this->registerDetectionCategory(
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
     * @return DetectionCategory
     */
    public function registerTermMatch(string $id, string $label, array $terms) : DetectionCategory
    {
        return $this->registerDetectionCategory(
            $id,
            $label,
            static function(LogEntry $entry) use($terms) : bool
            {
                $title = $entry->getTitle().' '.$entry->getText();

                foreach($terms as $term) {
                    if(stripos($title, $term) !== false) {
                        return true;
                    }
                }

                return false;
            }
        );
    }
}

<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader\Log;

use function AppLocalize\t;

class X4CategoriesEnum
{
    public const string ALERTS = 'alerts';
    public const string MISSIONS = 'missions';
    public const string TIPS = 'tips';
    public const string UPKEEP = 'upkeep';
    public const string NEWS = 'news';

    public static function getCategoryLabels() : array
    {
        return array(
            self::ALERTS => t('Alerts'),
            self::MISSIONS => t('Missions'),
            self::TIPS => t('Tips'),
            self::UPKEEP => t('Upkeep'),
            self::NEWS => t('News')
        );
    }
}
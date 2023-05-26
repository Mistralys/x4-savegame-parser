<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader\Log;

use function AppLocalize\t;

class X4CategoriesEnum
{
    public const ALERTS = 'alerts';
    public const MISSIONS = 'missions';
    public const TIPS = 'tips';
    public const UPKEEP = 'upkeep';
    public const NEWS = 'news';

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
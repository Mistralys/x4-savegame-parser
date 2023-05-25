<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader\Log;

use function AppLocalize\t;

class MiscLogCategory extends LogCategory
{
    public function __construct()
    {
        parent::__construct(
            LogCategories::CATEGORY_MISCELLANEOUS,
            t('Misc'),
            static function () : bool {
                return false;
            }
        );
    }
}
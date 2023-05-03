<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader\Log;

class Destroyed extends LogCategory
{
    public function getCategoryID() : string
    {
        return LogEntry::CATEGORY_DESTROYED;
    }
}
<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\Data\SaveReader\Log;

class Destroyed extends LogCategory
{
    protected function getAutoDataName(): string
    {
        return 'log/destroyed';
    }
}

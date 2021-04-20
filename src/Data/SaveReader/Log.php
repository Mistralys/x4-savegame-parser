<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\Data\SaveReader;

use Mistralys\X4Saves\Data\SaveReader\Log\Destroyed;

class Log extends Info
{
    protected function getAutoDataName(): string
    {
        return '';
    }

    public function getDestroyed() : Destroyed
    {
        return new Destroyed($this->reader);
    }
}

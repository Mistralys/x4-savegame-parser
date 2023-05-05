<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Traits;

interface DebuggableInterface
{
    public function getLogIdentifier() : string;
    public function enableLogging() : self;
    public function disableLogging() : self;
    public function isLoggingEnabled() : bool;
    public function setLoggingEnabled(bool $enabled) : self;
}

<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Traits;

use Mistralys\X4\SaveViewer\Parser\Collections;

interface ComponentInterface
{
    public function getComponentID() : string;
    public function getConnectionID() : string;
    public function getTypeID() : string;
    public function getUniqueID(): string;
    public function getCollections() : Collections;
}

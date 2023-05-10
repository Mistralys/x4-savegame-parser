<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader;

use AppUtils\ArrayDataCollection;
use Mistralys\X4\SaveViewer\Data\SaveReader;
use Mistralys\X4\SaveViewer\Parser\Collections;

abstract class Info extends ArrayDataCollection
{
    protected SaveReader $reader;
    protected Collections $collections;

    public function __construct(SaveReader $reader)
    {
        parent::__construct();

        $this->reader = $reader;
        $this->collections = $reader->getCollections();

        $this->init();
    }

    protected function init() : void
    {

    }
}

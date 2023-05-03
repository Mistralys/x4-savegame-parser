<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Collections;

use Mistralys\X4\SaveViewer\Parser\Traits\PersonContainerInterface;
use Mistralys\X4\SaveViewer\Parser\Types\PersonType;

class PeopleCollection extends BaseCollection
{
    public const COLLECTION_ID = 'people';

    private static int $counter = 0;

    public function getCollectionID() : string
    {
        return self::COLLECTION_ID;
    }

    public function createPerson(PersonContainerInterface $container, string $name='') : PersonType
    {
        self::$counter++;

        $sector = new PersonType($container, self::$counter, $name);

        $this->addComponent($sector);

        return $sector;
    }
}

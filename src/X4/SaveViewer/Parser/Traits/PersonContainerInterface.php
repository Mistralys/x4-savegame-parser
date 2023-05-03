<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Traits;

use Mistralys\X4\SaveViewer\Parser\Types\PersonType;

interface PersonContainerInterface extends ComponentInterface
{
    public const KEY_PERSONS = 'persons';

    /**
     * @param  PersonType $person
     * @return $this
     */
    public function addPerson(PersonType $person) : self;
}

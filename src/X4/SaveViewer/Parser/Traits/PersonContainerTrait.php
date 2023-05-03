<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Traits;

use Mistralys\X4\SaveViewer\Parser\Types\PersonType;

trait PersonContainerTrait
{
    /**
     * @param PersonType $person
     * @return $this
     */
    public function addPerson(PersonType $person) : self
    {
        return $this->setKeyComponent(PersonContainerInterface::KEY_PERSONS, $person);
    }
}

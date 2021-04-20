<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\Data\SaveReader;

use Mistralys\X4Saves\Data\SaveReader;

abstract class Info
{
    protected SaveReader $reader;

    protected array $data = array();

    public function __construct(SaveReader $reader)
    {
        $this->reader = $reader;

        $name = $this->getAutoDataName();
        if(!empty($name)) {
            $this->data = $reader->getRawData($name);
        }

        $this->init();
    }

    protected function init() : void
    {

    }

    abstract protected function getAutoDataName() : string;

    protected function getStringKey(string $name) : string
    {
        if(isset($this->data[$name])) {
            return strval($this->data[$name]);
        }

        return '';
    }

    protected function getIntKey(string $name) : int
    {
        if(isset($this->data[$name])) {
            return intval($this->data[$name]);
        }

        return 0;
    }
}

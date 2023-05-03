<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader;

use Mistralys\X4\SaveViewer\Data\SaveReader;

abstract class Info
{
    protected SaveReader $reader;

    protected array $data = array();
    private int $startAt;
    private int $endAt;

    public function __construct(SaveReader $reader)
    {
        $this->reader = $reader;

        $name = $this->getAutoDataName();
        if(!empty($name))
        {
            $this->autoLoad($name);
        }

        $this->init();
    }

    protected function autoLoad(string $dataID) : void
    {
        if(!$this->reader->dataExists($dataID))
        {
            return;
        }

        $data = $this->reader->getRawData($dataID);

        $this->startAt = (int)$data['startAt'];
        $this->endAt = (int)$data['endAt'];
        $this->data = $data['data'];
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

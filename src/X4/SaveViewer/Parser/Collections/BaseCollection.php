<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Collections;

use AppUtils\FileHelper\JSONFile;
use Mistralys\X4\SaveViewer\Parser\Collections;
use Mistralys\X4\SaveViewer\Parser\Traits\ComponentInterface;
use Mistralys\X4\SaveViewer\Parser\Types\BaseComponentType;

abstract class BaseCollection
{
    protected Collections $collections;

    /**
     * @var array<string,array<int,BaseComponentType>>
     */
    private array $components = array();

    public function __construct(Collections $collections)
    {
        $this->collections = $collections;
    }

    abstract public function getCollectionID() : string;

    public function save() : self
    {
        JSONFile::factory($this->getFilePath())
            ->putData($this->toArray(), true);

        return $this;
    }

    public function getFilePath() : string
    {
        return sprintf(
            '%s/collection-%s.json',
            $this->collections->getOutputFolder(),
            $this->getCollectionID()
        );
    }

    public function loadData() : array
    {
        return JSONFile::factory($this->getFilePath())->parse();
    }

    public function toArray() : array
    {
        $data = array();

        foreach($this->components as $key => $components)
        {
            $data[$key] = array();

            foreach ($components as $component)
            {
                $data[$key][] = $component->toArray();
            }
        }

        return $data;
    }

    public function getComponentByID(string $typeID, string $componentID) : ?BaseComponentType
    {
        if(!isset($this->components[$typeID])) {
            return null;
        }

        foreach($this->components[$typeID] as $component) {
            if($component->getComponentID() === $componentID) {
                return $component;
            }
        }

        return null;
    }

    protected function addComponent(ComponentInterface $component) : void
    {
        $type = $component->getTypeID();

        if(!isset($this->components[$type])) {
            $this->components[$type] = array();
        }

        $this->components[$type][] = $component;
    }

    /**
     * @param string $type
     * @return BaseComponentType[]
     */
    public function getComponentsByType(string $type) : array
    {
        return $this->components[$type] ?? array();
    }

    /**
     * Retrieves the type IDs of the components stored in the collection.
     * @return string[]
     */
    public function getComponentTypes() : array
    {
        return array_keys($this->components);
    }

    public function hasComponentType(string $typeID) : bool
    {
        return in_array($typeID, $this->getComponentTypes(), true);
    }
}

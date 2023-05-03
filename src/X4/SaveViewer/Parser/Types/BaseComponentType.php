<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Types;

use AppUtils\ArrayDataCollection;
use Mistralys\X4\SaveViewer\Parser\Collections;
use Mistralys\X4\SaveViewer\Parser\Traits\ComponentInterface;
use Mistralys\X4\SaveViewer\SaveViewerException;

abstract class BaseComponentType extends ArrayDataCollection implements ComponentInterface
{
    public const KEY_CONNECTION_ID = 'connectionID';
    public const KEY_COMPONENT_ID = 'componentID';
    public const KEY_PARENT_COMPONENT = 'parentComponent';

    public const ERROR_EMPTY_COMPONENT_ID = 135101;

    /**
     * @var array<string,mixed>
     */
    protected array $data = array();
    protected Collections $collections;

    public function __construct(Collections $collections, string $connectionID, string $componentID)
    {
        if(empty($componentID)) {
            throw new SaveViewerException(
                'Empty component ID.',
                '',
                self::ERROR_EMPTY_COMPONENT_ID
            );
        }

        parent::__construct(array_merge(
            array(
                self::KEY_CONNECTION_ID => $connectionID,
                self::KEY_COMPONENT_ID => $componentID,
                self::KEY_PARENT_COMPONENT => ''
            ),
            $this->getDefaultData()
        ));

        $this->collections = $collections;
    }

    abstract protected function getDefaultData() : array;

    public function getCollections() : Collections
    {
        return $this->collections;
    }

    public function getComponentID() : string
    {
        return $this->getString(self::KEY_COMPONENT_ID);
    }

    public function getConnectionID() : string
    {
        return $this->getString(self::KEY_CONNECTION_ID);
    }

    public function toArray() : array
    {
        return $this->data;
    }

    public function getUniqueID(): string
    {
        return $this->getTypeID().':'.$this->getComponentID();
    }

    /**
     * @param string $key
     * @param BaseComponentType $component
     * @return $this
     */
    protected function setKeyComponent(string $key, BaseComponentType $component) : self
    {
        $items = $this->getArray($key);
        $items[] = $component->getUniqueID();

        return $this->setKey($key, $items);
    }

    /**
     * @param ComponentInterface $component
     * @return $this
     */
    protected function setParentComponent(ComponentInterface $component) : self
    {
        return $this->setKey(self::KEY_PARENT_COMPONENT, $component->getUniqueID());
    }

    public function getParentComponent() : ?BaseComponentType
    {
        $uniqueID = $this->getKey(self::KEY_PARENT_COMPONENT);

        if(!empty($uniqueID)) {
            return $this->collections->getByUniqueID($uniqueID);
        }

        return null;
    }
}

<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Types;

use AppUtils\ClassHelper;
use AppUtils\ClassHelper\BaseClassHelperException;

class CelestialBodyType extends BaseComponentType
{
    public const string TYPE_ID = 'celestial-body';

    public function __construct(ClusterType $cluster, string $connectionID, string $componentID)
    {
        parent::__construct($cluster->getCollections(), $connectionID, $componentID);

        $this->setParentComponent($cluster);

        $cluster->addCelestial($this);
    }

    protected function getDefaultData() : array
    {
        return array();
    }

    /**
     * @return ClusterType
     * @throws BaseClassHelperException
     */
    public function getCluster() : ClusterType
    {
        return ClassHelper::requireObjectInstanceOf(
            ClusterType::class,
            $this->getParentComponent()
        );
    }

    public function getTypeID() : string
    {
        return self::TYPE_ID;
    }
}

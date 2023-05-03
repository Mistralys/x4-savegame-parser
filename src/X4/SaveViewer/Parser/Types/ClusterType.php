<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Types;

class ClusterType extends BaseComponentType
{
    public const TYPE_ID = 'cluster';

    public const KEY_SECTORS = 'sectors';
    public const KEY_REGIONS = 'regions';
    public const KEY_CELESTIALS = 'celestials';
    public const KEY_CODE = 'code';
    public const KEY_NAME = 'name';

    protected function getDefaultData() : array
    {
        return array(
            self::KEY_CODE => '',
            self::KEY_NAME => '',
            self::KEY_SECTORS => array(),
            self::KEY_REGIONS => array(),
            self::KEY_CELESTIALS => array()
        );
    }

    public function getTypeID() : string
    {
        return self::TYPE_ID;
    }

    /**
     * @param string $code
     * @return $this
     */
    public function setCode(string $code) : self
    {
        return $this->setKey(self::KEY_CODE, $code);
    }

    /**
     * @param SectorType $sector
     * @return $this
     */
    public function addSector(SectorType $sector) : self
    {
        return $this->setKeyComponent(self::KEY_SECTORS, $sector);
    }

    /**
     * @param RegionType $region
     * @return $this
     */
    public function addRegion(RegionType $region) : self
    {
        return $this->setKeyComponent(self::KEY_REGIONS, $region);
    }

    /**
     * @param CelestialBodyType $celestial
     * @return $this
     */
    public function addCelestial(CelestialBodyType $celestial) : self
    {
        return $this->setKeyComponent(self::KEY_CELESTIALS, $celestial);
    }
}

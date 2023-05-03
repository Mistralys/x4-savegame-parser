<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Types;

use Mistralys\X4\SaveViewer\Parser\Traits\PersonContainerInterface;

class PersonType extends BaseComponentType
{
    public const ROLE_CAPTAIN = 'captain';
    public const ROLE_SERVICE = 'service';
    public const ROLE_MARINE = 'marine';

    public const TYPE_ID = 'person';
    public const KEY_ROLE = 'role';
    public const KEY_SEED = 'seed';
    public const KEY_CODE = 'code';
    public const KEY_OWNER = 'owner';
    public const KEY_COVER = 'cover';
    public const KEY_RACE = 'race';
    public const KEY_GENDER = 'gender';
    public const KEY_MACRO = 'macro';
    public const KEY_NAME = 'name';
    public const KEY_IS_ANONYMOUS = 'anonymous';

    public function __construct(PersonContainerInterface $container, int $number, string $name='')
    {
        parent::__construct($container->getCollections(), 'person', 'P'.$number);

        $this->setParentComponent($container);
    }

    protected function getDefaultData() : array
    {
        return array(
            self::KEY_CODE => '',
            self::KEY_NAME => '',
            self::KEY_RACE => '',
            self::KEY_GENDER => '',
            self::KEY_OWNER => '',
            self::KEY_COVER => '',
            self::KEY_ROLE => '',
            self::KEY_MACRO => '',
            self::KEY_SEED => '',
            self::KEY_IS_ANONYMOUS => true
        );
    }

    public function getTypeID() : string
    {
        return self::TYPE_ID;
    }

    /**
     * @param string $role
     * @return $this
     */
    public function setRole(string $role) : self
    {
        return $this->setKey(self::KEY_ROLE, $role);
    }

    /**
     * @param string $seed
     * @return $this
     */
    public function setSeed(string $seed) : self
    {
        return $this->setKey(self::KEY_SEED, $seed);
    }

    /**
     * @param string $skill
     * @param int $level
     * @return $this
     */
    public function setSkillLevel(string $skill, int $level) : self
    {
        return $this->setKey('skill-'.$skill, $level);
    }

    public function setCode(string $code) : self
    {
        return $this->setKey(self::KEY_CODE, $code);
    }

    /**
     * @param string $raceID
     * @return $this
     */
    public function setOwner(string $raceID) : self
    {
        return $this->setKey(self::KEY_OWNER, $raceID);
    }

    /**
     * @param string $raceID
     * @return $this
     */
    public function setCover(string $raceID) : self
    {
        return $this->setKey(self::KEY_COVER, $raceID);
    }

    /**
     * @param string $macro
     * @return $this
     */
    public function setMacro(string $macro) : self
    {
        $parts = explode('_', $macro);
        $this->setKey(self::KEY_RACE, $parts[1]);

        if(in_array('female', $parts, true)) {
            $this->setKey(self::KEY_GENDER, 'female');
        } else if(in_array('male', $parts, true)) {
            $this->setKey(self::KEY_GENDER, 'male');
        }

        return $this->setKey(self::KEY_MACRO, $macro);
    }

    public function getName() : string
    {
        return $this->getString(self::KEY_NAME);
    }

    public function isAnonymous() : bool
    {
        return $this->getName() === '';
    }

    public function toArray() : array
    {
        // Store the anonymous flag for searches
        $this->setKey(self::KEY_IS_ANONYMOUS, $this->isAnonymous());

        return parent::toArray();
    }
}

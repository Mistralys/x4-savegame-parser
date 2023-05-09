<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader;

use AppUtils\BaseException;
use Mistralys\X4\SaveViewer\Data\BaseSaveFile;
use Mistralys\X4\SaveViewer\Parser\Tags\Tag\FactionsTag;

class Faction
{
    const ERROR_FACTION_RELATION_DOES_NOT_EXIST = 84501;

    private string $name;

    /**
     * @var array<string,mixed>
     */
    private array $data;

    private Factions $factions;

    private string $label;

    private string $ticker;

    private bool $major;

    public function __construct(Factions $factions, string $name, array $data)
    {
        $this->factions = $factions;
        $this->name = $name;
        $this->label = FactionDefs::getLabel($name);
        $this->ticker = FactionDefs::getTicker($name);
        $this->major = FactionDefs::isMajor($name);
        $this->data = $data;
    }

    /**
     * @return Factions
     */
    public function getFactions(): Factions
    {
        return $this->factions;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel() : string
    {
        return $this->label;
    }

    public function getTicker() : string
    {
        return $this->ticker;
    }

    public function isActive() : bool
    {
        return $this->data[FactionsTag::KEY_ACTIVE] === true;
    }

    public function getPlayerDiscount() : float
    {
        if(isset($this->data[FactionsTag::KEY_BOOSTERS]) && isset($this->data[FactionsTag::KEY_BOOSTERS]['player'])) {
            return floatval($this->data[FactionsTag::KEY_BOOSTERS]['player'][FactionsTag::KEY_BOOSTER_AMOUNT]);
        }

        return 0;
    }

    public function areRelationsLocked() : bool
    {
        return $this->data[FactionsTag::KEY_RELATIONS_LOCKED] === true;
    }

    /**
     * @return FactionRelation[]
     */
    public function getRelations() : array
    {
        $result = array();

        if(is_array($this->data[FactionsTag::KEY_RELATIONS]))
        {
            foreach ($this->data[FactionsTag::KEY_RELATIONS] as $factionName => $relation)
            {
                $result[] = new FactionRelation(
                    $this,
                    $factionName,
                    floatval($relation),
                    $this->getBoosterWith($factionName)
                );
            }
        }

        return $result;
    }

    public function isMajor() : bool
    {
        return $this->major;
    }

    private function getBoosterWith(string $name) : float
    {
        if(isset($this->data[FactionsTag::KEY_BOOSTERS]) && isset($this->data[FactionsTag::KEY_BOOSTERS][$name])) {
            return floatval($this->data[FactionsTag::KEY_BOOSTERS][$name][FactionsTag::KEY_BOOSTER_AMOUNT]);
        }

        return 0;
    }

    public function getPlayerRelation() : ?FactionRelation
    {
        $player = $this->factions->getPlayerFaction();

        if($this->hasRelationWith($player)) {
            return $this->getRelationWith($player);
        }

        return null;
    }

    public function hasRelationWith(Faction $faction) : bool
    {
        $relations = $this->getRelations();
        $name = $faction->getName();

        foreach($relations as $relation)
        {
            if($relation->getTargetFaction()->getName() === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Faction $faction
     * @return FactionRelation
     * @throws BaseException
     * @see Faction::ERROR_FACTION_RELATION_DOES_NOT_EXIST
     */
    public function getRelationWith(Faction $faction) : FactionRelation
    {
        $relations = $this->getRelations();
        $name = $faction->getName();

        foreach($relations as $relation)
        {
            if($relation->getTargetFaction()->getName() === $name) {
                return $relation;
            }
        }

        throw new BaseException(
            'Faction has no such relation.',
            sprintf('The faction [%s] has no relation with [%s].', $this->getName(), $faction->getName()),
            self::ERROR_FACTION_RELATION_DOES_NOT_EXIST
        );
    }

    public function getURLDetails(BaseSaveFile $file) : string
    {
        return '?page=ViewSave&amp;saveName='.$file->getName().'&view=faction-relations&amp;faction='.$this->getName();
    }
}

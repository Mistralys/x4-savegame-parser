<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader;

class FactionRelation
{
    private Faction $faction;
    private string $targetName;
    private float $value;
    private float $booster;

    public function __construct(Faction $faction, string $targetFactionName, float $value, float $booster)
    {
        $this->faction = $faction;
        $this->targetName = $targetFactionName;
        $this->value = $value;
        $this->booster = $booster;
    }

    /**
     * @return float
     */
    public function getValue(): float
    {
        return $this->value;
    }

    public function getBoosterValue() : float
    {
        if($this->booster !== 0.0) {
            return $this->booster;
        }

        return $this->getValue();
    }

    /**
     * @return float
     */
    public function getBooster(): float
    {
        return $this->booster;
    }

    public function hasBooster() : bool
    {
        return $this->booster !== 0.0;
    }

    /**
     * @return Faction
     */
    public function getFaction(): Faction
    {
        return $this->faction;
    }

    public function getTargetFaction() : Faction
    {
        return $this->faction->getFactions()->getByName($this->targetName);
    }

    public function getState(bool $withBooster=false) : string
    {
        $value = $this->getValue();
        if($withBooster) {
            $value = $this->getBoosterValue();
        }
        
        if($value >= 1) {
            return 'Ally';
        }

        if($value >= 0.1) {
            return 'Member';
        }

        if($value >= 0.01) {
            return 'Friend';
        }

        if($value >= -0.01) {
            return 'Neutral';
        }

        if($value >= -0.1) {
            return 'Kill military';
        }

        if($value > -1) {
            return 'Kill on sight';
        }

        return 'Nemesis';
    }
}

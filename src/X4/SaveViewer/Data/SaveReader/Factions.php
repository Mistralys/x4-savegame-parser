<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader;

use AppUtils\BaseException;
use Mistralys\X4\SaveViewer\Data\SaveFile;
use Mistralys\X4\SaveViewer\Parser\Tags\Tag\FactionsTag;

class Factions extends Info
{
    const ERROR_FACTION_NAME_DOES_NOT_EXIST = 84601;

    const FACTION_PLAYER = 'player';
    const FACTION_ALLIANCE_OF_THE_WORD = 'alliance';
    const FACTION_ANTIGONE_REPUBLIC = 'antigone';
    const FACTION_ARGON_FEDERATION = 'argon';
    const FACTION_DUKES_BUCCANEERS = 'buccaneers';
    const FACTION_CIVILIAN = 'civilian';
    const FACTION_COURT_OF_CURBS = 'court';
    const FACTION_CRIMINAL = 'criminal';
    const FACTION_FALLEN_FAMILIES = 'fallensplit';
    const FACTION_FREE_FAMILIES = 'freesplit';
    const FACTION_HATIKVAH_FREE_LEAGUE = 'hatikvah';
    const FACTION_HOLY_ORDER_PONTIFEX = 'holyorder';
    const FACTION_HOLY_ORDER_FANATIC = 'holyorderfanatic';
    const FACTION_KHAAK = 'khaak';
    const FACTION_MINISTRY_OF_FINANCE = 'ministry';
    const FACTION_OWNERLESS = 'ownerless';
    const FACTION_GODREALM_PARANID = 'paranid';
    const FACTION_SEGARIS_PIONEERS = 'pioneers';
    const FACTION_SCALE_PLATE_PACT = 'scaleplate';
    const FACTION_SMUGGLER = 'smuggler';
    const FACTION_ZYARTH_PATRIARCHY = 'split';
    const FACTION_TELADI_COMPANY = 'teladi';
    const FACTION_TERRAN_PROTECTORATE = 'terran';
    const FACTION_TRINITY = 'trinity';
    const FACTION_XENON = 'xenon';
    const FACTION_YAKI = 'yaki';

    /**
     * @var Faction[]
     */
    private array $factions = array();

    public function getURLList(SaveFile $save) : string
    {
        $data = array(
            'page' => 'ViewSave',
            'saveName' => $save->getName(),
            'view' => 'factions'
        );

        return '?'.http_build_query($data);
    }

    protected function getAutoDataName(): string
    {
        return FactionsTag::SAVE_NAME;
    }

    protected function init(): void
    {
        foreach($this->data as $factionDef)
        {
            $factionID = (string)$factionDef[FactionsTag::KEY_FACTION_ID];

            if(!FactionDefs::exists($factionID))
            {
                continue;
            }

            $this->factions[] = new Faction(
                $this,
                $factionID,
                $factionDef
            );
        }

        usort($this->factions, function(Faction $a, Faction $b) {
            return strnatcasecmp($a->getName(), $b->getName());
        });
    }

    /**
     * Fetches a faction by its name, e.g. "xenon".
     *
     * @param string $name
     * @return Faction
     * @throws BaseException If no faction could be found by that name.
     * @see Factions::ERROR_FACTION_NAME_DOES_NOT_EXIST
     */
    public function getByName(string $name) : Faction
    {
        foreach ($this->factions as $faction) {
            if($faction->getName() === $name) {
                return $faction;
            }
        }

        throw new BaseException(
            'Unknown faction',
            sprintf('The faction [%s] does not exist in the savegame.', $name),
            self::ERROR_FACTION_NAME_DOES_NOT_EXIST
        );
    }

    public function nameExists(string $name) : bool
    {
        foreach ($this->factions as $faction) {
            if($faction->getName() === $name) {
                return true;
            }
        }

        return false;
    }

    public function getPlayerFaction() : Faction
    {
        return $this->getByName(self::FACTION_PLAYER);
    }

    public function getAll() : array
    {
        return $this->factions;
    }
}

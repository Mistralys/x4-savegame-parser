<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader;

use AppUtils\BaseException;
use Mistralys\X4\SaveViewer\Data\BaseSaveFile;
use Mistralys\X4\SaveViewer\Parser\Tags\Tag\FactionsTag;

class Factions extends Info
{
    public const int ERROR_FACTION_NAME_DOES_NOT_EXIST = 84601;

    public const string FACTION_PLAYER = 'player';
    public const string FACTION_ALLIANCE_OF_THE_WORD = 'alliance';
    public const string FACTION_ANTIGONE_REPUBLIC = 'antigone';
    public const string FACTION_ARGON_FEDERATION = 'argon';
    public const string FACTION_DUKES_BUCCANEERS = 'buccaneers';
    public const string FACTION_CIVILIAN = 'civilian';
    public const string FACTION_COURT_OF_CURBS = 'court';
    public const string FACTION_CRIMINAL = 'criminal';
    public const string FACTION_FALLEN_FAMILIES = 'fallensplit';
    public const string FACTION_FREE_FAMILIES = 'freesplit';
    public const string FACTION_HATIKVAH_FREE_LEAGUE = 'hatikvah';
    public const string FACTION_HOLY_ORDER_PONTIFEX = 'holyorder';
    public const string FACTION_HOLY_ORDER_FANATIC = 'holyorderfanatic';
    public const string FACTION_KHAAK = 'khaak';
    public const string FACTION_MINISTRY_OF_FINANCE = 'ministry';
    public const string FACTION_OWNERLESS = 'ownerless';
    public const string FACTION_GODREALM_PARANID = 'paranid';
    public const string FACTION_SEGARIS_PIONEERS = 'pioneers';
    public const string FACTION_SCALE_PLATE_PACT = 'scaleplate';
    public const string FACTION_SMUGGLER = 'smuggler';
    public const string FACTION_ZYARTH_PATRIARCHY = 'split';
    public const string FACTION_TELADI_COMPANY = 'teladi';
    public const string FACTION_TERRAN_PROTECTORATE = 'terran';
    public const string FACTION_TRINITY = 'trinity';
    public const string FACTION_XENON = 'xenon';
    public const string FACTION_YAKI = 'yaki';

    /**
     * @var Faction[]
     */
    private array $factions = array();

    public function getURLList(BaseSaveFile $save) : string
    {
        $data = array(
            'page' => 'ViewSave',
            BaseSaveFile::PARAM_SAVE_ID => $save->getSaveID(),
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

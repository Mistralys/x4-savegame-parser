<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader;

use AppUtils\BaseException;

class FactionDefs
{
    const ERROR_FACTION_DOES_NOT_EXIST = 85001;

    private static $defs = array(
        Factions::FACTION_PLAYER => array(
            'label' => 'Player',
            'major' => 'yes',
            'ticker' => 'PLA'
        ),
        Factions::FACTION_ANTIGONE_REPUBLIC => array(
            'label' => 'Antigone Republic',
            'major' => 'yes',
            'ticker' => 'ANT'
        ),
        Factions::FACTION_ALLIANCE_OF_THE_WORD => array(
            'label' => 'Alliance of the Word',
            'major' => 'yes',
            'ticker' => 'ALI'
        ),
        Factions::FACTION_ARGON_FEDERATION => array(
            'label' => 'Argon Federation',
            'major' => 'yes',
            'ticker' => 'ARG'
        ),
        Factions::FACTION_DUKES_BUCCANEERS => array(
            'label' => 'Duke\'s Buccaneers',
            'major' => 'yes',
            'ticker' => 'BUC'
        ),
        Factions::FACTION_CIVILIAN => array(
            'label' => 'Civilians',
            'major' => 'no',
            'ticker' => ''
        ),
        Factions::FACTION_COURT_OF_CURBS => array(
            'label' => 'Court of Curbs',
            'major' => 'yes',
            'ticker' => 'CUB'
        ),
        Factions::FACTION_CRIMINAL => array(
            'label' => 'Criminals',
            'major' => 'no',
            'ticker' => ''
        ),
        Factions::FACTION_FALLEN_FAMILIES => array(
            'label' => 'Fallen Families',
            'major' => 'yes',
            'ticker' => 'FAF'
        ),
        Factions::FACTION_FREE_FAMILIES => array(
            'label' => 'Free Families',
            'major' => 'yes',
            'ticker' => 'FRF'
        ),
        Factions::FACTION_HATIKVAH_FREE_LEAGUE => array(
            'label' => 'Hatkivah Free League',
            'major' => 'yes',
            'ticker' => 'HAT'
        ),
        Factions::FACTION_HOLY_ORDER_PONTIFEX => array(
            'label' => 'Holy Order of the Pontifex',
            'major' => 'yes',
            'ticker' => 'HOP'
        ),
        Factions::FACTION_HOLY_ORDER_FANATIC => array(
            'label' => 'Holy Order fanatics',
            'major' => 'no',
            'ticker' => ''
        ),
        Factions::FACTION_KHAAK => array(
            'label' => 'Kha\'ak',
            'major' => 'yes',
            'ticker' => 'KHK'
        ),
        Factions::FACTION_MINISTRY_OF_FINANCE => array(
            'label' => 'Ministry of Finance',
            'major' => 'yes',
            'ticker' => 'MIN'
        ),
        Factions::FACTION_OWNERLESS => array(
            'label' => 'Ownerless',
            'major' => 'no',
            'ticker' => ''
        ),
        Factions::FACTION_GODREALM_PARANID => array(
            'label' => 'Godrealm of the Paranid',
            'major' => 'yes',
            'ticker' => 'PAR'
        ),
        Factions::FACTION_SEGARIS_PIONEERS => array(
            'label' => 'Segaris Pioneers',
            'major' => 'yes',
            'ticker' => 'PIO'
        ),
        Factions::FACTION_SCALE_PLATE_PACT => array(
            'label' => 'Scale Plate Pact',
            'major' => 'yes',
            'ticker' => 'SCA'
        ),
        Factions::FACTION_SMUGGLER => array(
            'label' => 'Smugglers',
            'major' => 'no',
            'ticker' => ''
        ),
        Factions::FACTION_ZYARTH_PATRIARCHY => array(
            'label' => 'Zyarth Patriarchy',
            'major' => 'yes',
            'ticker' => 'ZYA'
        ),
        Factions::FACTION_TELADI_COMPANY => array(
            'label' => 'Teladi Company',
            'major' => 'yes',
            'ticker' => 'TEL'
        ),
        Factions::FACTION_TERRAN_PROTECTORATE => array(
            'label' => 'Terran Protectorate',
            'major' => 'yes',
            'ticker' => 'TER'
        ),
        Factions::FACTION_TRINITY => array(
            'label' => 'Trinity',
            'major' => 'no',
            'ticker' => ''
        ),
        Factions::FACTION_XENON => array(
            'label' => 'Xenon',
            'major' => 'yes',
            'ticker' => 'XEN'
        ),
        Factions::FACTION_YAKI => array(
            'label' => 'Yaki',
            'major' => 'yes',
            'ticker' => 'YAK'
        )
    );

    public static function exists(string $factionName) : bool
    {
        return isset(self::$defs[$factionName]);
    }

    public static function getLabel(string $factionName) : string
    {
        return self::getKey($factionName, 'label');
    }

    public static function getTicker(string $factionName) : string
    {
        return self::getKey($factionName, 'ticker');
    }

    public static function isMajor(string $factionName) : bool
    {
        return self::getKey($factionName, 'major') === 'yes';
    }

    protected static function getKey(string $factionName, string $key) : string
    {
        if(isset(self::$defs[$factionName])) {
            return self::$defs[$factionName][$key];
        }

        throw new BaseException(
            sprintf('Unknown faction [%s]', $factionName),
            sprintf('No definition found for faction [%s].', $factionName),
            self::ERROR_FACTION_DOES_NOT_EXIST
        );
    }
}

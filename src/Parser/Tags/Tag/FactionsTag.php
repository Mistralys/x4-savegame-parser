<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\SaveParser\Tags\Tag;

use Mistralys\X4Saves\SaveParser\Tags\Tag;

class FactionsTag extends Tag
{
    const SAVE_NAME = 'factions';

    const KEY_FACTION_ID = 'id';
    const KEY_ACTIVE = 'active';
    const KEY_RELATIONS_LOCKED = 'relationsLocked';
    const KEY_RELATIONS = 'relations';
    const KEY_MOODS = 'moods';
    const KEY_BOOSTERS = 'boosters';
    const KEY_LICENCES = 'licences';
    const KEY_BOOSTER_AMOUNT = 'amount';
    const KEY_BOOSTER_TIME = 'time';

    private array $factions = array();

    private array $activeFaction = array();

    public function getTagPath() : string
    {
        return 'universe.factions';
    }

    public function getSaveName() : string
    {
        return self::SAVE_NAME;
    }

    public function childTagOpened(string $activePath, string $tagName, string $line, int $number) : void
    {
        $method = 'open_'.str_replace('.', '_', $activePath);
        
        if(method_exists($this, $method))
        {
            $this->$method($line, $number);
        }
    }

    protected function open_faction(string $line, int $number) : void
    {
        $atts = $this->getAttributes($line);
        $active = true;
        if (isset($atts['active']) && $atts['active'] === '0')
        {
            $active = false;
        }

        $this->log(sprintf('Found faction [%s].', $atts['id']));

        $this->activeFaction = array(
            self::KEY_FACTION_ID => $atts['id'],
            self::KEY_ACTIVE => $active,
            self::KEY_RELATIONS_LOCKED => false,
            self::KEY_RELATIONS => array(),
            self::KEY_MOODS => array(),
            self::KEY_BOOSTERS => array(),
            self::KEY_LICENCES => array()
        );
    }

    protected function open_faction_relations_relation(string $line, int $number) : void
    {
        $atts = $this->getAttributes($line);
        $this->activeFaction[self::KEY_RELATIONS][$atts['faction']] = $atts['relation'];
    }

    protected function open_faction_relations(string $line, int $number) : void
    {
        $atts = $this->getAttributes($line);
        if (isset($atts['locked']) && $atts['locked'] === '1')
        {
            $this->activeFaction[self::KEY_RELATIONS_LOCKED] = true;
        }
    }

    protected function open_faction_moods_mood(string $line, int $number) : void
    {
        $atts = $this->getAttributes($line);
        $this->activeFaction[self::KEY_MOODS][$atts['type']] = $atts['level'];
    }

    protected function open_faction_discounts_booster(string $line, int $number) : void
    {
        $atts = $this->getAttributes($line);
        $this->activeFaction[self::KEY_BOOSTERS][$atts['faction']] = array(
            self::KEY_BOOSTER_AMOUNT => $atts['amount'],
            self::KEY_BOOSTER_TIME => $atts['time']
        );
    }

    protected function open_faction_licences_licence(string $line, int $number) : void
    {
        $atts = $this->getAttributes($line);
        $this->activeFaction[self::KEY_LICENCES][$atts['type']] = $atts['factions'];
    }

    protected function close_faction(int $number) : void
    {
        if(!empty($this->activeFaction)) {
            $this->factions[] = $this->activeFaction;
            $this->log(sprintf('Added faction [%s].', $this->activeFaction[self::KEY_FACTION_ID]));
        }

        $this->activeFaction = array();
    }

    protected function open(string $line, int $number) : void
    {
    }

    protected function close(int $number) : void
    {
    }

    protected function getSaveData() : array
    {
        echo '<pre style="color:#444;font-family:monospace;font-size:14px;background:#f0f0f0;border-radius:5px;border:solid 1px #333;padding:16px;margin:12px 0;">';
        print_r($this->factions);
        echo '</pre>';
        
        return $this->factions;
    }

    protected function clear() : void
    {
        $this->factions = array();
    }
}

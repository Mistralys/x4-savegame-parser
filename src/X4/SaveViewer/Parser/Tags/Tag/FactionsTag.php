<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Tags\Tag;

use Mistralys\X4\SaveViewer\Parser\Tags\Tag;

class FactionsTag extends Tag
{
    public const string SAVE_NAME = 'factions';

    public const string KEY_FACTION_ID = 'id';
    public const string KEY_ACTIVE = 'active';
    public const string KEY_RELATIONS_LOCKED = 'relationsLocked';
    public const string KEY_RELATIONS = 'relations';
    public const string KEY_MOODS = 'moods';
    public const string KEY_BOOSTERS = 'boosters';
    public const string KEY_LICENCES = 'licences';
    public const string KEY_BOOSTER_AMOUNT = 'amount';
    public const string KEY_BOOSTER_TIME = 'time';

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

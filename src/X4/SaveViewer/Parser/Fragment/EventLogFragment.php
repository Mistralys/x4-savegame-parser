<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Fragment;

use DOMDocument;
use DOMElement;
use Mistralys\X4\SaveViewer\Parser\BaseDOMFragment;
use Mistralys\X4\SaveViewer\Parser\Collections\EventLogCollection;

class EventLogFragment extends BaseDOMFragment
{
    public const TAG_PATH = 'savegame.log';
    public const SAVE_NAME = 'event-log';

    /**
     * @var array<int,array<string,string|number|bool|NULL>>
     */
    private array $entries = array();
    private EventLogCollection $log;

    protected function parseDOM(DOMDocument $dom) : void
    {
        $el = $this->checkIsElement($dom->firstChild, 'log');

        if($el === null)
        {
            return;
        }

        $this->log = $this->collections->eventLog();

        foreach($el->childNodes as $node)
        {
            $this->parseLogEntry($this->checkIsElement($node));
        }

        $this->saveJSONFragment(self::SAVE_NAME, $this->entries);
    }

    /**
     * Example log entries:
     *
     * Missions
     * <pre>
     * <entry time="1982162.711" category="missions" title="Ocean of Fantasy" text="Terraforming project completed successfully: Import Methane"/>
     * <entry time="2002887.489" category="missions" title="Holy Three-Dimensionality" text="The Holy Order of the Pontifex and the Godrealm of the Paranid have unified under the Realm of the Trinity." faction="Anthea Syrkos"/>
     * </pre>
     *
     * Shipyard/Wharf
     * <pre>
     * <entry time="2254613.595" category="upkeep" title="Ship constructed" text="YAK Raiding Party Moreya (WSX-675) finished construction at station: Aramean HQ (YAQ-544). They have paid the station 391,392 Cr." interact="showonmap" component="[0x87dd0]" faction="{20203,2801}" money="39139272"/>
     * <entry time="2254955.945" category="upkeep" title="Ship repaired" text="ZYA Recon Fighter Jaguar (KDS-765) finished repairing at station: Aramean Wharf YAK (SGW-522). They have paid the station 26,019 Cr." interact="showonmap" component="[0xd602]" faction="{20203,2001}" money="2601947"/>
     * </pre>
     *
     * Accounting
     * <pre>
     * <entry time="2256508.296" category="upkeep" title="The account for #FO RIP Basic Needs in Avarice I has dropped to 92,580,328 Credits." interact="showonmap" component="[0x26e24]"/>
     * </pre>
     *
     * Ship events
     * <pre>
     * <entry time="2254738.332" title="Police Interdiction" text="SM AHO Graphene Forge - Chthonios E (G) OXT-880 in Pious Mists IV[\012]Ordered by Realm of the Trinity police to stop and be inspected.[\012]Response: Comply" faction="{20203,2301}"/>
     * <entry time="2255045.246" title="Pirate Harassment" text="ST~ BOR QMH - Boa BSR-466 in Great Reef[\012]Accosted by Argon Federation pirate ship[\012]ARG Pillager Minotaur Raider KMD-274.[\012]Response: Comply" faction="{20203,201}"/>
     * <entry time="2256619.112" category="upkeep" title="ST~ BOR MLX - Demeter V was forced to flee after being attacked by BOR Pillager Minotaur Raider in Barren Shores. Your ship is at Empty Space in Watchful Gaze." interact="showonmap" component="[0x916e0]"/>
     * <entry time="2194771.779" category="upkeep" title="ST AHO Graphene Forge - Baldric (EMQ-260) was destroyed." text="Location: Pious Mists II[\012]Commander: FO PAR Graphene Forge (AHO-015)" interact="showlocationonmap" component="[0x59445]" x="170173.719" y="16503.322" z="48768.77" highlighted="1"/>
     * <entry time="2213375.47" category="upkeep" title="ST EAC - Plutus V (G) (TKH-408) was destroyed." text="Location: Watchful Gaze[\012]Commander: FO ZYA Advanced Electronics Forge (EAC-540)[\012]Destroyed by: KHK Queen's Guard (TOO-105)" interact="showlocationonmap" component="[0x90ba5]" x="-66550.93" y="-2345.392" z="254310.406" highlighted="1"/>
     * </pre>
     *
     * Player custom alerts
     * <pre>
     * <entry time="2255121.645" category="alerts" title="Khaak have been spotted" text="Location: Grand Exchange I" interact="showonmap" component="[0x621cf0f6]"/>
     * </pre>
     *
     * Miscellaneous
     * <pre>
     * <entry time="2254812.147" title="Reputation gained" text="Reason: Trade Completed[\012]Current reputation: 28" faction="{20203,3301}"/>
     * <entry time="2254879.401" category="news" title="News update: " text="Zyarth Patriarchy mounting defence in Heart of Acrimony II"/>
     * </pre>
     *
     * @param DOMElement|null $node
     * @return void
     */
    private function parseLogEntry(?DOMElement $node) : void
    {
        if($node === null) {
            return;
        }

        $attributes = $this->parseElementAttributes($node);

        $this->log->createLogEntry(
            $attributes['time'] ?? '',
            $attributes['category'] ?? '',
            $attributes['title'] ?? '',
            $attributes['text'] ?? '',
            $attributes['faction'] ?? '',
            $attributes['component'] ?? '',
            $attributes['money'] ?? ''
        );
    }
}

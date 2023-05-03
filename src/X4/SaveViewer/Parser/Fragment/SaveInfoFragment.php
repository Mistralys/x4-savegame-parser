<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Fragment;

use DOMDocument;
use DOMElement;
use DOMNode;
use Mistralys\X4\SaveViewer\Parser\BaseDOMFragment;

class SaveInfoFragment extends BaseDOMFragment
{
    public const SAVE_NAME = 'savegame-info';

    public const KEY_PLAYER_NAME = 'name';
    public const KEY_PLAYER_MONEY = 'money';
    public const KEY_SAVE_NAME = 'saveName';
    public const KEY_SAVE_DATE = 'saveDate';
    public const KEY_GAME_GUID = 'guid';
    public const KEY_GAME_CODE = 'code';
    public const KEY_PLAYER_LOCATION = 'location';

    private array $info = array(
        self::KEY_PLAYER_NAME => '',
        self::KEY_PLAYER_MONEY => '',
        self::KEY_SAVE_NAME => '',
        self::KEY_SAVE_DATE => '',
        self::KEY_GAME_CODE => '',
        self::KEY_GAME_GUID => ''
    );

    protected function parseDOM(DOMDocument $dom) : void
    {
        foreach($dom->firstChild->childNodes as $node)
        {
            $this->parseNode($node);
        }

        $this->saveJSONFragment(self::SAVE_NAME, $this->info);
    }

    private function parseNode(DOMNode $node) : void
    {
        if(!$node instanceof DOMElement)
        {
            return;
        }

        if($node->nodeName === 'save')
        {
            $this->info[self::KEY_SAVE_NAME] = $node->getAttribute('name');
            $this->info[self::KEY_SAVE_DATE] = $node->getAttribute('date');
            return;
        }

        if($node->nodeName === 'game')
        {
            $this->info[self::KEY_GAME_CODE] = $node->getAttribute('code');
            $this->info[self::KEY_GAME_GUID] = $node->getAttribute('guid');
            return;
        }

        if($node->nodeName === 'player')
        {
            $this->info[self::KEY_PLAYER_NAME] = $node->getAttribute('name');
            $this->info[self::KEY_PLAYER_MONEY] = $node->getAttribute('money');
            $this->info[self::KEY_PLAYER_LOCATION] = $node->getAttribute('location');
        }
    }
}

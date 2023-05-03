<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Tags\Tag;

use Mistralys\X4\SaveViewer\Parser\Tags\Tag;

class PlayerComponentTag extends Tag
{
    const SAVE_NAME = 'player';

    const KEY_BLUEPRINTS = 'blueprints';
    const KEY_INVENTORY = 'inventory';

    private array $player = array(
        self::KEY_BLUEPRINTS => array(),
        self::KEY_INVENTORY => array()
    );

    public function getTagPath() : string
    {
        return 'component;class=player';
    }

    public function getSaveName() : string
    {
        return self::SAVE_NAME;
    }

    protected function open_blueprints_blueprint(string $line, int $number) : void
    {
        $atts = $this->getAttributes($line);
        $this->player['blueprints'][] = $atts['ware'];
    }

    protected function open_inventory_ware(string $line, int $number) : void
    {
        $atts = $this->getAttributes($line);
        $amount = 0;
        if(isset($atts['amount'])) {
            $amount = intval($atts['amount']);
        }
        
        $this->player['inventory'][$atts['ware']] = $amount;
    }

    protected function open(string $line, int $number) : void
    {
    }

    protected function close(int $number) : void
    {
    }

    protected function getSaveData() : array
    {
        return $this->player;
    }

    protected function clear() : void
    {
        $this->player = array();
    }
}

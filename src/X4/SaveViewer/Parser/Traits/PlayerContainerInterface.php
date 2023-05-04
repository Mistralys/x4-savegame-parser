<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Traits;

use Mistralys\X4\SaveViewer\Parser\Types\PlayerType;

interface PlayerContainerInterface extends ComponentInterface
{
    public const KEY_NAME_PLAYER = 'player';

    /**
     * @param PlayerType $player
     * @return $this
     */
    public function registerPlayer(PlayerType $player) : self;
}

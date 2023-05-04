<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Traits;

use Mistralys\X4\SaveViewer\Parser\Types\PlayerType;

/**
 * @see PlayerContainerInterface
 */
trait PlayerContainerTrait
{
    public function registerPlayer(PlayerType $player) : self
    {
        return $this->setKey(PlayerContainerInterface::KEY_NAME_PLAYER, $player->getUniqueID());
    }
}

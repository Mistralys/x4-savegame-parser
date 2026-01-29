<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Collections;

use Mistralys\X4\SaveViewer\Parser\Traits\PlayerContainerInterface;
use Mistralys\X4\SaveViewer\Parser\Types\PlayerType;

class PlayerCollection extends BaseCollection
{
    public const string COLLECTION_ID = 'player';

    public function getCollectionID() : string
    {
        return self::COLLECTION_ID;
    }

    public function createPlayer(PlayerContainerInterface $container, string $connectionID, string $componentID) : PlayerType
    {
        $player = new PlayerType($container->getCollections(), $connectionID, $componentID);

        $this->addComponent($player);

        return $player;
    }

    public function loadData() : array
    {
        $data = parent::loadData();

        if(!empty($data[PlayerType::TYPE_ID])) {
            return array_shift($data[PlayerType::TYPE_ID]);
        }

        return array();
    }
}

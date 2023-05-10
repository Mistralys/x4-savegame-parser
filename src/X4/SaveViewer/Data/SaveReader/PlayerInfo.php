<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader;

use AppUtils\FileHelper\JSONFile;
use Mistralys\X4\SaveViewer\Parser\Types\PlayerType;

class PlayerInfo extends Info
{
    protected function init() : void
    {
        $data = JSONFile::factory($this->collections
            ->player()
            ->getFilePath()
        )
            ->parse();

        if(isset($data[PlayerType::TYPE_ID][0])) {
            $this->data = $data[PlayerType::TYPE_ID][0];
        }
    }

    public function getName() : string
    {
        return $this->getString(PlayerType::KEY_NAME);
    }

    public function getCode() : string
    {
        return $this->getString(PlayerType::KEY_CODE);
    }
}

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

    /**
     * Convert PlayerInfo to array suitable for CLI API output.
     *
     * @return array<string,mixed> JSON-serializable array
     */
    public function toArrayForAPI(): array
    {
        return [
            'name' => $this->getName(),
            'code' => $this->getCode(),
            'blueprints' => $this->getArray(PlayerType::KEY_BLUEPRINTS),
            'wares' => $this->getArray(PlayerType::KEY_WARES)
        ];
    }
}

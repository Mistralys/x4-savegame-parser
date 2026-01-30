<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader;

use AppUtils\FileHelper\JSONFile;
use DateTime;
use Mistralys\X4\SaveViewer\Parser\Fragment\SaveInfoFragment;

class SaveInfo extends Info
{
    protected function init() : void
    {
        if(!$this->reader->dataExists(SaveInfoFragment::SAVE_NAME)) {
            return;
        }

        $this->setKeys($this->reader->getRawData(SaveInfoFragment::SAVE_NAME));
    }

    public function getSaveName() : string
    {
        return $this->getString(SaveInfoFragment::KEY_SAVE_NAME);
    }

    public function getSaveDate() : ?DateTime
    {
        return $this->getDateTime(SaveInfoFragment::KEY_SAVE_DATE);
    }

    public function getPlayerName() : string
    {
        return $this->getString(SaveInfoFragment::KEY_PLAYER_NAME);
    }

    public function getMoney() : int
    {
        return $this->getInt(SaveInfoFragment::KEY_PLAYER_MONEY);
    }

    public function getMoneyPretty() : string
    {
        return number_format($this->getMoney(), 0, '.', ' ');
    }

    public function getGameGUID() : string
    {
        return $this->getString(SaveInfoFragment::KEY_GAME_GUID);
    }

    public function getGameCode() : int
    {
        return $this->getInt(SaveInfoFragment::KEY_GAME_CODE);
    }

    public function getGameStartTime() : float
    {
        return $this->getFloat(SaveInfoFragment::KEY_START_TIME);
    }

    public function getExtractionDuration() : ?float
    {
        return $this->reader->getAnalysis()->getExtractionDuration();
    }

    public function getExtractionDurationFormatted() : ?string
    {
        return $this->reader->getAnalysis()->getExtractionDurationFormatted();
    }

    /**
     * Convert SaveInfo to array suitable for CLI API output.
     *
     * @return array<string,mixed> JSON-serializable array
     */
    public function toArrayForAPI(): array
    {
        return [
            'saveName' => $this->getSaveName(),
            'playerName' => $this->getPlayerName(),
            'money' => $this->getMoney(),
            'moneyFormatted' => $this->getMoneyPretty(),
            'saveDate' => $this->getSaveDate()?->format('c'),
            'gameStartTime' => $this->getGameStartTime(),
            'gameGUID' => $this->getGameGUID(),
            'gameCode' => $this->getGameCode(),
            'extractionDuration' => $this->getExtractionDuration(),
            'extractionDurationFormatted' => $this->getExtractionDurationFormatted()
        ];
    }
}

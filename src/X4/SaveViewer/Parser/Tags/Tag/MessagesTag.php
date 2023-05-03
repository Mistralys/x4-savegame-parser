<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Tags\Tag;

use Mistralys\X4\SaveViewer\Parser\Tags\Tag;

class MessagesTag extends Tag
{
    private array $messages = array();

    public function getTagPath() : string
    {
        return 'messages';
    }

    public function getSaveName() : string
    {
        return 'messages';
    }

    protected function open(string $line, int $number) : void
    {
    }

    protected function close(int $number) : void
    {
    }

    protected function open_entry(string $line, int $number) : void
    {
        $this->messages[] = $this->getAttributes($line);
    }

    protected function getSaveData() : array
    {
        return $this->messages;
    }

    protected function clear() : void
    {
        $this->messages = array();
    }
}

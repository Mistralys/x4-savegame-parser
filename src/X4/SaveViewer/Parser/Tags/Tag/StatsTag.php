<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Tags\Tag;

use Mistralys\X4\SaveViewer\Parser\Tags\Tag;

class StatsTag extends Tag
{
    public const string SAVE_NAME = 'stats';

    private array $stats = array();

    public function getTagPath() : string
    {
        return 'stats';
    }

    public function getSaveName() : string
    {
        return self::SAVE_NAME;
    }

    protected function open(string $line, int $number) : void
    {
    }

    protected function close(int $number) : void
    {
    }

    protected function open_stat(string $line, int $number) : void
    {
        $atts = $this->getAttributes($line);
        $this->stats[$atts['id']] = $atts['value'];
    }

    protected function getSaveData() : array
    {
        return $this->stats;
    }

    protected function clear() : void
    {
        $this->stats = array();
    }
}

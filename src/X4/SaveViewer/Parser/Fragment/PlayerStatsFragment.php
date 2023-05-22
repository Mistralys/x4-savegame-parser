<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Fragment;

use DOMDocument;
use Mistralys\X4\SaveViewer\Parser\BaseDOMFragment;

class PlayerStatsFragment extends BaseDOMFragment
{
    public const TAG_PATH = 'savegame.stats';

    protected function parseDOM(DOMDocument $dom) : void
    {
    }
}

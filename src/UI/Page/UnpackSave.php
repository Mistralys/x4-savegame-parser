<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\UI\Pages;

use Mistralys\X4Saves\SaveParser;
use Mistralys\X4Saves\UI\Page;

class UnpackSave extends Page
{
    public function getTitle(): string
    {
        return 'Unpack savegame';
    }

    protected function _render(): void
    {
        $save = $this->requireSave();

        set_time_limit(0);

        $parser = new SaveParser($save->getName());
        $parser->unpack();
        $parser->convert();

        $this->redirect('?');
    }

    public function getNavItems(): array
    {
        return array();
    }
}

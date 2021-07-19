<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\UI\Pages;

use Mistralys\X4Saves\Data\SaveFile;
use Mistralys\X4Saves\SaveParser;
use Mistralys\X4Saves\UI\Page;

class UnpackSave extends Page
{
    const URL_NAME = 'UnpackSave';

    public function getTitle(): string
    {
        return 'Unpack savegame';
    }

    protected function getURLParams() : array
    {
        return array(
            SaveFile::PARAM_SAVE_NAME => $this->requireSave()->getName()
        );
    }

    protected function _render(): void
    {
        $save = $this->requireSave();

        set_time_limit(0);

        $parser = new SaveParser($save->getName());
        $parser->unpack();

        $this->redirect('?');
    }

    public function getNavItems(): array
    {
        return array();
    }
}

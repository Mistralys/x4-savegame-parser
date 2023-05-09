<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages;

use Mistralys\X4\SaveViewer\Data\BaseSaveFile;
use Mistralys\X4\SaveViewer\UI\Page;

class CreateBackup extends Page
{
    const URL_NAME = 'CreateBackup';

    public function getTitle(): string
    {
        return 'Back up savegame';
    }

    protected function getURLParams() : array
    {
        return array(
            BaseSaveFile::PARAM_SAVE_NAME => $this->requireSave()->getName()
        );
    }

    protected function _render(): void
    {
        $save = $this->requireSave();

        set_time_limit(0);

        $save->writeBackup();

        $this->redirect('?');
    }

    public function getNavItems(): array
    {
        return array();
    }
}

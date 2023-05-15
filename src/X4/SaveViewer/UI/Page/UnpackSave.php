<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages;

use Mistralys\X4\SaveViewer\Data\BaseSaveFile;
use Mistralys\X4\SaveViewer\SaveParser;
use Mistralys\X4\SaveViewer\UI\Page;
use function AppLocalize\t;

class UnpackSave extends Page
{
    const URL_NAME = 'UnpackSave';

    public function getTitle(): string
    {
        return 'Unpack savegame';
    }

    public function getSubtitle() : string
    {
        return '';
    }

    public function getAbstract() : string
    {
        return '';
    }

    protected function getURLParams() : array
    {
        return array(
            BaseSaveFile::PARAM_SAVE_ID => $this->requireSave()->getSaveID()
        );
    }

    protected function _render(): void
    {
        $save = $this->requireSave();

        set_time_limit(0);

        $parser = new SaveParser($save->getSaveName());
        $parser->unpack();

        $this->redirect('?');
    }

    public function getNavItems(): array
    {
        return array();
    }

    public function getNavTitle() : string
    {
        return t('Unpack savegame');
    }

    protected function preRender() : void
    {
    }
}

<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages\ViewSave;

use Mistralys\X4\SaveViewer\Data\SaveFile;
use Mistralys\X4\SaveViewer\Data\SaveReader;
use Mistralys\X4\SaveViewer\UI\Pages\ViewSave;

/**
 * @property ViewSave $page
 *
 */
abstract class SubPage extends \Mistralys\X4\SaveViewer\UI\SubPage
{
    protected SaveReader $reader;
    protected SaveFile $save;

    protected function getURLParams() : array
    {
        return array();
    }

    protected function init() : void
    {
        $this->reader = $this->page->getReader();
        $this->save = $this->page->getSave();
    }
}

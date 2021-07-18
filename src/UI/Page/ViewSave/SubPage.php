<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\UI\Pages\ViewSave;

use Mistralys\X4Saves\Data\SaveFile;
use Mistralys\X4Saves\Data\SaveReader;
use Mistralys\X4Saves\UI\Pages\ViewSave;

/**
 * @property ViewSave $page
 *
 */
abstract class SubPage extends \Mistralys\X4Saves\UI\SubPage
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

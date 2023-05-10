<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI;

use Mistralys\X4\SaveViewer\Data\BaseSaveFile;
use Mistralys\X4\SaveViewer\Data\SaveReader;
use Mistralys\X4\UI\BaseSubPage;

abstract class SubPage extends BaseSubPage
{
    public function getReader() : SaveReader
    {
        return $this->page->getReader();
    }

    public function getSave() : BaseSaveFile
    {
        return $this->page->getSave();
    }
}

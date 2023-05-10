<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages\ViewSave;

use Mistralys\X4\SaveViewer\Data\BaseSaveFile;
use Mistralys\X4\SaveViewer\Data\SaveReader;
use Mistralys\X4\SaveViewer\UI\Pages\ViewSave;

/**
 * @property ViewSave $page
 *
 */
abstract class SubPage extends \Mistralys\X4\SaveViewer\UI\SubPage
{
    protected SaveReader $reader;
    protected BaseSaveFile $save;

    protected function getURLParams() : array
    {
        return array();
    }
}

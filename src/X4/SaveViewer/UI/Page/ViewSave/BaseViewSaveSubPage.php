<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages\ViewSave;

use Mistralys\X4\SaveViewer\UI\Pages\ViewSave;
use Mistralys\X4\SaveViewer\UI\RedirectException;
use Mistralys\X4\SaveViewer\UI\ViewerSubPage;

/**
 * @property ViewSave $page
 */
abstract class BaseViewSaveSubPage extends ViewerSubPage
{
    protected function getURLParams() : array
    {
        return array();
    }
}

<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI;

use Exception;

class RedirectException extends Exception
{
    private string $url;

    public function __construct(string $url)
    {
        parent::__construct('Redirect', 0);

        $this->url = $url;
    }

    public function getURL() : string
    {
        return $this->url;
    }
}

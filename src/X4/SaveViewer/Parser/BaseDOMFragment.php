<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser;

use DOMDocument;
use DOMElement;
use DOMNode;

abstract class BaseDOMFragment extends BaseFragment
{
    abstract protected function parseDOM(DOMDocument $dom) : void;

    protected function registerActions() : void
    {
    }

    protected function _processFile() : void
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;

        $dom->load($this->getXMLFile());

        $this->parseDOM($dom);
    }
}

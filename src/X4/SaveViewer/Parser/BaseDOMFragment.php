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

    protected function parseElementAttributes(DOMElement $element) : array
    {
        if(empty($element->attributes)) {
            return array();
        }

        $result = array();
        foreach($element->attributes as $attributeNode)
        {
            $result[$attributeNode->nodeName] = $attributeNode->nodeValue;
        }

        return $result;
    }
}

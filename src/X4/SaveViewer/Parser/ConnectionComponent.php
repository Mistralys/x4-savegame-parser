<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser;

use DOMElement;

class ConnectionComponent
{
    public DOMElement $connectionNode;
    public DOMElement $componentNode;
    public string $connectionID;
    public string $componentID;
    public string $componentClass;

    public function __construct(DOMElement $connectionNode, DOMElement $componentNode)
    {
        $this->componentNode = $componentNode;
        $this->connectionNode = $connectionNode;
        $this->connectionID = $connectionNode->getAttribute('connection');
        $this->componentID = $componentNode->getAttribute('id');
        $this->componentClass = $componentNode->getAttribute('class');
    }

    public function componentAttr(string $name) : string
    {
        return $this->componentNode->getAttribute($name);
    }
}

<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Tags;

abstract class ComplexTag extends Tag
{
    public function __construct()
    {
        parent::__construct();

        // Only look for the partial opening tag, the
        // rest is done by the matching mechanism.
        $this->opening = '<'.$this->getTagPath();
        $this->openingLength = strlen($this->opening);
    }

    public function isMatch(string $line) : bool
    {
        if(!parent::isMatch($line))
        {
            return false;
        }

        return $this->_isMatch($line);
    }

    abstract protected function _isMatch(string $line) : bool;
}

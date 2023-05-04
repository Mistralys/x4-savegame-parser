<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\DataProcessing;

use AppUtils\FileHelper\JSONFile;
use Mistralys\X4\SaveViewer\Parser\Collections;

abstract class BaseDataProcessor
{
    protected DataProcessingHub $hub;
    protected Collections $collections;

    public function __construct(DataProcessingHub $hub)
    {
        $this->hub = $hub;
        $this->collections = $hub->getCollections();
    }

    public function process() : void
    {
        $this->_process();
    }

    abstract protected function _process() : void;

    protected function saveAsJSON(array $data, string $fileID) : JSONFile
    {
        return JSONFile::factory($this->getJSONFilePath($fileID))->putData($data, true);
    }

    public function getJSONFilePath(string $fileID) : string
    {
        return sprintf(
            '%s/data-%s.json',
            $this->collections->getOutputFolder(),
            $fileID
        );
    }
}

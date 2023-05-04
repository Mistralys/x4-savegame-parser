<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser;

use AppUtils\FileHelper\JSONFile;
use Mistralys\X4\SaveViewer\BaseXMLParser;

abstract class BaseFragment extends BaseXMLParser
{
    public function __construct(Collections $collections, string $xmlFile, string $outputPath)
    {
        parent::__construct($collections, $xmlFile, $outputPath);

        $this->processFile();
    }

    protected function saveJSONFragment(string $name, array $data) : void
    {
        $file = sprintf(
            '%s/%s.json',
            $this->collections->getOutputFolder(),
            $name
        );

        JSONFile::factory($file)->putData($data, true);
    }
}

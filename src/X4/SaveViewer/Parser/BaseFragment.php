<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser;

use AppUtils\FileHelper\JSONFile;
use Mistralys\X4\SaveViewer\BaseXMLParser;

abstract class BaseFragment extends BaseXMLParser
{
    public function __construct(Collections $collections, FileAnalysis $analysis, string $xmlFile)
    {
        parent::__construct($collections, $analysis, $xmlFile);

        $this->processFile();
    }

    protected function saveJSONFragment(string $name, array $data) : void
    {
        $file = sprintf(
            '%s/%s.json',
            $this->collections->getOutputFolder()->getPath(),
            $name
        );

        JSONFile::factory($file)->putData($data, true);
    }
}

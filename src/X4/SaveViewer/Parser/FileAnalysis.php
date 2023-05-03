<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser;

use AppUtils\FileHelper\JSONFile;
use AppUtils\Microtime;

class FileAnalysis
{
    public const KEY_PROCESS_DATE = 'process-dates';

    /**
     * @var array<string,FileAnalysis>
     */
    private static array $files = array();
    private JSONFile $file;
    private string $outputFolder;

    /**
     * @var array<string,array<string,string>>
     */
    private array $data = array(
        self::KEY_PROCESS_DATE => array(),
    );

    private function __construct(string $outputFolder)
    {
        $this->outputFolder = $outputFolder;
        $this->file = JSONFile::factory($outputFolder.'/analysis.json');

        if($this->file->exists()) {
            $this->data = $this->file->parse();
        }
    }

    public static function create(string $outputFolder) : FileAnalysis
    {
        if(!isset(self::$files[$outputFolder])) {
            self::$files[$outputFolder] = new FileAnalysis($outputFolder);
        }

        return self::$files[$outputFolder];
    }

    public function exists() : bool
    {
        return $this->file->exists();
    }

    public function save() : self
    {
        $this->file->putData($this->data, true);
        return $this;
    }

    public function setProcessDate(string $file, Microtime $time) : self
    {
        $this->data[self::KEY_PROCESS_DATE][$file] = $time->getISODate();
        $this->save();
        return $this;
    }

    public function hasProcessDate(string $file) : bool
    {
        return isset($this->data[self::KEY_PROCESS_DATE][$file]);
    }
}

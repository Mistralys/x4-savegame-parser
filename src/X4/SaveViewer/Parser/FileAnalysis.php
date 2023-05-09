<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser;

use AppUtils\ArrayDataCollection;
use AppUtils\ConvertHelper;
use AppUtils\FileHelper\JSONFile;
use AppUtils\Microtime;
use DateTime;
use Mistralys\X4\SaveViewer\Parser\SaveSelector\SaveGameFile;
use Mistralys\X4\SaveViewer\SaveViewerException;

class FileAnalysis extends ArrayDataCollection
{
    public const ERROR_CANNOT_GET_MODIFIED_DATE = 66756002;

    public const KEY_PROCESS_DATE = 'process-dates';
    public const KEY_SAVE_DATE = 'save-date';
    public const KEY_SAVE_ID = 'save-id';

    /**
     * @var array<string,FileAnalysis>
     */
    private static array $files = array();
    private JSONFile $storageFile;
    private SaveGameFile $saveFile;

    private function __construct(SaveGameFile $file)
    {
        $this->saveFile = $file;
        $this->storageFile = JSONFile::factory($file->getStorageFolder().'/analysis.json');

        parent::__construct();

        if($this->storageFile->exists()) {
            $this->setKeys($this->storageFile->parse());
        }
    }

    public static function createAnalysis(SaveGameFile $file) : FileAnalysis
    {
        return new FileAnalysis($file);
    }

    public function exists() : bool
    {
        return $this->storageFile->exists();
    }

    public function save() : self
    {
        $this->storageFile->putData($this->data, true);
        return $this;
    }

    public function setProcessDate(string $file, Microtime $time) : self
    {
        $dates = $this->getArray(self::KEY_PROCESS_DATE);
        $dates[$file] = $time->getISODate();
        $this->setKey(self::KEY_PROCESS_DATE, $dates);

        $this->save();

        return $this;
    }

    public function hasProcessDate(string $file) : bool
    {
        $dates = $this->getArray(self::KEY_PROCESS_DATE);
        return isset($dates[$file]);
    }

    public function registerSave() : self
    {
        if($this->hasSaveID()) {
            return $this;
        }

        $this->setKey(self::KEY_SAVE_DATE, $this->getReferenceDateModified()->format('Y-m-d H:i:s'));
        $this->setKey(self::KEY_SAVE_ID, $this->getReferenceSaveID());

        return $this->save();
    }

    public function getSaveID() : string
    {
        if($this->hasSaveID())
        {
            return $this->getString(self::KEY_SAVE_ID);
        }

        return $this->getReferenceSaveID();
    }

    private function getReferenceSaveID() : string
    {
        return ConvertHelper::string2shortHash(sprintf(
            'X4Save-%s-%s',
            $this->getReferenceDateModified()->getTimestamp(),
            $this->saveFile->getBaseName()
        ));
    }

    public function getDateModified() : DateTime
    {
        if($this->hasSaveID())
        {
            return $this->getDateTime(self::KEY_SAVE_DATE);
        }

        return $this->getReferenceDateModified();
    }

    private function getReferenceDateModified() : DateTime
    {
        $date = $this->saveFile->getReferenceFile()->getModifiedDate();
        if($date !== null) {
            return $date;
        }

        throw new SaveViewerException(
            'Cannot get modified date from the savegame file.',
            sprintf(
                'Affected file: [%s].',
                $this->saveFile->getReferenceFile()->getPath()
            ),
            self::ERROR_CANNOT_GET_MODIFIED_DATE
        );
    }

    public function hasSaveID() : bool
    {
        return $this->exists() && !empty($this->getKey(self::KEY_SAVE_ID));
    }
}

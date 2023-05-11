<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser;

use AppUtils\ArrayDataCollection;
use AppUtils\ConvertHelper;
use AppUtils\FileHelper\FileInfo;
use AppUtils\FileHelper\FolderInfo;
use AppUtils\FileHelper\JSONFile;
use AppUtils\FileHelper_Exception;
use AppUtils\Microtime;
use DateTime;
use Mistralys\X4\SaveViewer\Parser\SaveSelector\SaveGameFile;
use Mistralys\X4\SaveViewer\SaveViewerException;

class FileAnalysis extends ArrayDataCollection
{
    public const ERROR_CANNOT_GET_MODIFIED_DATE = 66756002;

    public const ANALYSIS_FILE_NAME = 'analysis.json';
    public const BACKUP_ARCHIVE_FILE_NAME = 'backup.gz';

    public const KEY_PROCESS_DATE = 'process-dates';
    public const KEY_SAVE_DATE = 'save-date';
    public const KEY_SAVE_ID = 'save-id';
    public const KEY_SAVE_NAME = 'save-name';

    private JSONFile $storageFile;
    private string $saveName;
    private DateTime $modifiedDate;
    private FolderInfo $storageFolder;

    private function __construct(FolderInfo $storageFolder, DateTime $modifiedDate, string $saveName)
    {
        $this->storageFolder = $storageFolder;
        $this->storageFile = JSONFile::factory($storageFolder->getPath().'/'.self::ANALYSIS_FILE_NAME);
        $this->modifiedDate = $modifiedDate;
        $this->saveName = $saveName;

        parent::__construct();

        if($this->storageFile->exists()) {
            $this->setKeys($this->storageFile->parse());
        }
    }

    public function getStorageFolder() : FolderInfo
    {
        return $this->storageFolder;
    }

    public function getStorageFile() : JSONFile
    {
        return $this->storageFile;
    }

    public static function createFromSaveFile(SaveGameFile $file) : FileAnalysis
    {
        return new FileAnalysis(
            $file->getStorageFolder(),
            $file->getReferenceFile()->getModifiedDate(),
            $file->getBaseName()
        );
    }

    /**
     * @param string|FolderInfo $analysisFile
     * @return FileAnalysis
     * @throws FileHelper_Exception
     */
    public static function createFromDataFile($analysisFile) : FileAnalysis
    {
        $file = FileInfo::factory($analysisFile);
        $folder = FolderInfo::factory($file->getFolderPath());

        $parts = explode('-', $folder->getName());
        array_shift($parts); // remove "unpack"
        $date = DateTime::createFromFormat('YmdHis', array_shift($parts));
        $baseName = array_shift($parts);

        return new FileAnalysis(
            $folder,
            $date,
            $baseName
        );
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

        $this->setKey(self::KEY_SAVE_NAME, $this->saveName);
        $this->setKey(self::KEY_SAVE_DATE, $this->getReferenceDateModified()->format('Y-m-d H:i:s'));
        $this->setKey(self::KEY_SAVE_ID, $this->getSaveID());

        return $this->save();
    }

    public function getSaveID() : string
    {
        return ConvertHelper::string2shortHash(sprintf(
            'X4Save-%s-%s',
            $this->modifiedDate->getTimestamp(),
            $this->saveName
        ));
    }

    /**
     * @return string The name without extension, e.g. <code>quicksave</code>
     */
    public function getSaveName() : string
    {
        return $this->saveName;
    }

    private function getReferenceSaveID() : string
    {
        return ConvertHelper::string2shortHash(sprintf(
            'X4Save-%s-%s',
            $this->modifiedDate->getTimestamp(),
            $this->saveName
        ));
    }

    public function getBackupFile() : FileInfo
    {
        return FileInfo::factory(sprintf(
            '%s/%s',
            $this->getStorageFolder(),
            self::BACKUP_ARCHIVE_FILE_NAME
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
        return $this->modifiedDate;
    }

    public function hasSaveID() : bool
    {
        return $this->exists() && !empty($this->getKey(self::KEY_SAVE_ID));
    }
}

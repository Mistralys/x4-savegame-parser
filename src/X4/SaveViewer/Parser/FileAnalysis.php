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
    public const string ANALYSIS_FILE_NAME = 'analysis.json';
    public const string BACKUP_ARCHIVE_FILE_NAME = 'backup.gz';

    public const string KEY_PROCESS_DATE = 'process-dates';
    public const string KEY_SAVE_DATE = 'save-date';
    public const string KEY_SAVE_ID = 'save-id';
    public const string KEY_SAVE_NAME = 'save-name';
    public const string KEY_EXTRACTION_DURATION = 'extraction-duration';

    private JSONFile $storageFile;
    private string $saveName;
    private DateTime $modifiedDate;
    private FolderInfo $storageFolder;
    private FolderInfo $xmlFolder;
    private FolderInfo $jsonFolder;

    private function __construct(FolderInfo $storageFolder, DateTime $modifiedDate, string $saveName)
    {
        $this->storageFolder = $storageFolder;
        $this->storageFile = JSONFile::factory($storageFolder->getPath().'/'.self::ANALYSIS_FILE_NAME);
        $this->modifiedDate = $modifiedDate;
        $this->saveName = $saveName;
        $this->xmlFolder = FolderInfo::factory($this->getStorageFolder()->getPath().'/XML');
        $this->jsonFolder = FolderInfo::factory($this->getStorageFolder()->getPath().'/JSON');

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
        $date = DateTime::createFromFormat(SaveGameFile::STORAGE_FOLDER_DATE_FORMAT, array_shift($parts));
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

    public function getXMLFolder() : FolderInfo
    {
        return $this->xmlFolder;
    }

    public function getJSONFolder() : FolderInfo
    {
        return $this->jsonFolder;
    }

    public function hasXML() : bool
    {
        return $this->getXMLFolder()->exists();
    }

    public function setExtractionDuration(float $seconds) : self
    {
        $this->setKey(self::KEY_EXTRACTION_DURATION, $seconds);
        return $this->save();
    }

    public function getExtractionDuration() : ?float
    {
        $duration = $this->getKey(self::KEY_EXTRACTION_DURATION);

        if($duration === null || $duration === '') {
            return null;
        }

        return (float)$duration;
    }

    public function getExtractionDurationFormatted() : ?string
    {
        $duration = $this->getExtractionDuration();

        if($duration === null) {
            return null;
        }

        $seconds = (int)$duration;
        $hours = (int)floor($seconds / 3600);
        $minutes = (int)floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        $parts = [];

        if($hours > 0) {
            $parts[] = $hours . 'h';
        }

        if($minutes > 0) {
            $parts[] = $minutes . 'm';
        }

        if($secs > 0 || empty($parts)) {
            $parts[] = $secs . 's';
        }

        return implode(' ', $parts);
    }
}

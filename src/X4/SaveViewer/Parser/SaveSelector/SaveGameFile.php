<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\SaveSelector;

use AppUtils\FileHelper\FileInfo;
use DateTime;
use Mistralys\X4\SaveViewer\SaveViewerException;

class SaveGameFile
{
    public const ERROR_BOTH_FILES_EMPTY = 6678001;
    public const ERROR_CANNOT_GET_MODIFIED_DATE = 6678002;
    public const ERROR_XML_FILE_NOT_AVAILABLE = 6678003;
    public const ERROR_ZIP_FILE_NOT_AVAILABLE = 6678004;

    private ?FileInfo $zipFile;
    private ?FileInfo $xmlFile;
    private FileInfo $referenceFile;

    public function __construct(?FileInfo $zipFile, ?FileInfo $xmlFile)
    {
        $this->zipFile = $zipFile;
        $this->xmlFile = $xmlFile;

        if(isset($this->zipFile)) {
            $this->referenceFile = $this->zipFile;
        } else if(isset($this->xmlFile)) {
            $this->referenceFile = $this->xmlFile;
        } else {
            throw new SaveViewerException(
                'Either an archive file or an xml file must be specified, they can not both be empty.',
                '',
                self::ERROR_BOTH_FILES_EMPTY
            );
        }
    }

    /**
     * File name without extension, e.g. "quicksave".
     * @return string
     */
    public function getID() : string
    {
        return str_replace(
            array('.xml.gz', '.xml'),
            '',
            $this->referenceFile->getBaseName()
        );
    }

    /**
     * Full file name, e.g. "quicksave.xml.gz" of the relevant file (ZIP or XML).
     * @return string
     */
    public function getName() : string
    {
        return $this->referenceFile->getName();
    }

    public function getDateModified() : DateTime
    {
        $date = $this->referenceFile->getModifiedDate();
        if($date !== null) {
            return $date;
        }

        throw new SaveViewerException(
            'Cannot get modified date from the savegame file.',
            sprintf(
                'Affected file: [%s].',
                $this->referenceFile->getPath()
            ),
            self::ERROR_CANNOT_GET_MODIFIED_DATE
        );
    }

    public function getTimestamp() : int
    {
        return $this->getDateModified()->getTimestamp();
    }

    public function isUnzipped() : bool
    {
        return $this->xmlFile !== null && $this->xmlFile->exists();
    }

    public function getZipFile() : ?FileInfo
    {
        return $this->zipFile;
    }

    public function requireZipFile() : FileInfo
    {
        if(isset($this->zipFile)) {
            return $this->zipFile;
        }

        throw new SaveViewerException(
            'Zipped file not available.',
            '',
            self::ERROR_ZIP_FILE_NOT_AVAILABLE
        );
    }

    public function requireXMLFile() : FileInfo
    {
        if(isset($this->xmlFile)) {
            return $this->xmlFile;
        }

        throw new SaveViewerException(
            'XML file not available.',
            '',
            self::ERROR_XML_FILE_NOT_AVAILABLE
        );
    }

    public function unzip() : FileInfo
    {
        if(isset($this->xmlFile) && $this->xmlFile->exists())
        {
            return $this->xmlFile;
        }

        $sourceFile = $this->requireZipFile();
        $outFile = FileInfo::factory(str_replace('.xml.gz', '.xml', $sourceFile->getPath()));

        $this->_unzip($this->requireZipFile(), $outFile);

        $this->xmlFile = $outFile;

        return $outFile;
    }

    private function _unzip(FileInfo $sourceFile, FileInfo $targetFile) : void
    {
        $this->log('Unzipping file [%s].', $sourceFile->getName());

        $buffer_size = 4096; // read 4kb at a time

        $file = gzopen($sourceFile->getPath(), 'rb');
        $out_file = fopen($targetFile->getPath(), 'wb');

        while(!gzeof($file)) {
            // Read buffer-size bytes
            // Both fwrite and gzread and binary-safe
            fwrite($out_file, gzread($file, $buffer_size));
        }

        fclose($out_file);
        gzclose($file);

        $this->log('Unzip complete.');
    }

    private function log(string $message, ...$params) : void
    {

    }
}

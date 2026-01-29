<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\SaveSelector;

use AppUtils\BaseException;
use AppUtils\ConvertHelper;
use AppUtils\FileHelper\FileInfo;
use AppUtils\FileHelper\FolderInfo;
use AppUtils\FileHelper_Exception;
use DateTime;
use Mistralys\X4\SaveViewer\Parser\FileAnalysis;
use Mistralys\X4\SaveViewer\Parser\SaveSelector;
use Mistralys\X4\SaveViewer\SaveViewerException;
use function AppUtils\parseVariable;

class SaveGameFile
{
    public const ERROR_BOTH_FILES_EMPTY = 136401;
    public const ERROR_XML_FILE_NOT_AVAILABLE = 136402;
    public const ERROR_ZIP_FILE_NOT_AVAILABLE = 136403;
    public const ERROR_CANNOT_ACCESS_STORAGE_FOLDER = 136404;
    public const ERROR_CANNOT_GET_ARCHIVE_DATE = 136405;

    public const STORAGE_FOLDER_DATE_FORMAT = 'YmdHis';
    public const FILE_MODE_ZIP = 'zip';
    public const FILE_MODE_XML = 'xml';

    private FileInfo $zipFile;
    private FileInfo $xmlFile;
    private FileInfo $referenceFile;
    private FolderInfo $outputFolder;
    private FileAnalysis $analysis;
    private string $fileMode;

    /**
     * @param FolderInfo $outputFolder
     * @param FileInfo|null $zipFile
     * @param FileInfo|null $xmlFile
     * @throws SaveViewerException
     */
    public function __construct(FolderInfo $outputFolder, ?FileInfo $zipFile, ?FileInfo $xmlFile)
    {
        $this->outputFolder = $outputFolder;

        if($zipFile !== null)
        {
            $this->referenceFile = $zipFile;
            $this->zipFile = $zipFile;
            $this->xmlFile = $this->resolveExtractionXMLFile($zipFile);
            $this->fileMode = self::FILE_MODE_ZIP;
        }
        else if($xmlFile !== null)
        {
            $this->referenceFile = $xmlFile;
            $this->fileMode = self::FILE_MODE_XML;
            $this->xmlFile = $xmlFile;
            $this->zipFile = FileInfo::factory(str_replace('.xml', '.xml.gz', $this->xmlFile->getPath()));
        }
        else
        {
            throw new SaveViewerException(
                'Either an archive file or an xml file must be specified, they can not both be empty.',
                '',
                self::ERROR_BOTH_FILES_EMPTY
            );
        }

        $this->analysis = FileAnalysis::createFromSaveFile($this);
    }

    /**
     * @return string
     * @see self::FILE_MODE_XML
     * @see self::FILE_MODE_ZIP
     */
    public function getFileMode() : string
    {
        return $this->fileMode;
    }

    public function getID() : string
    {
        return $this->analysis->getSaveID();
    }

    /**
     * The storage folder for this savegame.
     *
     * @return FolderInfo
     * @throws SaveViewerException
     * @throws FileHelper_Exception
     */
    public function getStorageFolder() : FolderInfo
    {
        return FolderInfo::factory(sprintf(
            '%s/unpack-%s-%s',
            $this->outputFolder->getPath(),
            $this->getDateModified()->format(self::STORAGE_FOLDER_DATE_FORMAT),
            $this->getBaseName()
        ));
    }

    /**
     * File name without extension, e.g. "quicksave".
     * @return string
     */
    public function getBaseName() : string
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
        if(!isset($this->analysis)) {
            return $this->referenceFile->getModifiedDate();
        }

        return $this->analysis->getDateModified();
    }

    public function getTimestamp() : int
    {
        return $this->getDateModified()->getTimestamp();
    }

    public function isUnzipped() : bool
    {
        return $this->xmlFile->exists();
    }

    public function getZipFile() : FileInfo
    {
        return $this->zipFile;
    }

    public function requireZipFile() : FileInfo
    {
        if($this->zipFile->exists()) {
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
        if($this->xmlFile->exists()) {
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
        if($this->fileMode === self::FILE_MODE_XML)
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

    public function getReferenceFile() : FileInfo
    {
        return $this->referenceFile;
    }

    public function getAnalysis() : FileAnalysis
    {
        return $this->analysis;
    }

    public function isTempFile() : bool
    {
        return $this->getBaseName() === SaveSelector::TEMP_SAVE_NAME;
    }

    /**
     * Special case: We generate an XML savegame file name based
     * on the modification date of the existing GZ archive file.
     * This is used when there is both an archive file and an XML
     * file in the savegame folder: The player may have extracted
     * it manually.
     *
     * As there is no reliable way to check whether the XML file
     * is up-to-date with the archive, we generate an XML file
     * name based on the modification date of the archive file,
     * to guarantee that we will extract information from the
     * right version of the XML.
     *
     * This way the save is not considered "zipped", even if the
     * original XML file is present.
     *
     * @param FileInfo $zipFile
     * @return FileInfo
     * @throws FileHelper_Exception
     * @throws SaveViewerException
     */
    private function resolveExtractionXMLFile(FileInfo $zipFile) : FileInfo
    {
        $date = $zipFile->getModifiedDate();
        if($date === null) {
            throw new SaveViewerException(
                'Cannot get GZ file date.',
                '',
                self::ERROR_CANNOT_GET_ARCHIVE_DATE
            );
        }

        return FileInfo::factory(sprintf(
            '%s/%s-%s.xml',
            $zipFile->getFolderPath(),
            $zipFile->getBaseName(),
            $date->format(self::STORAGE_FOLDER_DATE_FORMAT)
        ));
    }

    /**
     * @return FileInfo
     */
    public function getXMLFile() : FileInfo
    {
        return $this->xmlFile;
    }
}

<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser;

use AppUtils\FileHelper;
use AppUtils\FileHelper\FileInfo;
use AppUtils\FileHelper\FolderInfo;
use AppUtils\FileHelper_Exception;
use Mistralys\X4\SaveViewer\Parser\SaveSelector\SaveGameFile;
use Mistralys\X4\SaveViewer\SaveViewerException;
use Mistralys\X4\SaveViewer\Traits\DebuggableInterface;
use Mistralys\X4\SaveViewer\Traits\DebuggableTrait;
use Mistralys\X4\SaveViewer\Config\Config;

class SaveSelector implements DebuggableInterface
{
    use DebuggableTrait;

    public const ERROR_MOST_RECENT_FILE_NOT_FOUND = 136001;
    public const ERROR_SAVEGAME_NOT_FOUND = 136002;
    public const ERROR_CANNOT_ACCESS_SAVES_FOLDER = 136003;
    public const ERROR_CANNOT_ACCESS_SAVE_FILE = 136004;
    public const TEMP_SAVE_NAME = 'temp_save';

    private FolderInfo $savesFolder;

    private FolderInfo $storageFolder;

    /**
     * @var SaveGameFile[]|null
     */
    private ?array $cachedFiles = null;

    public function __construct(FolderInfo $savesFolder, FolderInfo $storageFolder)
    {
        $this->savesFolder = $savesFolder;
        $this->storageFolder = $storageFolder;
    }

    public function getSavesFolder() : FolderInfo
    {
        return $this->savesFolder;
    }

    public function getStorageFolder() : FolderInfo
    {
        return $this->storageFolder;
    }

    public function getLogIdentifier() : string
    {
        return 'SaveSelector ['.$this->savesFolder->getName().'] | ';
    }

    /**
     * @param string|FolderInfo $savesFolder
     * @param string|FolderInfo $storageFolder
     * @return SaveSelector
     * @throws FileHelper_Exception
     */
    public static function create($savesFolder, $storageFolder) : SaveSelector
    {
        return new SaveSelector(
            FolderInfo::factory($savesFolder),
            FolderInfo::factory($storageFolder)
        );
    }

    /**
     * Creates an instance of the save selector using
     * the paths configured in the <code>config.php</code>
     * file.
     *
     * @return SaveSelector
     * @throws FileHelper_Exception
     */
    public static function createFromConfig() : SaveSelector
    {
        return self::create(
            Config::getSavesFolder(),
            Config::getString('X4_STORAGE_FOLDER')
        );
    }

    /**
     * Returns the most recent save game, if any are found in the target folder.
     *
     * @return SaveGameFile|null
     * @throws FileHelper_Exception
     * @throws SaveViewerException
     */
    public function getMostRecent() : ?SaveGameFile
    {
        $saves = $this->getSaveGames();

        if(!empty($saves)) {
            return array_shift($saves);
        }

        return null;
    }

    /**
     * Like {@see self::getMostRecent()}, but always returns a save
     * game instance or throws and exception if none are available.
     *
     * @return SaveGameFile
     * @throws FileHelper_Exception
     * @throws SaveViewerException {@see self::ERROR_MOST_RECENT_FILE_NOT_FOUND}
     */
    public function requireMostRecent() : SaveGameFile
    {
        $save = $this->getMostRecent();

        if($save !== null) {
            return $save;
        }

        throw new SaveViewerException(
            'Most recent save game not found.',
            '',
            self::ERROR_MOST_RECENT_FILE_NOT_FOUND
        );
    }

    /**
     * Retrieves the list of available save game files,
     * sorted from most recent to least recent.
     *
     * NOTE: Fetches the save games fresh each time
     * the method is called.
     *
     * @return SaveGameFile[]
     * @throws SaveViewerException
     */
    public function getSaveGames() : array
    {
        if(isset($this->cachedFiles)) {
            return $this->cachedFiles;
        }

        $info = $this->compileFileInformation();
        $result = array();

        foreach($info as $item)
        {
            $result[] = new SaveGameFile(
                $this->storageFolder,
                $item['gz'],
                $item['xml']
            );
        }

        usort($result, static function (SaveGameFile $a, SaveGameFile $b) : int
        {
            return $b->getTimestamp() - $a->getTimestamp();
        });

        $this->cachedFiles = $result;

        return $result;
    }

    public function clearCache() : self
    {
        $this->cachedFiles = null;
        return $this;
    }

    /**
     * @return array<int,{gz:FileInfo|NULL,xml:FileInfo|NULL}>
     * @throws SaveViewerException
     */
    public function compileFileInformation() : array
    {
        $this->log('Detecting savegame files.');
        $this->log('Target folder: [%s].', $this->savesFolder->getPath());

        $files = $this->getFilesList();

        if(empty($files)) {
            return array();
        }

        $list = array();
        foreach($files as $file)
        {
            $id = str_replace(array('.xml.gz', '.xml'), '', basename($file));

            if($id === self::TEMP_SAVE_NAME) {
                continue;
            }

            if(!isset($list[$id])) {
                $list[$id] = array(
                    'gz' => null,
                    'xml' => null
                );
            }

            try
            {
                $info = FileInfo::factory($file);
            }
            catch (FileHelper_Exception $e)
            {
                throw new SaveViewerException(
                    'Cannot access savegame file.',
                    '',
                    self::ERROR_CANNOT_ACCESS_SAVE_FILE
                );
            }

            $extension = $info->getExtension();
            $list[$id][$extension] = $info;
        }

        $this->log('Found [%s] savegame files.', count($list));

        return $list;
    }

    /**
     * @return string[]
     * @throws SaveViewerException
     */
    private function getFilesList() : array
    {
        try
        {
            return FileHelper::createFileFinder($this->savesFolder)
                ->includeExtensions(array('gz', 'xml'))
                ->setPathmodeAbsolute()
                ->getAll();
        }
        catch (FileHelper_Exception $e)
        {
            throw new SaveViewerException(
                'Cannot read savegame files from the saves folder.',
                '',
                self::ERROR_CANNOT_ACCESS_SAVES_FOLDER,
                $e
            );
        }
    }

    /**
     * @param string|FileInfo $saveGameFile
     * @return SaveGameFile
     */
    public function getSaveGameByFileName($saveGameFile) : SaveGameFile
    {
        $saves = $this->getSaveGames();
        $name = FileInfo::factory($saveGameFile)->getName();

        foreach($saves as $save)
        {
            if($save->getName() === $name) {
                return $save;
            }
        }

        throw new SaveViewerException(
            'Cannot find savegame by name.',
            sprintf(
                'The savegame [%s] was not found. The available saves are: '.PHP_EOL.
                '- %s',
                $name,
                implode(PHP_EOL.'- ', $this->getSaveNames())
            ),
            self::ERROR_SAVEGAME_NOT_FOUND
        );
    }

    /**
     * @return string[]
     * @throws SaveViewerException
     */
    public function getSaveNames() : array
    {
        $names = array();
        $saves = $this->getSaveGames();

        foreach($saves as $save)
        {
            $names[] = $save->getName();
        }

        return $names;
    }
}

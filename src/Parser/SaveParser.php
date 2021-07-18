<?php

declare(strict_types=1);

namespace Mistralys\X4Saves;

use AppUtils\ConvertHelper;
use AppUtils\FileHelper;
use AppUtils\FileHelper_Exception;
use Mistralys\X4Saves\Data\SaveReader\Blueprints;
use Mistralys\X4Saves\SaveParser\Tags\Tag;
use SimpleXMLElement;

class SaveParser
{
    const ERROR_XML_FILE_NOT_FOUND = 84401;
    const ERROR_INVALID_TAG_CLASS = 84402;

    private string $saveName;

    private string $outputFolder;

    private string $zipFile;

    private string $xmlFile;

    private string $currentTag = '';

    /**
     * @var Tag[]
     */
    private $activeTags = array();

    /**
     * @var resource|NULL
     */
    private $outFile = null;

    /**
     * @var Tag[]
     */
    private array $openingTags;

    /**
     * @var array<string,Tag>
     */
    private array $closingTags;

    private array $analysis = array(
        'date' => null
    );

    private int $tagCounter = 0;

    private string $analysisFile;

    private bool $analysisLoaded = false;

    /**
     * @var array<string,Tag>
     */
    private array $tagPaths = array();

    /**
     * @var string[]
     */
    private array $activeIDStack;

    public function __construct(string $saveName)
    {
        $this->saveName = $this->parseName($saveName);
        $this->zipFile = X4_SAVES_FOLDER.'/'.$saveName.'.xml.gz';
        $this->outputFolder = X4_SAVES_FOLDER.'/unpack_'.$this->saveName;
        $this->xmlFile = X4_SAVES_FOLDER.'/'.$saveName.'.xml';
        $this->analysisFile = $this->outputFolder.'/analysis.json';
    }

    /**
     * @throws X4Exception
     * @throws FileHelper_Exception
     */
    public function unpack() : void
    {
        $this->loadAnalysis();
        $this->splitFile();
    }

    public function isUnpacked() : bool
    {
        $this->loadAnalysis();

        return isset($this->analysis['date']);
    }

    private function loadAnalysis() : void
    {
        if($this->analysisLoaded) {
            return;
        }

        $this->log('Loading previous analysis.');

        $this->analysisLoaded = true;

        if(!file_exists($this->analysisFile)) {
            $this->log('No analysis found.');
            return;
        }

        $data = FileHelper::parseJSONFile($this->analysisFile);
        if(filemtime($this->xmlFile) === $data['date'])
        {
            $this->log('Found an up to date analysis.');
            $this->analysis = $data;
            return;
        }

        $this->log('Found an older existing analysis; Removing it.');
        FileHelper::deleteTree($this->outputFolder);
    }

    private function log(string $message) : void
    {
        echo $message.PHP_EOL.'<br>';
    }

    private function splitFile() : void
    {
        $this->log(sprintf('Splitting the XML file [%s.].', $this->saveName));

        if($this->isUnpacked()) {
            $this->log('Already split, skipping.');
            return;
        }

        $this->log('Extracting data.');

        $this->createFolders();
        $this->compileTags();

        $this->analysis['date'] = filemtime($this->xmlFile);

        $in = fopen($this->xmlFile, "r+");
        $number = 0;
        $maxNumber = -1;

        while (($line = stream_get_line($in, 1024 * 1024, "\n")) !== false)
        {
            $line = trim($line);

            if(!empty($line))
            {
                $this->analyzeLine($line, $number);
            }

            $number++;

            if($maxNumber > 0 && $number >= $maxNumber)
            {
                $this->log('Stopped at maximum of '.$maxNumber.' lines.');
                die('STOPPED');
            }
        }

        fclose($in);

        FileHelper::saveAsJSON($this->analysis, $this->analysisFile, true);

        $this->log('Done extracting.');
    }

    private function createFolders() : void
    {
        FileHelper::createFolder($this->outputFolder);
    }

    private function compileTags() : void
    {
        $tags = FileHelper::createFileFinder(__DIR__.'/Tags/Tag')
            ->getPHPClassNames();

        foreach($tags as $tagName)
        {
            $tag = $this->createTag($tagName);

            $this->tagPaths[$tag->getTagPath()] = $tag;
        }
    }

    /**
     * @param string $id
     * @return Tag
     * @throws X4Exception
     *
     * @see SaveParser::ERROR_INVALID_TAG_CLASS
     */
    private function createTag(string $id) : Tag
    {
        $class = 'Mistralys\X4Saves\SaveParser\Tags\Tag\\'.$id;

        $obj = new $class($this->outputFolder);

        if($obj instanceof Tag)
        {
            return $obj;
        }

        throw new X4Exception(
            'Invalid parser tag class',
            sprintf('The tag [%s] is not a valid tag class.', $id),
            self::ERROR_INVALID_TAG_CLASS
        );
    }

    private array $nameStack = array();

    /**
     * @param string $line Trimmed text for the line read from the save file (never empty)
     * @param int $number The line number in the file
     */
    private function analyzeLine(string $line, int $number) : void
    {
        if(substr($line, 0, 2) === '</')
        {
            $this->tagClosed($number);
        }
        else if(substr($line, -2, 2) === '/>')
        {
            $parts = explode(' ', trim($line, '<>'));
            $tagStr = array_shift($parts);

            $this->tagOpened($tagStr, $line, $number);
            $this->tagClosed($number);
        }
        else if(substr($line, 0, 1) === '<')
        {
            $parts = explode(' ', trim($line, '<>'));
            $tagStr = array_shift($parts);

            if($tagStr === '?xml' || $tagStr === 'savegame')
            {
                return;
            }

            $this->tagOpened($tagStr, $line, $number);
        }
    }

    private function tagOpened(string $tagName, string $line, int $number) : void
    {
        $this->tagCounter++;
        $tagID = $this->tagCounter;

        $this->nameStack[] = $tagName;

        $activePath = implode('.', $this->nameStack);

        if($this->childTagOpened($activePath, $tagName, $line, $number))
        {
            return;
        }

        $this->activeIDStack[] = $tagID;

        foreach($this->tagPaths as $tag)
        {
            if(!$tag->isPathMatch($activePath, $tagName, $line))
            {
                continue;
            }

            $this->log('----------------------------------------------------------------');
            $this->log(sprintf('Opening tag [%s], ID [%s].', $tagName, $tagID));

            if(!isset($this->activeTags[$tagID]))
            {
                $this->activeTags[$tagID] = array(
                    'path' => $activePath,
                    'tags' => array()
                );
            }

            $this->activeTags[$tagID]['tags'][] = $tag;

            $tag->tagOpened($line, $number);
        }
    }

    private function childTagOpened(string $activePath, string $tagName, string $line, int $number) : bool
    {
        $ids = array_keys($this->activeTags);
        if(empty($ids))
        {
            return false;
        }

        $activeID = array_pop($ids);
        $tags = $this->activeTags[$activeID]['tags'];
        $parentPath = $this->activeTags[$activeID]['path'];

        foreach ($tags as $tag)
        {
            $tagPath = substr($activePath, strlen($parentPath)+1);

            $this->log('...Child tag opened ['.$tagName.'].');

            $tag->childTagOpened($tagPath, $tagName, $line, $number);
        }

        return true;
    }

    private function tagClosed(int $number) : void
    {
        $activePath = implode('.', $this->nameStack);

        $tagName = array_pop($this->nameStack);
        $tagID = array_pop($this->activeIDStack);

        if(!isset($this->activeTags[$tagID]))
        {
            return;
        }

        $parentPath = $this->activeTags[$tagID]['path'];

        // Closing a parent tag
        if($activePath === $parentPath)
        {
            foreach($this->activeTags[$tagID]['tags'] as $tag)
            {
                $this->log(sprintf('...Closing tag [%s].', $tagName));

                $tag->tagClosed($activePath, $tagName, $number);
            }
            
            unset($this->activeTags[$tagID]);
        }
        // Closing child tags
        else
        {
            // Keep the active ID in the stack for further children
            $this->activeIDStack[] = $tagID;
            $parentPath = $this->activeTags[$tagID]['path'];

            foreach($this->activeTags[$tagID]['tags'] as $tag)
            {
                $this->log(sprintf('...Closing child tag [%s].', $tagName));

                $tagPath = substr($activePath, strlen($parentPath)+1);

                $tag->childTagClosed($tagPath, $tagName, $number);
            }
        }
    }

    private function saveData(string $nodeName, array $data) : void
    {
        FileHelper::saveAsJSON(
            $data,
            $this->outputFolder.'/json/'.$nodeName.'.json',
            true
        );
    }

    private function parseName(string $fileName) : string
    {
        if(!strstr($fileName, '.')) {
            return $fileName;
        }

        $parts = explode('.', $fileName);
        return array_shift($parts);
    }
}

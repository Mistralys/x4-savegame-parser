<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\Tags;

use AppUtils\ConvertHelper;
use AppUtils\FileHelper;
use SimpleXMLElement;

abstract class Tag
{
    public const string PATH_SEPARATOR = ';';

    private string $outputFolder;
    protected int $startAt = 0;
    protected int $endAt = 0;
    private string $path;
    private bool $isComplexPath;
    private int $pathLength;
    private string $tagName;
    private array $matchAttributes;

    public function __construct(string $outputFolder)
    {
        $this->outputFolder = $outputFolder;
        $this->path = $this->getTagPath();
        $this->pathLength = strlen($this->path);

        $this->parsePath();
    }

    private function parsePath() : void
    {
        $this->isComplexPath = strstr($this->path, self::PATH_SEPARATOR) !== false;

        if(!$this->isComplexPath)
        {
            return;
        }

        $parts = explode(self::PATH_SEPARATOR, $this->path);
        $this->tagName = $parts[0];
        $this->matchAttributes = ConvertHelper::parseQueryString($parts[1]);
    }

    public function getID() : string
    {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }

    abstract public function getTagPath() : string;

    abstract public function getSaveName() : string;

    public function tagOpened(string $line, int $number)
    {
        $this->startAt = $number;
        $this->open($line, $number);
    }

    public function childTagOpened(string $activePath, string $tagName, string $line, int $number) : void
    {
        $method = 'open_'.str_replace('.', '_', $activePath);
        if(method_exists($this, $method))
        {
            $this->$method($line, $number);
        }
    }

    public function tagClosed(string $activePath, string $tagName, int $number)
    {
        $this->endAt = $number;
        $this->close($number);
        $this->saveData();
    }

    public function childTagClosed(string $activePath, string $tagName, int $number) : void
    {
        $method = 'close_'.str_replace('.', '_', $activePath);
        
        if(method_exists($this, $method))
        {
            $this->$method($number);
        }
    }

    abstract protected function open(string $line, int $number) : void;

    abstract protected function close(int $number) : void;

    /**
     * Slightly slower than using the regex.
     *
     * @param string $line
     * @return array
     * @throws \Exception
     */
    protected function getAttributes_simplexml(string $line) : array
    {
        // Ensure that even opening tags end like self-closing tags for the parsing to work
        $line = rtrim($line, '/>').'/>';

        $attributes = (new SimpleXMLElement($line))->attributes();
        $result = array();

        foreach($attributes as $name => $val)
        {
            $result[$name] = (string)$val;
        }

        return $result;
    }
    
    protected function getAttributes(string $line) :array
    {
        preg_match_all('/ ([a-z]+)="(.*)"/sU', $line, $matches, PREG_PATTERN_ORDER);
        
        $result = array();
        foreach ($matches[1] as $idx => $tagName)
        {
            $result[$tagName] = $matches[2][$idx];
        }

        return $result;
    }

    /**
     * This has the worst performance.
     *
     * @param string $line
     * @return array
     */
    protected function getAttributes_stringbased(string $line) : array
    {
        $result = array();

        if(strpos($line, '"') === false) {
            return $result;
        }

        $line = substr($line, strpos($line, ' ')+1);
        $line = trim($line, '"/>');
        $parts = explode('" ', $line);

        foreach($parts as $part)
        {
            $tagName = substr($part, 0, strpos($part, '='));
            $value = substr($part, strpos($part, '"')+1);

            $result[$tagName] = $value;
        }

        return $result;
    }

    public function isPathMatch(string $activePath, string $tagName, string $line) : bool
    {
        if(!$this->isComplexPath)
        {
            return substr($activePath, 0, $this->pathLength) === $this->path;
        }

        if($this->tagName !== $tagName)
        {
            return false;
        }

        $atts = $this->getAttributes($line);

        foreach($this->matchAttributes as $name => $val)
        {
            if(!isset($atts[$name]) || $atts[$name] !== $val)
            {
                return false;
            }
        }

        return true;
    }

    abstract protected function getSaveData() : array;

    abstract protected function clear() : void;

    protected function saveData() : void
    {
        $data = $this->getSaveData();

        if(empty($data)) {
            return;
        }

        $save = array(
            'startAt' => $this->startAt,
            'endAt' => $this->endAt,
            'data' => $data
        );

        FileHelper::saveAsJSON(
            $save,
            $this->getSavePath(),
            true
        );
    }

    public function getSavePath() : string
    {
        return sprintf(
            '%s/%s.json',
            $this->outputFolder,
            $this->getSaveName()
        );
    }

    protected function log(string $message) : void
    {
        echo $this->getID().' | '.$message.PHP_EOL.'<br>';
    }
}

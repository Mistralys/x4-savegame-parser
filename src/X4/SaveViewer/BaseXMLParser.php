<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer;

use AppUtils\ClassHelper;
use AppUtils\ClassHelper\BaseClassHelperException;
use AppUtils\ConvertHelper;
use AppUtils\FileHelper;
use AppUtils\Microtime;
use DOMElement;
use DOMNode;
use Mistralys\X4\SaveViewer\Parser\BaseFragment;
use Mistralys\X4\SaveViewer\Parser\Collections;
use Mistralys\X4\SaveViewer\Parser\ConnectionComponent;
use Mistralys\X4\SaveViewer\Parser\FileAnalysis;
use Mistralys\X4\X4Exception;
use XMLReader;

abstract class BaseXMLParser
{
    private bool $logging = false;
    private string $xmlFile;
    private string $outputPath;
    private XMLReader $xml;
    protected FileAnalysis $analysis;

    private array $tagPath = array();
    private string $tagPathString = '';

    public const ACTION_WRITE = 'write';
    public const ACTION_IGNORE = 'ignore';
    public const ACTION_DOM = 'dom';

    /**
     * @var array<string,array{action:string,params:array<string,mixed>}>
     */
    private array $tagActions = array();
    private string $id;
    protected Collections $collections;

    public function __construct(Collections $collections, string $filePath, string $outputPath)
    {
        $this->id = ConvertHelper::string2shortHash($filePath);
        $this->xmlFile = $filePath;
        $this->outputPath = $outputPath;
        $this->analysis = FileAnalysis::create($this->outputPath);
        $this->collections = $collections;

        $this->registerActions();
    }

    public function getXMLFile() : string
    {
        return $this->xmlFile;
    }

    public function getOutputPath() : string
    {
        return $this->outputPath;
    }

    public function setLoggingEnabled(bool $logging) : self
    {
        $this->logging = $logging;
        return $this;
    }

    protected function log(string $message, ...$args) : void
    {
        if($this->logging === false) {
            return;
        }

        echo 'Parser ['.$this->id.'] | ';

        if(empty($args)) {
            echo $message;
        } else
        {
            echo sprintf($message, ...$args);
        }

        echo PHP_EOL;
    }

    abstract protected function registerActions() : void;

    protected function registerAction(string $tagPath, string $action, array $params=array()) : void
    {
        $this->tagActions[$tagPath] = array(
            'action' => $action,
            'params' => $params
        );
    }

    protected function registerIgnore(string $tagPath) : void
    {
        $this->registerAction($tagPath, self::ACTION_IGNORE);
    }

    /**
     * Extracts the XML source code of the target tag path to an XML file.
     * It can then be post-processed later using the specified processor
     * class.
     *
     * @param string $tagPath
     * @param class-string $processorClass
     * @return void
     */
    protected function registerExtractXML(string $tagPath, string $processorClass) : void
    {
        $this->registerAction(
            $tagPath,
            self::ACTION_WRITE,
            array(
                'processor' => $processorClass
            )
        );
    }

    protected function registerDOM(string $tagPath, callable $callback) : void
    {
        $this->registerAction(
            $tagPath,
            self::ACTION_DOM,
            array(
                'callback' => $callback
            )
        );
    }

    public function processFile() : self
    {
        if($this->analysis->hasProcessDate($this->xmlFile)) {
            $this->log('ProcessFile | Already processed, skipping.');
            return $this;
        }

        $this->log('ProcessFile | Found [%s] actions.', count($this->tagActions));
        $this->log('ProcessFile | File: [%s].', basename($this->xmlFile));
        $this->log('ProcessFile | File size: [%s].', ConvertHelper::bytes2readable(filesize($this->xmlFile)));

        $this->_processFile();

        $this->analysis->setProcessDate($this->xmlFile, Microtime::createNow());

        return $this;
    }

    protected function _processFile() : void
    {
        $this->xml = $this->createReader();

        while($this->xml->read())
        {
            if ($this->xml->nodeType === XMLReader::ELEMENT)
            {
                $this->appendPath();
            }

            $action = $this->tagActions[$this->tagPathString]['action'] ?? null;

            if($action === null) {
                continue;
            }

            if($action === self::ACTION_IGNORE) {
                $this->log('ProcessFile | Tag [%s] | Ignore.', $this->tagPathString);
                $this->xml->next();
                continue;
            }

            if($action === self::ACTION_WRITE) {
                $this->log('ProcessFile | Tag [%s] | Write XML to file.', $this->tagPathString);
                $this->writeFragment();
                $this->xml->next();
                continue;
            }

            if($action === self::ACTION_DOM) {
                $this->DOMifyFragment($this->tagActions[$this->tagPathString]['params']['callback']);
                $this->xml->next();
                continue;
            }

            $this->log('ProcessFile | Tag [%s] | No action configured.', $this->tagPathString);
        }
    }

    /**
     * @return array<string,BaseFragment>
     * @throws BaseClassHelperException
     */
    private function getPostProcessors() : array
    {
        $processors = array();

        $folder = $this->outputPath.'/XML';

        if(!is_dir($folder)) {
            return $processors;
        }

        $files = FileHelper::createFileFinder($folder)
            ->includeExtension('xml')
            ->setPathmodeAbsolute()
            ->getAll();

        foreach($this->tagActions as $tagPath => $tagData)
        {
            if($tagData['action'] !== self::ACTION_WRITE)
            {
                continue;
            }

            $class = $tagData['params']['processor'];

            ClassHelper::requireClassExists($class);

            foreach($files as $file)
            {
                if(strpos(basename($file), $tagPath) !== 0) {
                    continue;
                }

                $processors[$file] = ClassHelper::requireObjectInstanceOf(
                    BaseFragment::class,
                    new $class($this->collections, $file, $this->outputPath)
                )
                    ->setLoggingEnabled($this->logging);
            }
        }

        return $processors;
    }

    public function postProcessFragments() : self
    {
        $processors = $this->getPostProcessors();

        if(empty($processors)) {
            $this->log('PostProcess | No post processors found, skipping.');
            return $this;
        }

        $this->log('PostProcess | Found [%s] files to post-process.', count($processors));

        foreach($processors as $outputFile => $processor)
        {
            $this->log('PostProcess | Processing file [%s] via [%s].', basename($outputFile), get_class($processor));

            $processor
                ->processFile()
                ->postProcessFragments();
        }

        $this->analysis->save();
        $this->collections->save();

        return $this;
    }

    private static int $fileCounter = 0;

    private function DOMifyFragment() : void
    {
        $callback($this->xml->expand());
    }

    private function writeFragment() : void
    {
        self::$fileCounter++;

        $outputFile = sprintf(
            '%s/XML/%s-%03d.xml',
            $this->outputPath,
            $this->tagPathString,
            self::$fileCounter
        );

        $this->log('ProcessFile | Tag [%s] | Written to file [%s].', $this->tagPathString, basename($outputFile));

        $this->tagActions[$this->tagPathString]['params']['file'] = $outputFile;

        FileHelper::saveFile(
            $outputFile,
            '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.
            $this->xml->readOuterXml()
        );
    }

    private function appendPath() : void
    {
        $name = $this->xml->name;

        $class = $this->getAttribute('class');
        if(!empty($class)) {
            $name .= '['.$class.']';
        }

        $connection = $this->getAttribute('connection');
        if(!empty($connection)) {
            $name .= '[ID]';
        }

        $this->tagPath[$this->xml->depth] = $name;

        $this->tagPath = array_slice($this->tagPath, 0, $this->xml->depth+1);

        $this->tagPathString = implode('.', $this->tagPath);
    }

    private function dumpNode(string $label='') : void
    {
        print_r(array(
            'label' => $label,
            'type' => $this->xml->nodeType,
            'name' => $this->xml->name,
            'depth' => $this->xml->depth,
            'attributes' => $this->getAttributes()
        ));
    }

    private function getAttributes() : array
    {
        if($this->xml->attributeCount === 0) {
            return array();
        }

        $result = array();

        for($i=0; $i < $this->xml->attributeCount; $i++) {
            $this->xml->moveToNextAttribute();
            $result[$this->xml->name] = $this->xml->value;
        }

        $this->xml->moveToElement();

        return $result;
    }

    private function getAttribute(string $name) : string
    {
        $attributes = $this->getAttributes();
        return $attributes[$name] ?? '';
    }

    /**
     * @return XMLReader
     * @throws BaseClassHelperException
     * @throws X4Exception
     */
    private function createReader() : XMLReader
    {
        $reader = new XMLReader();
        if($reader->open($this->xmlFile))
        {
            return $reader;
        }

        throw new X4Exception(
            'Could not create the XML reader.'
        );
    }

    protected function getFirstChildByName(DOMNode $node, string $tagName) : ?DOMElement
    {
        foreach($node->childNodes as $childNode)
        {
            if($childNode instanceof DOMElement && $childNode->nodeName === $tagName) {
                return $childNode;
            }
        }

        return null;
    }

    protected function checkIsElement(DOMNode $node, ?string $tagName=null) : ?DOMElement
    {
        if(!$node instanceof DOMElement)
        {
            return null;
        }

        if($tagName !== null && $node->nodeName !== $tagName)
        {
            return null;
        }

        return $node;
    }

    protected function getConnectionNodes(?DOMElement $parentNode) : array
    {
        if($parentNode === null) {
            return array();
        }

        if($parentNode->nodeName === 'connections')
        {
            $connections = $parentNode;
        }
        else
        {
            $connections = $this->getFirstChildByName($parentNode, 'connections');
        }

        if($connections === null)
        {
            return array();
        }

        $result = array();

        foreach($connections->childNodes as $childNode)
        {
            $element = $this->checkIsElement($childNode, 'connection');

            if($element !== null) {
                $result[] = $element;
            }
        }

        return $result;
    }

    /**
     * @param DOMElement|null $parentNode
     * @return ConnectionComponent[]
     */
    protected function getConnectionComponents(?DOMElement $parentNode) : array
    {
        $connections = $this->getConnectionNodes($parentNode);

        $result = array();

        foreach($connections as $connection)
        {
            $component = $this->getComponentNode($connection);

            if($component !== null) {
                $result[] = new ConnectionComponent(
                    $connection,
                    $component
                );
            }
        }

        return $result;
    }

    /**
     * Fetches all <component> nodes from the following cases:
     *
     * - The parent has a child <connections> tag
     * - The parent is a <connections> tag
     * - The parent is a <connection> tag
     *
     * @param DOMElement|ConnectionComponent|null $parentNode
     * @return ConnectionComponent[]
     */
    protected function findConnectionComponents($parentNode) : array
    {
        if($parentNode instanceof ConnectionComponent) {
            $parentNode = $parentNode->componentNode;
        }

        if($parentNode === null) {
            return array();
        }

        if($parentNode->nodeName === 'connection')
        {
            $componentNode = $this->getComponentNode($parentNode);

            if($componentNode !== null)
            {
                return array(new ConnectionComponent(
                    $parentNode,
                    $componentNode
                ));
            }

            return array();
        }

        return $this->getConnectionComponents($parentNode);
    }

    protected function findConnectionComponent(?DOMElement $parentNode) : ?ConnectionComponent
    {
        $items = $this->findConnectionComponents($parentNode);

        if(!empty($items)) {
            return $items[0];
        }

        return null;
    }

    protected function getComponentNode(DOMElement $parentNode) : ?DOMElement
    {
        return $this->getFirstChildByName($parentNode, 'component');
    }
}

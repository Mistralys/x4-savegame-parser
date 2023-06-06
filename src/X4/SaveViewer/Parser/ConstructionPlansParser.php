<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser;

use AppUtils\FileHelper\FileInfo;
use DOMDocument;
use DOMElement;
use Mistralys\X4\SaveViewer\Parser\ConstructionPlans\ConstructionPlan;

class ConstructionPlansParser
{
    private FileInfo $xmlFile;
    private DOMDocument $dom;

    /**
     * @var ConstructionPlan[]
     */
    private array $plans = array();

    public function __construct(FileInfo $xmlFile)
    {
        $this->xmlFile = $xmlFile;
        $this->dom = new DOMDocument();
        $this->dom->preserveWhiteSpace = false;

        $this->parse();
    }

    public static function createFromConfig() : ConstructionPlansParser
    {
        return new ConstructionPlansParser(FileInfo::factory(X4_FOLDER.'/constructionplans.xml'));
    }

    private function parse() : void
    {
        if(!$this->xmlFile->exists()) {
            return;
        }

        $this->dom->loadXML($this->xmlFile->getContents());

        $planNodes = $this->dom->getElementsByTagName('plan');

        foreach($planNodes as $planNode)
        {
            if($planNode instanceof DOMElement) {
                $this->parsePlan($planNode);
            }
        }

        usort($this->plans, static function(ConstructionPlan $a, ConstructionPlan $b) : int {
            return strnatcasecmp($a->getLabel(), $b->getLabel());
        });
    }

    private function parsePlan(DOMElement $planNode) : void
    {
        $this->plans[] = new ConstructionPlan($planNode);
    }

    /**
     * @return ConstructionPlan[]
     */
    public function getPlans() : array
    {
        return $this->plans;
    }

    public function getXMLFile() : FileInfo
    {
        return $this->xmlFile;
    }
}

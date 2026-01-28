<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser;

use AppUtils\FileHelper\FileInfo;
use AppUtils\Request;
use DOMDocument;
use DOMElement;
use Mistralys\X4\SaveViewer\Parser\ConstructionPlans\ConstructionPlan;
use Mistralys\X4\SaveViewer\Parser\ConstructionPlans\ConstructionPlanException;
use Mistralys\X4\SaveViewer\UI\Pages\ConstructionPlansPage;
use Mistralys\X4\SaveViewer\UI\Pages\ViewPlanPage;
use Mistralys\X4\UI\Page\BasePage;
use Mistralys\X4\SaveViewer\Config\Config;

class ConstructionPlansParser
{
    public const ERROR_PLAN_ID_NOT_FOUND = 138201;

    private FileInfo $xmlFile;
    private DOMDocument $dom;

    /**
     * @var array<string,ConstructionPlan>
     */
    private array $plans = array();

    public function __construct(FileInfo $xmlFile)
    {
        $this->xmlFile = $xmlFile;
        $this->dom = new DOMDocument();
        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput = true;

        $this->parse();
    }

    public static function createFromConfig() : ConstructionPlansParser
    {
        return new ConstructionPlansParser(FileInfo::factory(Config::getString('X4_FOLDER') . DIRECTORY_SEPARATOR . 'constructionplans.xml'));
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

        uasort($this->plans, static function(ConstructionPlan $a, ConstructionPlan $b) : int {
            return strnatcasecmp($a->getLabel(), $b->getLabel());
        });
    }

    public function save() : self
    {
        $this->xmlFile->copyTo(sprintf(
            '%s/%s-backup-%s.%s',
            $this->xmlFile->getFolderPath(),
            $this->xmlFile->getBaseName(),
            date('YmdHis'),
            $this->xmlFile->getExtension()
        ));

        $this->xmlFile->putContents($this->dom->saveXML());

        return $this;
    }

    private function parsePlan(DOMElement $planNode) : void
    {
        $plan = new ConstructionPlan($this, $planNode);
        $this->plans[$plan->getID()] = $plan;
    }

    /**
     * @return ConstructionPlan[]
     */
    public function getPlans() : array
    {
        return array_values($this->plans);
    }

    public function getXMLFile() : FileInfo
    {
        return $this->xmlFile;
    }

    public function getURLList(array $params=array()) : string
    {
        $params[BasePage::REQUEST_PARAM_PAGE] = ConstructionPlansPage::URL_NAME;

        return '?'.http_build_query($params);
    }

    public function getByRequest() : ?ConstructionPlan
    {
        $id = (string)Request::getInstance()
            ->registerParam(ViewPlanPage::REQUEST_PARAM_PLAN_ID)
            ->setEnum($this->getPlanIDs())
            ->get();

        if(!empty($id)) {
            return $this->getByID($id);
        }

        return null;
    }

    /**
     * @param string $id
     * @return ConstructionPlan
     * @throws ConstructionPlanException {@see self::ERROR_PLAN_ID_NOT_FOUND}
     */
    public function getByID(string $id) : ConstructionPlan
    {
         if(isset($this->plans[$id])) {
             return $this->plans[$id];
         }

        throw new ConstructionPlanException(
            'Construction plan not found by ID.',
            sprintf(
                'The plan ID [%s] does not exist.',
                $id
            ),
            self::ERROR_PLAN_ID_NOT_FOUND
        );
    }

    public function planIDExists(string $id) : bool
    {
        return isset($this->plans[$id]);
    }

    /**
     * @return string[]
     */
    public function getPlanIDs() : array
    {
        return array_keys($this->plans);
    }
}

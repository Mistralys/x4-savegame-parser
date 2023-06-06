<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Parser\ConstructionPlans;

use DOMElement;
use Mistralys\X4\Database\Modules\ModuleDef;
use Mistralys\X4\Database\Modules\ModuleDefs;
use Mistralys\X4\Database\Modules\ModuleException;

class PlanModule
{
    public const ERROR_NO_MODULE_DETECTED = 137701;

    private DOMElement $node;
    private ?ModuleDef $module = null;
    private bool $detected = false;

    public function __construct(DOMElement $node)
    {
        $this->node = $node;
    }

    public function getMacroID() : string
    {
        return $this->node->getAttribute('macro');
    }

    public function getModule() : ModuleDef
    {
        if(isset($this->module)) {
            return $this->module;
        }

        throw new ModuleException(
            'No module detected.',
            '',
            self::ERROR_NO_MODULE_DETECTED
        );
    }

    public function detectModule() : ?ModuleDef
    {
         if($this->detected === false)
         {
             $this->detected = true;
             $this->module = ModuleDefs::getInstance()->findByMacro($this->getMacroID());
         }

         return $this->module;
    }
}

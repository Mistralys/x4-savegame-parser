<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\Data\SaveReader\Blueprints;

class Blueprint
{
    private string $name;

    private BlueprintCategory $category;

    public function __construct(BlueprintCategory $category, string $name)
    {
        $this->category = $category;
        $this->name = $name;
    }

    /**
     * @return BlueprintCategory
     */
    public function getCategory(): BlueprintCategory
    {
        return $this->category;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}

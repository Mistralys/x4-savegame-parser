<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Data\SaveReader\Log\Categories;

use Mistralys\X4\SaveViewer\Data\SaveReader\Log\LogCategories;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log\MiscLogCategory;

/**
 * @property array<string,CachedCategory|MiscLogCategory>
 * @method CachedCategory|MiscLogCategory getByID(string $id)
 */
class CachedCategories extends LogCategories
{
    public function registerCategory(CachedCategory $category) : void
    {
        $this->categories[$category->getCategoryID()] = $category;
    }
}

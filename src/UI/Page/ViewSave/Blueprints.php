<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\UI\Pages\ViewSave;

class Blueprints extends SubPage
{
    const URL_PARAM = 'Blueprints';

    public function getURLName() : string
    {
        return self::URL_PARAM;
    }

    public function isInSubnav() : bool
    {
        return true;
    }

    public function getTitle() : string
    {
        return 'Blueprints';
    }

    public function renderContent() : void
    {
        $categories = $this->reader->getBlueprints()->getCategories();

        foreach($categories as $category) {
            ?>
            <h4><?php echo $category->getLabel() ?></h4>
            <ul>
                <?php
                $blueprints = $category->getBlueprints();
                foreach ($blueprints as $blueprint) {
                    ?>
                    <li><?php echo $blueprint->getName() ?></li>
                    <?php
                }
                ?>
            </ul>
            <?php
        }
    }
}
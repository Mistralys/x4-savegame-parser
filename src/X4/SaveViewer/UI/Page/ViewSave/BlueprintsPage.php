<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages\ViewSave;

use AppUtils\Request;
use Mistralys\X4\Database\Races\RaceDefs;
use Mistralys\X4\Database\Races\RaceException;
use Mistralys\X4\SaveViewer\Data\SaveReader\Blueprints\Blueprint;
use Mistralys\X4\UI\Button;
use Mistralys\X4\UI\Text;
use function AppLocalize\pt;
use function AppLocalize\pts;
use function AppLocalize\t;
use function AppUtils\sb;

class BlueprintsPage extends SubPage
{
    public const URL_NAME = 'Blueprints';
    public const REQUEST_PARAM_GENERATE_XML = 'generate-xml';
    public const REQUEST_PARAM_GENERATE_MARKDOWN = 'generate-markdown';

    public function getURLName() : string
    {
        return self::URL_NAME;
    }

    public function isInSubnav() : bool
    {
        return true;
    }

    public function getTitle() : string
    {
        return t('Blueprints');
    }

    public function getSubtitle() : string
    {
        return '';
    }

    public function getAbstract() : string
    {
        return '';
    }

    public function renderContent() : void
    {
        if(Request::getInstance()->getBool(self::REQUEST_PARAM_GENERATE_XML)) {
            $this->renderXML();
            return;
        }

        if(Request::getInstance()->getBool(self::REQUEST_PARAM_GENERATE_MARKDOWN)) {
            $this->renderMarkdown();
            return;
        }

        $collection = $this->getReader()->getBlueprints();
        $categories = $collection->getCategories();

        ?>
            <p>
                <?php
                pts('%1$s knows a total of %2$s blueprints.', $this->getReader()->getPlayer()->getName(), $collection->countBlueprints());
                pt('Available categories:');
                ?>
            </p>
            <p>
                <?php
                foreach($categories as $category) {
                    ?>
                    <a href="#category-<?php echo $category->getID() ?>">
                        <?php echo $category->getLabel() ?>
                    </a>
                    <?php echo Text::create(' - '.$category->countBlueprints())->colorMuted() ?>
                    <br>
                    <?php
                }
                ?>
            </p>
            <p>
                <?php
                echo sb()
                    ->add(Button::create(t('Generate XML'))
                        ->colorPrimary()
                        ->link($collection->getURLGenerateXML())
                    )
                    ->add(Button::create(t('Generate Markdown'))
                        ->colorPrimary()
                        ->link($collection->getURLGenerateMarkdown())
                    );
                ?>
            </p>
            <br>
        <?php

        foreach($categories as $category) {
            ?>
            <h4 id="category-<?php echo $category->getID() ?>"><?php echo $category->getLabel() ?></h4>
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

    private function renderXML() : void
    {
        $list = $this->resolveList();

        $xml = '<blueprints>'.PHP_EOL;

        foreach ($list as $raceLabel => $categories)
        {
            foreach ($categories as $categoryLabel => $blueprints)
            {
                $xml .= '    <!-- Category: ' . $raceLabel.' / '.$categoryLabel . ' -->' . PHP_EOL;

                foreach ($blueprints as $blueprint)
                {
                    $xml .= '    <blueprint ware="' . $blueprint->getName() . '"/>' . PHP_EOL;
                }

                $xml .= PHP_EOL;
            }
        }

        $xml .= '</blueprints>';

        ?>
            <p><?php pt('XML source for the player\'s blueprints:') ?></p>
            <textarea rows="10" style="width: 96%;font-family:monospace"><?php echo htmlspecialchars($xml) ?></textarea>
        <?php
    }

    private function renderMarkdown() : void
    {
        $list = $this->resolveList();
        $xml = '';

        foreach ($list as $raceLabel => $categories)
        {
            $xml .= '### '.$raceLabel.PHP_EOL.PHP_EOL;

            foreach ($categories as $categoryLabel => $blueprints)
            {
                $xml .= '#### '.$categoryLabel . PHP_EOL.PHP_EOL;
                $xml .= '```xml'.PHP_EOL;

                foreach ($blueprints as $blueprint)
                {
                    $xml .= '<blueprint ware="' . $blueprint->getName() . '"/>' . PHP_EOL;
                }

                $xml .= '```'.PHP_EOL;
                $xml .= PHP_EOL;
            }
        }

        ?>
        <p><?php pt('Markdown source for the player\'s blueprints:') ?></p>
        <textarea rows="10" style="width: 96%;font-family:monospace"><?php echo htmlspecialchars($xml) ?></textarea>
        <?php
    }

    /**
     * @return array<string,array<string,array<int,Blueprint>>>
     * @throws RaceException
     */
    private function resolveList() : array
    {
        $collection = $this->getReader()->getBlueprints();
        $categories = $collection->getCategories();
        $list = array();
        $raceCollection = RaceDefs::getInstance();

        foreach($categories as $category)
        {
            $blueprints = $category->getBlueprints();
            $categoryLabel = $category->getLabel();

            foreach ($blueprints as $blueprint)
            {
                $raceID = $blueprint->getRaceID();

                $raceLabel = t('General');
                if($raceCollection->idExists($raceID)) {
                    $raceLabel = $raceCollection->getByID($raceID)->getLabel();
                }

                if(!isset($list[$raceLabel])) {
                    $list[$raceLabel] = array();
                }

                if(!isset($list[$raceLabel][$categoryLabel])) {
                    $list[$raceLabel][$categoryLabel] = array();
                }

                $list[$raceLabel][$categoryLabel][] = $blueprint;
            }
        }

        return $list;
    }
}

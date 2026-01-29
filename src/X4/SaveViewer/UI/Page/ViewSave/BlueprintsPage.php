<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages\ViewSave;

use Mistralys\X4\Database\Blueprints\BlueprintCategory;
use Mistralys\X4\Database\Blueprints\BlueprintDef;
use Mistralys\X4\Database\Blueprints\BlueprintDefs;
use Mistralys\X4\Database\Blueprints\BlueprintSelection;
use Mistralys\X4\Database\Races\RaceDefs;
use Mistralys\X4\Database\Races\RaceException;
use Mistralys\X4\UI\Button;
use Mistralys\X4\UI\Icon;
use Mistralys\X4\UI\Text;
use function AppLocalize\pt;
use function AppLocalize\pts;
use function AppLocalize\t;
use function AppUtils\sb;

class BlueprintsPage extends BaseViewSaveSubPage
{
    public const string URL_NAME = 'Blueprints';
    public const string REQUEST_PARAM_GENERATE_XML = 'generate-xml';
    public const string REQUEST_PARAM_GENERATE_MARKDOWN = 'generate-markdown';
    public const string REQUEST_PARAM_SHOW_TYPE = 'show-type';

    public const string SHOW_TYPE_ALL = 'all';
    public const string SHOW_TYPE_OWNED = 'owned';
    public const string SHOW_TYPE_UNOWNED = 'unowned';
    public const string REQUEST_PARAM_CATEGORY = 'category';
    private string $showType;
    private string $activeID = '';

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

    private ?BlueprintSelection $selection = null;

    public function getSelection() : BlueprintSelection
    {
        if(isset($this->selection)) {
            return $this->selection;
        }

        if($this->showType === self::SHOW_TYPE_OWNED)
        {
            $this->selection = $this->getReader()->getBlueprints()->getOwned();
        }
        else if($this->showType === self::SHOW_TYPE_UNOWNED)
        {
            $this->selection = $this->getReader()->getBlueprints()->getUnowned();
        }
        else
        {
            $this->selection = BlueprintDefs::getInstance()->createSelection();
        }

        return $this->selection;
    }

    public function renderContent() : void
    {
        $this->showType = (string)$this->request->registerParam(self::REQUEST_PARAM_SHOW_TYPE)
            ->setEnum(self::SHOW_TYPE_ALL, self::SHOW_TYPE_OWNED, self::SHOW_TYPE_UNOWNED)
            ->get(self::SHOW_TYPE_ALL);

        $knownBlueprints = BlueprintDefs::getInstance()->createSelection();

        $categories = $knownBlueprints->getCategories();
        $this->activeID = $this->resolveActiveCategoryID();

        if($this->request->getBool(self::REQUEST_PARAM_GENERATE_XML)) {
            $this->renderXML();
            return;
        }

        if($this->request->getBool(self::REQUEST_PARAM_GENERATE_MARKDOWN)) {
            $this->renderMarkdown();
            return;
        }

        $blueprintsCollection = $this->getReader()->getBlueprints();
        $ownedBlueprints = $blueprintsCollection->getOwned();

        ?>
            <nav class="nav nav-pills">
                <a class="nav-link <?php if(empty($this->activeID)) { echo 'active'; } ?>" href="<?php echo $blueprintsCollection->getURLView(array(
                    self::REQUEST_PARAM_SHOW_TYPE => $this->showType
                )) ?>">
                    <?php echo Icon::allItems() ?>
                    <?php pt('All'); ?>
                    <?php echo Text::create(' - '.$this->countAll())->colorMuted(); ?>
                </a>
                <?php
                foreach($categories as $category)
                {
                    ?>
                    <a class="nav-link <?php if($this->activeID === $category->getID()) { echo 'active'; } ?>" href="<?php echo $this->getURL(array(self::REQUEST_PARAM_CATEGORY => $category->getID())) ?>">
                        <?php echo $category->getLabel() ?>
                        <?php echo Text::create(' - '.$this->countByCategory($category))->colorMuted(); ?>
                    </a>
                    <?php
                }
                ?>
            </nav>
            <hr>
            <p>
                <?php
                pts(
                    '%1$s knows a total of %2$s/%3$s blueprints.',
                    $this->getReader()->getPlayer()->getName(),
                    $ownedBlueprints->countBlueprints(),
                    $knownBlueprints->countBlueprints()
                );
                ?>
            </p>
            <p>
                <?php
                $btnAll = Button::create(t('Show all'))
                    ->link($blueprintsCollection->getURLView(array(self::REQUEST_PARAM_CATEGORY => $this->activeID)));

                $btnOwned = Button::create(t('Show owned'))
                    ->link($blueprintsCollection->getURLShowOwned(array(self::REQUEST_PARAM_CATEGORY => $this->activeID)));

                $btnUnowned = Button::create(t('Show unowned'))
                    ->link($blueprintsCollection->getURLShowUnowned(array(self::REQUEST_PARAM_CATEGORY => $this->activeID)));

                if($this->showType === self::SHOW_TYPE_OWNED) {
                    $btnOwned->colorPrimary();
                } else if($this->showType === self::SHOW_TYPE_UNOWNED) {
                    $btnUnowned->colorPrimary();
                } else {
                    $btnAll->colorPrimary();
                }

                echo sb()
                    ->add($btnAll)
                    ->add($btnOwned)
                    ->add($btnUnowned)
                    ->add('&#160;')
                    ->add(Button::create(t('Generate XML'))
                        ->makeOutline()
                        ->link($blueprintsCollection->getURLGenerateXML($this->showType, array(self::REQUEST_PARAM_CATEGORY => $this->activeID)))
                    )
                    ->add(Button::create(t('Generate Markdown'))
                        ->makeOutline()
                        ->link($blueprintsCollection->getURLGenerateMarkdown($this->showType, array(self::REQUEST_PARAM_CATEGORY => $this->activeID)))
                    );
                ?>
            </p>
            <br>
        <?php

        foreach($categories as $category)
        {
            if(!empty($this->activeID) && $category->getID() !== $this->activeID) {
                continue;
            }

            if($this->countByCategory($category) === 0) {
                continue;
            }

            ?>
            <h4 id="category-<?php echo $category->getID() ?>"><?php echo $category->getLabel() ?></h4>
            <ul>
                <?php
                $blueprints = $category->getBlueprints();

                foreach ($blueprints as $blueprint)
                {
                    $isOwned = $blueprintsCollection->isOwned($blueprint);

                    $class = 'blueprint-unowned text-warning';
                    if($isOwned) {
                        $class = 'blueprint-owned text-success';
                    }

                    if(!$this->isValid($blueprint)) {
                        continue;
                    }

                    ?>
                    <li class="<?php echo $class ?>">
                        <?php echo $blueprint->getLabel() ?>
                    </li>
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
                    $xml .= '    <blueprint ware="' . $blueprint->getID() . '"/>' . PHP_EOL;
                }

                $xml .= PHP_EOL;
            }
        }

        $xml .= '</blueprints>';

        ?>
            <p><?php pt('XML source for the current blueprint selection:') ?></p>
            <textarea rows="10" style="width: 96%;font-family:monospace"><?php echo htmlspecialchars($xml) ?></textarea>
            <?php $this->displayBackButton() ?>
        <?php
    }

    private function displayBackButton() : void
    {
        ?>
        <p>
            <?php
            echo Button::create(t('Back'))
                ->setIcon(Icon::back())
                ->colorPrimary()
                ->link($this->getReader()->getBlueprints()->getURLView(array(
                    self::REQUEST_PARAM_CATEGORY => $this->activeID,
                    self::REQUEST_PARAM_SHOW_TYPE => $this->showType
                )));
            ?>
        </p>
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
        <?php $this->displayBackButton(); ?>
        <?php
    }

    /**
     * @return array<string,array<string,array<int,BlueprintDef>>>
     * @throws RaceException
     */
    private function resolveList() : array
    {
        $categories = $this->getSelection()->getCategories();
        $list = array();
        $raceCollection = RaceDefs::getInstance();

        foreach($categories as $category)
        {
            if(!empty($this->activeID) && $category->getID() !== $this->activeID) {
                continue;
            }

            $blueprints = $category->getBlueprints();
            $categoryLabel = $category->getLabel();

            foreach ($blueprints as $blueprint)
            {
                if(!$this->isValid($blueprint)) {
                    continue;
                }

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

    private function isValid(BlueprintDef $blueprint) : bool
    {
        $collection = $this->getReader()->getBlueprints();
        $hasBP = $collection->isOwned($blueprint);

        if($this->showType === self::SHOW_TYPE_UNOWNED && $hasBP) {
            return false;
        }

        if($this->showType === self::SHOW_TYPE_OWNED && !$hasBP) {
            return false;
        }

        return true;
    }

    private function countAll() : int
    {
        return $this->getSelection()->countBlueprints();
    }

    private function countByCategory(BlueprintCategory $category) : int
    {
        $blueprints = $category->getBlueprints();
        $total = 0;

        foreach ($blueprints as $blueprint)
        {
            if($this->isValid($blueprint)) {
                $total++;
            }
        }

        return $total;
    }

    private function resolveActiveCategoryID() : string
    {
        $known = BlueprintDefs::getInstance()->createSelection();

        return (string)$this->request->registerParam(self::REQUEST_PARAM_CATEGORY)
            ->setEnum($known->getCategoryIDs())
            ->get('');
    }

    protected function getURLParams() : array
    {
        $params = array();

        if(!empty($this->activeID)) {
            $params[self::REQUEST_PARAM_CATEGORY] = $this->activeID;
        }

        if(!empty($this->showType)) {
            $params[self::REQUEST_PARAM_SHOW_TYPE] = $this->showType;
        }

        return $params;
    }
}

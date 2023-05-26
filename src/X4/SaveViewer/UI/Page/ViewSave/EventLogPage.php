<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages\ViewSave;

use AppUtils\PaginationHelper;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log\Categories\CachedCategories;
use Mistralys\X4\SaveViewer\Data\SaveReader\Log\LogEntry;
use Mistralys\X4\UI\Button;
use Mistralys\X4\UI\Icon;
use Mistralys\X4\UI\Page\BasePage;
use Mistralys\X4\UserInterface\DataGrid\DataGrid;
use Mistralys\X4\UserInterface\DataGrid\GridColumn;
use function AppLocalize\pt;
use function AppLocalize\pts;
use function AppLocalize\t;

class EventLogPage extends SubPage
{
    public const URL_NAME = 'EventLog';
    public const REQUEST_PARAM_CATEGORY = 'category';
    public const REQUEST_PARAM_ANALYZE = 'analyze';
    public const REQUEST_PARAM_PAGE = 'grid-page';
    public const DEFAULT_ITEMS_PER_PAGE = 20;
    public const REQUEST_PARAM_ITEMS_PER_PAGE = 'items_per_page';
    public const REQUEST_PARAM_SEARCH = 'search';

    private DataGrid $grid;
    private GridColumn $cTime;
    private GridColumn $cCategory;
    private GridColumn $cTitle;
    private Log $log;
    private string $activeID;
    private GridColumn $cText;
    private GridColumn $cMoney;
    private ?CachedCategories $categories;
    private int $pageNumber = 1;
    private PaginationHelper $pagination;
    private int $itemsPerPage = self::DEFAULT_ITEMS_PER_PAGE;
    private string $search = '';

    public function isInSubnav() : bool
    {
        return true;
    }

    public function getURLName() : string
    {
        return self::URL_NAME;
    }

    public function getTitle() : string
    {
        return t('Event log');
    }

    public function getSubtitle() : string
    {
        return '';
    }

    public function getAbstract() : string
    {
        return '';
    }

    protected function preRender() : void
    {
        $this->log = $this->getReader()->getLog();

        if($this->request->getBool(self::REQUEST_PARAM_ANALYZE)) {
            $this->handleAnalysis();
            return;
        }

        if(!$this->log->isCacheValid()) {
            return;
        }

        $this->categories = $this->log->loadAnalysisCache();
        $this->pageNumber = $this->resolvePageNumber();
        $this->itemsPerPage = $this->resolveItemsPerPage();
        $this->search = $this->resolveSearchTerms();
        $this->activeID = (string)$this->request
            ->registerParam(self::REQUEST_PARAM_CATEGORY)
            ->setEnum($this->categories->getIDs())
            ->get();

        $this->createGrid();
    }

    private function resolvePageNumber() : int
    {
        $result = (int)$this->request
            ->registerParam(self::REQUEST_PARAM_PAGE)
            ->setInteger()
            ->get(1);

        if($result >= 1) {
            return $result;
        }

        return 1;
    }

    private function resolveItemsPerPage() : int
    {
        $result = (int)$this->request
            ->registerParam(self::REQUEST_PARAM_ITEMS_PER_PAGE)
            ->setInteger()
            ->get(self::DEFAULT_ITEMS_PER_PAGE);

        if($result < 1) {
            $result = 1;
        }

        return $result;
    }

    public function renderContent() : void
    {
        if(!$this->log->isCacheValid()) {
            $this->renderCacheIsInvalid();
            return;
        }

        $categories = $this->categories->getAll();
        $entries = $this->resolveEntries();
        $allActive = empty($this->activeID);

        ?>
        <nav class="nav nav-pills">
            <a class="nav-link <?php if($allActive) { echo 'active'; } ?>" <?php if($allActive) { ?> aria-current="page" <?php } ?> href="<?php echo $this->log->getURL() ?>">
                <?php echo Icon::allItems() ?>
                <?php pt('All') ?>
                <span class="text-secondary"> - <?php echo count($this->categories->getEntries()) ?></span>
            </a>
            <?php
            foreach($categories as $category)
            {
                $url = $this->getURL(array(
                    self::REQUEST_PARAM_CATEGORY => $category->getCategoryID()
                ));

                $active = $category->getCategoryID() === $this->activeID;

                ?>
                <a class="nav-link <?php if($active) { echo 'active'; } ?>" <?php if($active) { ?> aria-current="page" <?php } ?> href="<?php echo $url ?>">
                    <?php echo $category->getLabel() ?>
                    <span class="text-secondary"> - <?php echo $category->countEntries() ?></span>
                </a>
                <?php
            }
            ?>
        </nav>
        <hr>
        <form method="get" class="d-flex">
            <?php
                $hiddens = $this->getURLParams();
                $hiddens[BasePage::REQUEST_PARAM_PAGE] = $this->request->getParam(BasePage::REQUEST_PARAM_PAGE);
                $hiddens[BasePage::REQUEST_PARAM_VIEW] = $this->request->getParam(BasePage::REQUEST_PARAM_VIEW);

                foreach($hiddens as $name => $value)
                {
                    if($name === self::REQUEST_PARAM_SEARCH) {
                        continue;
                    }

                    ?>
                    <input type="hidden" name="<?php echo $name ?>" value="<?php echo $value ?>">
                    <?php
                }
            ?>
            <input name="<?php echo self::REQUEST_PARAM_SEARCH ?>" class="form-control me-1" type="search" placeholder="<?php pt('Search') ?>" aria-label="<?php pt('Search') ?>" value="<?php echo $this->search ?>">
            <button class="btn btn-primary" type="submit"><?php pt('Search') ?></button>
        </form>
        <hr>
        <?php

        foreach($entries as $entry)
        {
            $row = $this->grid->createRow()
                ->setValue($this->cTime, $entry->getTime()->getIntervalStr())
                ->setValue($this->cTitle, $entry->getTitle())
                ->setValue($this->cCategory, $entry->getCategory()->getLabel())
                ->setValue($this->cText, str_replace('[\012]', '<br>', $entry->getText()))
                ->setValue($this->cMoney, $entry->getMoneyPretty());

            $this->grid->addRow($row);
        }

        if(empty($entries)) {
            ?>
            <div class="alert alert-info">
                <?php pt('No log entries found for the current criteria.') ?>
            </div>
            <?php
            return;
        }

        $this->displayPagination();

        $this->grid->display();

        $this->displayPagination();
    }

    private function displayPagination() : void
    {
        $this->pagination->setAdjacentPages(5);

        $numbers = $this->pagination->getPageNumbers();

        if( count($numbers) === 1) {
            return;
        }

        ?>
        <nav class="nav nav-pills">
            <a class="nav-link" title="<?php pt('Jump to the first page'); ?>'" href="<?php echo $this->getGridPageURL(1) ?>">
                <?php echo Icon::first(); ?>
            </a>
            <a class="nav-link" href="<?php echo $this->getGridPageURL($this->pagination->getPreviousPage()) ?>">
                <?php echo Icon::previous(); ?>
                <?php pt('Previous'); ?>
            </a>
            <?php
            foreach($numbers as $number)
            {
                $active = $number === $this->pageNumber;

                $url = $this->getGridPageURL($number);

                ?>
                <a class="nav-link <?php if($active) { echo 'active'; } ?>" href="<?php echo $url ?>">
                    <?php echo $number ?>
                </a>
                <?php
            }
            ?>
            <a class="nav-link" href="<?php echo $this->getGridPageURL($this->pagination->getNextPage()) ?>">
                <?php pt('Next'); ?>
                <?php echo Icon::next(); ?>
            </a>
            <a class="nav-link" title="<?php pt('Jump to the last page'); ?>" href="<?php echo $this->getGridPageURL($this->pagination->getLastPage()) ?>">
                <?php echo Icon::last(); ?>
            </a>
            <span class="text-secondary" style="line-height:2.4rem">
                <?php pt(
                    'Page %1$s/%2$s',
                    $this->pageNumber,
                    $this->pagination->getLastPage()
                ); ?>
            </span>
        </nav>
        <br>
        <?php
    }

    protected function getURLParams() : array
    {
        $params['save'] = $this->getSave()->getSaveID();

        if(isset($this->activeID))
        {
            $params[self::REQUEST_PARAM_CATEGORY] = $this->activeID;
        }

        if(isset($this->itemsPerPage))
        {
            $params[self::REQUEST_PARAM_ITEMS_PER_PAGE] = $this->itemsPerPage;
        }

        return $params;
    }

    private function getGridPageURL(int $page) : string
    {
        return $this->log->getURL(array(
            self::REQUEST_PARAM_PAGE => $page
        ));
    }

    /**
     * @return LogEntry[]
     */
    private function resolveEntries() : array
    {
        if(!empty($this->activeID)) {
            $category = $this->categories->getByID($this->activeID);
            $entries = $category->getEntries();
        }
        else
        {
            $entries = $this->categories->getEntries();
        }

        if(!empty($this->search)) {
            $entries = $this->filterEntries($entries);
        }

        $this->pagination = new PaginationHelper(
            count($entries),
            $this->itemsPerPage,
            $this->pageNumber
        );

        return array_slice(
            $entries,
            $this->pagination->getOffsetStart(),
            self::DEFAULT_ITEMS_PER_PAGE
        );
    }

    /**
     * @param LogEntry[] $entries
     * @return LogEntry[]
     */
    private function filterEntries(array $entries) : array
    {
        $result = array();

        foreach($entries as $entry)
        {
            $haystack = $entry->getText().$entry->getTitle();

            if(stripos($haystack, $this->search) !== false) {
                $result[] = $entry;
            }
        }

        return $result;
    }

    private function createGrid() : void
    {
        $this->grid = $this->page->getUI()->createDataGrid();

        $this->cTime = $this->grid->addColumn('time', t('How long ago?'));
        $this->cCategory = $this->grid->addColumn('category', t('Category'));
        $this->cTitle = $this->grid->addColumn('title', t('Title'));
        $this->cText = $this->grid->addColumn('text', t('Message'));
        $this->cMoney = $this->grid->addColumn('money', t('Money'))->alignRight();
    }

    private function renderCacheIsInvalid() : void
    {
        ?>
        <div class="alert alert-warning">
            <strong><?php pt('The log has not been analyzed yet.'); ?></strong>
        </div>
        <p>
            <?php
                pt('To enable the log browser, an analysis will automatically categorize the log entries to make them easier to browse.');
            ?>
        </p>
        <p>
            <?php
                pts('Note:');
                pts('This can take a while depending on the log size.');
            ?>
        </p>
        <p>
            <?php
                Button::create(t('Analyze now'))
                    ->setIcon(Icon::analyze())
                    ->colorPrimary()
                    ->link($this->log->getURL(array(
                        self::REQUEST_PARAM_ANALYZE => 'yes'
                    )))
                    ->display();
            ?>
        </p>
        <?php
    }

    private function handleAnalysis() : void
    {
        $this->log->generateAnalysisCache();

        $this->sendRedirect($this->log->getURL());
    }

    private function resolveSearchTerms() : string
    {
        return (string)$this->request->getFilteredParam(self::REQUEST_PARAM_SEARCH);
    }
}

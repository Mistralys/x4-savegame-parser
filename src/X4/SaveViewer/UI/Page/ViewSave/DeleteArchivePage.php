<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\UI\Pages\ViewSave;

use Mistralys\X4\SaveViewer\Data\ArchivedSave;
use Mistralys\X4\SaveViewer\UI\RedirectException;
use Mistralys\X4\UI\Button;
use Mistralys\X4\UI\Icon;
use Mistralys\X4\UI\Text;
use function AppLocalize\pt;
use function AppLocalize\pts;
use function AppLocalize\t;
use function AppUtils\sb;

class DeleteArchivePage extends SubPage
{
    public const URL_NAME = 'DeleteArchive';
    public const REQUEST_PARAM_CONFIRM = 'confirm';

    public function isInSubnav() : bool
    {
        return false;
    }

    public function getURLName() : string
    {
        return self::URL_NAME;
    }

    public function getTitle() : string
    {
        return t('Delete a save archive');
    }

    public function getSubtitle() : string
    {
        return '';
    }

    public function getAbstract() : string
    {
        return (string)sb()
            ->t('Allows deleting a previously archived savegame.')
            ->noteBold()
            ->t('Original savegames cannot be deleted.');
    }

    protected function preRender() : void
    {
        if($this->request->getBool(self::REQUEST_PARAM_CONFIRM)) {
            $this->handleDeleteArchive();
        }
    }

    public function renderContent() : void
    {
        $save = $this->getSave();

        if(!$save instanceof ArchivedSave)
        {
            ?>
            <div class="alert alert-warning" role="alert">
                <strong><?php pt('Only archived saves may be deleted.') ?></strong>
            </div>
            <?php
            return;
        }

        ?>
        <p>
            <strong><?php echo Text::create(t('This will delete the archived savegame files.'))->colorDanger() ?></strong>
        </p>
        <p>
            <?php pts('Are you sure?'); pts('This cannot be undone.'); ?>
        </p>
        <p>
            <?php
                echo Button::create(t('Delete now'))
                    ->colorDanger()
                    ->setIcon(Icon::delete())
                    ->link($save->getURLDelete(array(self::REQUEST_PARAM_CONFIRM => 'yes')));
            ?>
        </p>
        <?php
    }

    private function handleDeleteArchive() : void
    {
        $save = $this->getSave();

        if(!$save instanceof ArchivedSave)
        {
            return;
        }

        $manager = $this->page->getApplication()->getSaveManager();

        $save->deleteArchive();

        $this->sendRedirect($manager->getURLSavesArchive());
    }
}

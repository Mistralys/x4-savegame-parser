<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer;

use AppUtils\FileHelper;
use Mistralys\X4\UI\UserInterface;
use Mistralys\X4\X4Application;
use Mistralys\X4\SaveViewer\UI\Pages\CreateBackup;
use Mistralys\X4\SaveViewer\UI\Pages\SavesList;
use Mistralys\X4\SaveViewer\UI\Pages\UnpackSave;
use Mistralys\X4\SaveViewer\UI\Pages\ViewSave;

class SaveViewer extends X4Application
{
    public function getTitle() : string
    {
        return 'X4 Save game viewer';
    }

    public function registerPages(UserInterface $ui) : void
    {
        $ui->registerPage(SavesList::URL_NAME, SavesList::class);
        $ui->registerPage(CreateBackup::URL_NAME, CreateBackup::class);
        $ui->registerPage(ViewSave::URL_NAME, ViewSave::class);
        $ui->registerPage(UnpackSave::URL_NAME, UnpackSave::class);
    }

    public function getDefaultPageID() : ?string
    {
        return SavesList::URL_NAME;
    }

    public function getVersion() : string
    {
        return FileHelper::readContents(__DIR__.'/../../../VERSION');
    }
}

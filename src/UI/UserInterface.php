<?php

declare(strict_types=1);

namespace Mistralys\X4Saves\UI;

use AppUtils\Request;
use Mistralys\X4Saves\UI\Pages\CreateBackup;
use Mistralys\X4Saves\UI\Pages\SavesList;
use Mistralys\X4Saves\UI\Pages\UnpackSave;
use Mistralys\X4Saves\UI\Pages\ViewSave;

class UserInterface
{
    private string $title = 'X4 Foundations: SaveGame viewer';

    private Page $activePage;

    private Request $request;

    private array $pages = array(
        CreateBackup::URL_NAME => CreateBackup::class,
        ViewSave::URL_NAME => ViewSave::class,
        UnpackSave::URL_NAME => UnpackSave::class
    );

    public function __construct()
    {
        $this->request = new Request();
        $this->activePage = $this->detectPage();
    }

    private function detectPage() : Page
    {
        $pageID = $this->request->getParam('page');

        if(isset($this->pages[$pageID]))
        {
            $class = $this->pages[$pageID];
            return new $class();
        }

        return new SavesList();
    }

    public function display()
    {
        $content = $this->activePage->render();

?><!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title><?php echo $this->title ?></title>

        <!-- Bootstrap core CSS -->
        <link rel="stylesheet" href="vendor/twbs/bootstrap/dist/css/bootstrap.css">
        <link rel="stylesheet" href="vendor/fortawesome/font-awesome/css/fontawesome.css">
        <link rel="stylesheet" href="vendor/fortawesome/font-awesome/css/solid.css">
        <link rel="stylesheet" href="css/ui.css">
    </head>
    <body>
        <nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
            <a class="navbar-brand col-md-3 col-lg-2 mr-0 px-3" href="?"><?php echo $this->title ?></a>
            <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-toggle="collapse" data-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </nav>

        <div class="container-fluid">
            <div class="row">

                <?php
                    $items = $this->activePage->getNavItems();

                    if(!empty($items))
                    {
                        $this->displayNavigation($items);
                    }
                ?>

                <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2"><?php echo $this->activePage->getTitle() ?></h1>
                    </div>

                    <?php echo $content; ?>
                </main>
            </div>
        </div>

        <script src="vendor/components/jquery/jquery.slim.js"></script>
        <script src="vendor/twbs/bootstrap/dist/js/bootstrap.js"></script>
    </body>
</html><?php
    }

    private function displayNavigation(array $items) : void
    {
        ?>
        <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
            <div class="sidebar-sticky pt-3">
                <ul class="nav flex-column">
                    <?php
                        foreach($items as $item)
                        {
                            ?>
                            <li class="nav-item">
                                <a class="nav-link active" href="<?php echo $item['url'] ?>">
                                    <?php echo $item['label'] ?>
                                </a>
                            </li>
                            <?php
                        }
                    ?>
                </ul>
            </div>
        </nav>

        <?php
    }
}

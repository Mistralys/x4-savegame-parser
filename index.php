<?php

declare(strict_types=1);

use Mistralys\X4\SaveViewer\SaveViewer;
use Mistralys\X4\UI\UserInterface;

require_once 'vendor/autoload.php';
require_once 'config.php';

$ui = new UserInterface(new SaveViewer());
$ui->display();

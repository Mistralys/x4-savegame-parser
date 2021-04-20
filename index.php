<?php

use Mistralys\X4Saves\UI\UserInterface;

require_once 'vendor/autoload.php';
require_once 'config.php';

$ui = new UserInterface();
$ui->display();

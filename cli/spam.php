<?php

include __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;

$app = new Application();

$app->add(new Spam\Installer);
$app->add(new Spam\Linker);
$app->add(new Spam\Unlinker);
$app->add(new Spam\Switcher);
// $app->add(new Spam\LogViewer);

$app->run();
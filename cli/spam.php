<?php

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    // within project
    include __DIR__ . '/../vendor/autoload.php';
} else {
    // within composer
    include __DIR__ . '/../../../autoload.php';
}

use Symfony\Component\Console\Application;

$app = new Application();

$app->add(new Spam\Installer);
$app->add(new Spam\Linker);
$app->add(new Spam\Unlinker);
$app->add(new Spam\Switcher);
// $app->add(new Spam\LogViewer);

$app->run();
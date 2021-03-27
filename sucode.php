#!/usr/bin/env php
<?php
// application.php

require __DIR__.'/vendor/autoload.php';

use App\Command\AddCommand;
use App\Command\DiffCommand;
use App\Command\DiffFullCommand;
use App\Command\InitCommand;
use App\Command\ZipperCommand;
use Symfony\Component\Console\Application;

$application = new Application();

// ... register commands
$application->add(new InitCommand());
$application->add(new AddCommand());
$application->add(new DiffCommand());
$application->add(new DiffFullCommand());
$application->add(new ZipperCommand());

$application->run();
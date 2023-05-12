#!/usr/bin/env php
<?php
require __DIR__.'/vendor/autoload.php';

use App\Command\BuildCommand;
use Symfony\Component\Console\Application;

$application = new Application('build', '1.0.0');
$command = new BuildCommand();

$application->add($command);

$application->setDefaultCommand($command->getName(), true);
$application->run();

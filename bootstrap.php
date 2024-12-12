<?php

use Eccube2\Console\Application;

if (class_exists('\Eccube2\Console\Application')) {
    require __DIR__.'/src/Command/GenerateDummyDataCommand.php';
    Application::appendConfigPath(__DIR__.'/config');
}

class_exists('\Eccube2\Tests\Fixture\Generator');

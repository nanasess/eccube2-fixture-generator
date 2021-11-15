<?php

use Eccube2\Console\Application;

Application::appendConfigPath(__DIR__.'/config');

\class_exists('\Eccube2\Tests\Fixture\Generator');

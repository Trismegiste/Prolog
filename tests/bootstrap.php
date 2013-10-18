<?php

define('FIXTURES_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'WAM_TestCase.php';

/*
 * bootstrapping the test suite with composer
 */

if (!$loader = @include __DIR__ . '/../vendor/autoload.php') {
    die('You must set up the project dependencies, run the following commands:' . PHP_EOL .
            'curl -s http://getcomposer.org/installer | php' . PHP_EOL .
            'php composer.phar install --dev' . PHP_EOL);
}

$loader->add('tests\\', dirname(__DIR__));
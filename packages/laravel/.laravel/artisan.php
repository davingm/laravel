<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Symfony\Component\Console\Input\ArgvInput;

define('LARAVEL_START', microtime(true));

$projectRoot = dirname(__DIR__);

require $projectRoot.'/vendor/autoload.php';

/** @var Application $app */
$app = require $projectRoot.'/bootstrap/app.php';

exit($app->handleCommand(new ArgvInput()));

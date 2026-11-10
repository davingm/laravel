<?php

declare(strict_types=1);

$autoload = dirname(__DIR__).'/vendor/autoload.php';

if (! is_file($autoload)) {
    throw new RuntimeException('Devtools test dependencies are missing. Run composer install in packages/devtools.');
}

require $autoload;

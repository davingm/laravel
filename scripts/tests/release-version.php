<?php

declare(strict_types=1);

require dirname(__DIR__).DIRECTORY_SEPARATOR.'release.php';

$checks = [
    ReleaseVersion::latest(['v1.9.9', 'v1.10.0', 'v1.2.0-alpha']) === 'v1.10.0',
    ReleaseVersion::latest(['v1.2.0-alpha']) === null,
    ReleaseVersion::bump('v1.2.3', 'patch') === 'v1.2.4',
    ReleaseVersion::bump('v1.2.3', 'minor') === 'v1.3.0',
    ReleaseVersion::bump('v1.2.3', 'major') === 'v2.0.0',
    ReleaseVersion::bump(null, 'patch') === 'v0.0.1',
    ReleaseVersion::normalize('1.2.3') === 'v1.2.3',
    ReleaseVersion::compare('v1.10.0', 'v1.9.9') > 0,
    ReleaseVersion::hasTag(['1.2.3'], 'v1.2.3'),
];

foreach ($checks as $index => $passed) {
    if (! $passed) {
        fwrite(STDERR, 'Release version check '.($index + 1)." failed.\n");
        exit(1);
    }
}

try {
    ReleaseVersion::normalize('v1.2');
    fwrite(STDERR, "Invalid semantic version was accepted.\n");
    exit(1);
} catch (InvalidArgumentException) {
}

fwrite(STDOUT, 'Release version checks passed.'.PHP_EOL);

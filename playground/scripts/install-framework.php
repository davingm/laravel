<?php

declare(strict_types=1);

$projectRoot = getcwd();

if ($projectRoot === false) {
    fwrite(STDERR, "Unable to determine the application directory.\n");
    exit(1);
}

$sourceCandidates = [
    $projectRoot.DIRECTORY_SEPARATOR.'packages'.DIRECTORY_SEPARATOR.'laravel'.DIRECTORY_SEPARATOR.'.laravel',
    dirname($projectRoot).DIRECTORY_SEPARATOR.'packages'.DIRECTORY_SEPARATOR.'laravel'.DIRECTORY_SEPARATOR.'.laravel',
];

$source = null;

foreach ($sourceCandidates as $candidate) {
    if (is_dir($candidate) && ! is_link($candidate)) {
        $source = $candidate;
        break;
    }
}

if ($source === null) {
    fwrite(STDERR, "Framework source was not found in packages/laravel/.laravel.\n");
    exit(1);
}

$destination = $projectRoot.DIRECTORY_SEPARATOR.'.laravel';

/** Copy source files while leaving generated application data untouched. */
function copyFrameworkDirectory(string $source, string $destination): void
{
    if (! is_dir($destination) && ! mkdir($destination, 0777, true) && ! is_dir($destination)) {
        throw new RuntimeException("Unable to create directory: {$destination}");
    }

    foreach (new DirectoryIterator($source) as $entry) {
        if ($entry->isDot()) {
            continue;
        }

        $name = $entry->getFilename();

        if (in_array($name, ['cache', 'generated', 'node_modules'], true)) {
            continue;
        }

        $sourcePath = $entry->getPathname();
        $destinationPath = $destination.DIRECTORY_SEPARATOR.$name;

        if ($entry->isDir()) {
            copyFrameworkDirectory($sourcePath, $destinationPath);
        } elseif (! copy($sourcePath, $destinationPath)) {
            throw new RuntimeException("Unable to copy framework file: {$sourcePath}");
        }
    }
}

try {
    copyFrameworkDirectory($source, $destination);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage()."\n");
    exit(1);
}

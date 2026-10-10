<?php

declare(strict_types=1);

$projectRoot = getcwd();

if ($projectRoot === false) {
    fwrite(STDERR, "Unable to determine the project directory.\n");
    exit(1);
}

$playgroundPath = $projectRoot.DIRECTORY_SEPARATOR.'playground';

if (is_link($playgroundPath) || ! is_dir($playgroundPath)) {
    fwrite(STDERR, "The playground directory was not found.\n");
    exit(1);
}

$playgroundManifest = $playgroundPath.DIRECTORY_SEPARATOR.'composer.json';

if (! is_file($playgroundManifest)) {
    fwrite(STDERR, "The playground Composer manifest was not found.\n");
    exit(1);
}

/**
 * Copy the contents of the playground into the Composer project root.
 */
function copyDirectoryContents(string $source, string $destination): void
{
    if (! is_dir($destination) && ! mkdir($destination, 0777, true) && ! is_dir($destination)) {
        throw new RuntimeException("Unable to create directory: {$destination}");
    }

    foreach (new DirectoryIterator($source) as $entry) {
        if ($entry->isDot()) {
            continue;
        }

        $sourcePath = $entry->getPathname();
        $destinationPath = $destination.DIRECTORY_SEPARATOR.$entry->getFilename();

        if ($entry->isDir()) {
            copyDirectoryContents($sourcePath, $destinationPath);

            continue;
        }

        if (realpath($sourcePath) === realpath($destinationPath)) {
            continue;
        }

        if (! copy($sourcePath, $destinationPath)) {
            throw new RuntimeException("Unable to copy file: {$sourcePath}");
        }
    }
}

/**
 * Remove a directory tree without following symbolic links.
 */
function removeDirectoryTree(string $directory): void
{
    foreach (new DirectoryIterator($directory) as $entry) {
        if ($entry->isDot()) {
            continue;
        }

        $path = $entry->getPathname();

        if ($entry->isLink() || ! $entry->isDir()) {
            if (! unlink($path)) {
                throw new RuntimeException("Unable to remove file: {$path}");
            }

            continue;
        }

        removeDirectoryTree($path);

        if (! rmdir($path)) {
            throw new RuntimeException("Unable to remove directory: {$path}");
        }
    }
}

try {
    copyDirectoryContents($playgroundPath, $projectRoot);

    $applicationManifestPath = $projectRoot.DIRECTORY_SEPARATOR.'composer.json';
    $applicationManifest = json_decode(file_get_contents($applicationManifestPath), true, flags: JSON_THROW_ON_ERROR);

    if (($applicationManifest['name'] ?? null) !== 'auto/laravel' || ($applicationManifest['type'] ?? null) !== 'project') {
        throw new RuntimeException('The playground files did not replace the bootstrap Composer manifest.');
    }

    foreach (['artisan', 'bootstrap'.DIRECTORY_SEPARATOR.'app.php', 'package.json'] as $requiredFile) {
        if (! is_file($projectRoot.DIRECTORY_SEPARATOR.$requiredFile)) {
            throw new RuntimeException("The materialized project is missing a required file: {$requiredFile}");
        }
    }

    removeDirectoryTree($playgroundPath);

    if (! rmdir($playgroundPath)) {
        throw new RuntimeException("Unable to remove the playground directory: {$playgroundPath}");
    }

    $installerPath = $projectRoot.DIRECTORY_SEPARATOR.'.materialize-playground.php';

    if (is_file($installerPath) && ! unlink($installerPath)) {
        throw new RuntimeException("Unable to remove the installer file: {$installerPath}");
    }
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage()."\n");
    exit(1);
}

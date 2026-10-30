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
function copyDirectoryContents(string $source, string $destination, string $playgroundPath): void
{
    if (! is_dir($destination) && ! mkdir($destination, 0777, true) && ! is_dir($destination)) {
        throw new RuntimeException("Unable to create directory: {$destination}");
    }

    foreach (new DirectoryIterator($source) as $entry) {
        if ($entry->isDot()) {
            continue;
        }

        if (realpath($source) === realpath($playgroundPath)
            && $entry->isDir()
            && in_array($entry->getFilename(), ['scripts', 'tests'], true)) {
            continue;
        }

        $sourcePath = $entry->getPathname();
        $destinationPath = $destination.DIRECTORY_SEPARATOR.$entry->getFilename();

        if ($entry->isDir()) {
            copyDirectoryContents($sourcePath, $destinationPath, $playgroundPath);

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
    copyDirectoryContents($playgroundPath, $projectRoot, $playgroundPath);

    $applicationManifestPath = $projectRoot.DIRECTORY_SEPARATOR.'composer.json';
    $applicationManifest = json_decode(file_get_contents($applicationManifestPath), true, flags: JSON_THROW_ON_ERROR);

    if (($applicationManifest['name'] ?? null) !== 'davingm/laravel' || ($applicationManifest['type'] ?? null) !== 'project') {
        throw new RuntimeException('The playground files did not replace the bootstrap Composer manifest.');
    }

    // Keep runtime installation guaranteed even if a published playground
    // manifest was packaged before this dependency was added there.
    $applicationManifest['require'] ??= [];
    // The monorepo playground uses a local path repository. Generated starter
    // applications consume the released package from Packagist instead.
    $applicationManifest['require']['davingm/laravel-runtime'] = '^1.0.3';
    $applicationManifest['config'] ??= [];
    $applicationManifest['config']['allow-plugins'] ??= [];
    $applicationManifest['config']['allow-plugins']['davingm/laravel-runtime'] = true;

    // The path repository is only for monorepo development. Installed apps
    // resolve the runtime plugin from Packagist and do not contain packages/.
    unset($applicationManifest['repositories']);

    if (file_put_contents($applicationManifestPath, json_encode($applicationManifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL) === false) {
        throw new RuntimeException('Unable to write the installed application Composer manifest.');
    }

    // Patch package.json: replace any local file: paths with published npm versions.
    $packageManifestPath = $projectRoot.DIRECTORY_SEPARATOR.'package.json';
    if (is_file($packageManifestPath)) {
        $packageManifest = json_decode(file_get_contents($packageManifestPath), true, flags: JSON_THROW_ON_ERROR);
        $patched = false;

        foreach (['dependencies', 'devDependencies', 'optionalDependencies'] as $section) {
            if (! isset($packageManifest[$section])) {
                continue;
            }
            foreach ($packageManifest[$section] as $pkg => $version) {
                // Replace local path references with published npm version constraints
                if (str_starts_with((string) $version, 'file:')) {
                    $packageManifest[$section][$pkg] = '^1.0.0';
                    $patched = true;
                }
            }
        }

        if ($patched) {
            if (file_put_contents($packageManifestPath, json_encode($packageManifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL) === false) {
                throw new RuntimeException('Unable to write the installed application package.json.');
            }
        }
    }

    foreach (['.gitignore', 'bootstrap'.DIRECTORY_SEPARATOR.'app.php', 'package.json'] as $requiredFile) {
        if (! is_file($projectRoot.DIRECTORY_SEPARATOR.$requiredFile)) {
            throw new RuntimeException("The materialized project is missing a required file: {$requiredFile}");
        }
    }

    $applicationGitignore = file_get_contents($projectRoot.DIRECTORY_SEPARATOR.'.gitignore');

    if (! is_string($applicationGitignore) || ! preg_match('/^\/\.laravel\/\r?$/m', $applicationGitignore)) {
        throw new RuntimeException('The materialized application .gitignore must ignore the generated .laravel runtime directory.');
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

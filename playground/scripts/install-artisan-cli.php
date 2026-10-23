<?php

declare(strict_types=1);

$npm = PHP_OS_FAMILY === 'Windows' ? 'npm.cmd' : 'npm';

/** Run a fixed npm command and return its exit status. */
function runNpm(string $npm, array $arguments, bool $inheritOutput = false): int
{
    $command = escapeshellarg($npm).' '.implode(' ', array_map('escapeshellarg', $arguments));
    $descriptors = $inheritOutput
        ? [0 => STDIN, 1 => STDOUT, 2 => STDERR]
        : [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];

    $process = proc_open($command, $descriptors, $pipes);

    if (! is_resource($process)) {
        return 127;
    }

    if (! $inheritOutput) {
        fclose($pipes[0]);
        stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
    }

    return proc_close($process);
}

/** Return the installed global package version, if npm can report one. */
function globalPackageVersion(string $npm, string $package): ?string
{
    $command = escapeshellarg($npm).' '.implode(' ', array_map('escapeshellarg', [
        'list', '--global', '--depth=0', '--json', $package,
    ]));
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

    if (! is_resource($process)) {
        return null;
    }

    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    $manifest = json_decode($output, true);
    $version = $manifest['dependencies'][$package]['version'] ?? null;

    return is_string($version) ? $version : null;
}

$package = '@davingm/laravel-cli';
$minimumVersion = '1.0.0';
$installedVersion = globalPackageVersion($npm, $package);

if ($installedVersion !== null && version_compare($installedVersion, $minimumVersion, '>=')) {
    fwrite(STDOUT, "Global {$package} {$installedVersion} is already installed; skipping installation.\n");
    exit(0);
}

fwrite(STDOUT, "Installing the global artisan command ({$package})...\n");

if (runNpm($npm, ['install', '--global', $package], inheritOutput: true) !== 0) {
    fwrite(STDERR, "Unable to install {$package} globally. Install Node.js/npm and run `npm install --global {$package}` manually.\n");
    exit(0);
}

if (($installedVersion = globalPackageVersion($npm, $package)) === null) {
    fwrite(STDERR, "npm reported success, but {$package} was not found in the global package list. Check npm's global installation and PATH.\n");

    exit(0);
}

if (version_compare($installedVersion, $minimumVersion, '<')) {
    fwrite(STDERR, "npm currently provides {$package} {$installedVersion}, which is a placeholder and has no usable artisan launcher. Approve its staged release on npmjs.com, then run `npm install --global {$package}`.\n");

    exit(0);
}

fwrite(STDOUT, "Global artisan command installed. Open a new terminal if `artisan` is not found in this one.\n");

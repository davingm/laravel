#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Bundle the framework playground into a distributable zip archive.
 *
 * Usage (from monorepo root):
 *   php scripts/bundle.php --version=1.0.0
 *
 * Output:
 *   .devcontainer/bundle/laravel-betav{version}.zip
 *
 * The zip contains a clean, materialized copy of playground/ — no vendor/,
 * no node_modules/, no .laravel/ cache. Extract anywhere and run:
 *   composer run setup
 */

// ── Helpers ───────────────────────────────────────────────────────────────

function abort(string $message, int $code = 1): never
{
    fwrite(STDERR, "\033[31mError:\033[0m {$message}\n");
    exit($code);
}

function info(string $message): void
{
    echo "\033[36m→\033[0m {$message}\n";
}

function ok(string $message): void
{
    echo "\033[32m✓\033[0m {$message}\n";
}

/**
 * Directories (relative to playground root) to skip entirely during copy.
 * Skipping at copy time is far faster than copying then deleting.
 */
const SKIP_DIRS = [
    'node_modules',
    'vendor',
    '.laravel',
    'public/build',
    'public/hot',
    'public/storage',
    '.phpunit.cache',
];

/**
 * Files (relative to playground root) to exclude from the bundle.
 */
const SKIP_FILES = [
    '.env',
    '.env.preview',
    '.env.production',
    'composer.lock',
    'package-lock.json',
    '.phpunit.result.cache',
    'auth.json',
];

function copyTree(string $source, string $dest, string $rootSource = ''): void
{
    if ($rootSource === '') {
        $rootSource = realpath($source);
    }
    if (! is_dir($dest) && ! mkdir($dest, 0755, true) && ! is_dir($dest)) {
        abort("Unable to create directory: {$dest}");
    }

    foreach (new DirectoryIterator($source) as $entry) {
        if ($entry->isDot()) {
            continue;
        }

        $name       = $entry->getFilename();
        $sourcePath = $entry->getPathname();
        $destPath   = $dest.DIRECTORY_SEPARATOR.$name;
        $rel        = str_replace('\\', '/', ltrim(str_replace($rootSource, '', realpath($sourcePath)), '/\\'));

        // Skip directories on the blocklist
        if ($entry->isDir()) {
            foreach (SKIP_DIRS as $skip) {
                if ($rel === $skip || str_starts_with($rel, $skip.'/')) {
                    continue 2;
                }
            }
            copyTree($sourcePath, $destPath, $rootSource);
            continue;
        }

        // Skip files on the blocklist
        if (in_array($rel, SKIP_FILES, true)) {
            continue;
        }

        if (! copy($sourcePath, $destPath)) {
            abort("Unable to copy: {$sourcePath}");
        }
    }
}

function deleteTree(string $path): void
{
    if (! file_exists($path)) {
        return;
    }
    if (is_file($path) || is_link($path)) {
        unlink($path);
        return;
    }
    foreach (new DirectoryIterator($path) as $entry) {
        if ($entry->isDot()) {
            continue;
        }
        $p = $entry->getPathname();
        $entry->isDir() && ! $entry->isLink() ? deleteTree($p) : unlink($p);
    }
    rmdir($path);
}

function ensureDir(string $path): void
{
    if (! is_dir($path) && ! mkdir($path, 0755, true) && ! is_dir($path)) {
        abort("Unable to create directory: {$path}");
    }
}

/**
 * Create a zip archive from a directory.
 * Tries ZipArchive first, then PowerShell (Windows), then the `zip` CLI.
 */
function createZip(string $sourceDir, string $zipPath): void
{
    // Strategy 1: PHP ZipArchive extension
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort("ZipArchive could not open: {$zipPath}");
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($it as $file) {
            if (! $file->isFile()) {
                continue;
            }
            $rel = substr(str_replace('\\', '/', $file->getRealPath()), strlen(str_replace('\\', '/', $sourceDir)) + 1);
            $zip->addFile($file->getRealPath(), $rel);
        }
        $zip->close();
        return;
    }

    // Strategy 2: PowerShell Compress-Archive (Windows)
    if (PHP_OS_FAMILY === 'Windows') {
        $src = str_replace('/', '\\', $sourceDir);
        $dst = str_replace('/', '\\', $zipPath);
        $cmd = "powershell -NoProfile -Command \"Compress-Archive -Force -Path '{$src}\\*' -DestinationPath '{$dst}'\"";
        $ret = 0;
        passthru($cmd, $ret);
        if ($ret !== 0) {
            abort("PowerShell Compress-Archive failed (exit {$ret}).");
        }
        return;
    }

    // Strategy 3: unix zip CLI
    $cmd = sprintf('cd %s && zip -rq %s .', escapeshellarg($sourceDir), escapeshellarg($zipPath));
    $ret = 0;
    passthru($cmd, $ret);
    if ($ret !== 0) {
        abort("zip CLI failed (exit {$ret}).");
    }
}

// ── Parse arguments ────────────────────────────────────────────────────────

$opts    = getopt('', ['version:']);
$version = $opts['version'] ?? null;

if ($version === null || ! preg_match('/^\d+\.\d+\.\d+$/', $version)) {
    abort("Usage: php scripts/bundle.php --version=X.Y.Z");
}

// ── Paths ──────────────────────────────────────────────────────────────────

$root       = dirname(__DIR__);
$playground = $root.DIRECTORY_SEPARATOR.'playground';
$bundleDir  = $root.DIRECTORY_SEPARATOR.'.devcontainer'.DIRECTORY_SEPARATOR.'bundle';
$tempDir    = sys_get_temp_dir().DIRECTORY_SEPARATOR.'laravel-bundle-'.uniqid();
$zipName    = "laravel-betav{$version}.zip";
$zipPath    = $bundleDir.DIRECTORY_SEPARATOR.$zipName;

if (! is_dir($playground)) {
    abort("Playground directory not found: {$playground}");
}

echo "\n";
echo "  \033[1mdavingm/laravel bundle\033[0m\n";
echo "  Version : betav{$version}\n";
echo "  Output  : .devcontainer/bundle/{$zipName}\n";
echo "\n";

// ── Step 1: Create bundle dir ──────────────────────────────────────────────

ensureDir($bundleDir);

// ── Step 2: Copy playground (skipping heavy dirs) ─────────────────────────

info("Copying playground source...");
copyTree($playground, $tempDir);
ok("Playground copied.");

// ── Step 3: Materialize composer.json ─────────────────────────────────────

info("Materializing composer.json...");
$composerPath = $tempDir.DIRECTORY_SEPARATOR.'composer.json';

if (! is_file($composerPath)) {
    abort("composer.json not found in temp directory.");
}

$composerData = json_decode(file_get_contents($composerPath), true, flags: JSON_THROW_ON_ERROR);
unset($composerData['repositories']);
if (isset($composerData['require']['davingm/laravel-runtime'])) {
    $composerData['require']['davingm/laravel-runtime'] = '^1.0.2';
}
file_put_contents(
    $composerPath,
    json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL
);
ok("composer.json materialized.");

// ── Step 3b: Patch package.json — replace local file: paths ───────────────

$packagePath = $tempDir.DIRECTORY_SEPARATOR.'package.json';
if (is_file($packagePath)) {
    $packageData = json_decode(file_get_contents($packagePath), true, flags: JSON_THROW_ON_ERROR);
    $patched = false;
    foreach (['dependencies', 'devDependencies', 'optionalDependencies'] as $section) {
        if (! isset($packageData[$section])) { continue; }
        foreach ($packageData[$section] as $pkg => $ver) {
            if (str_starts_with((string) $ver, 'file:')) {
                $packageData[$section][$pkg] = '^1.0.0';
                $patched = true;
            }
        }
    }
    if ($patched) {
        file_put_contents(
            $packagePath,
            json_encode($packageData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL
        );
    }
}
ok("package.json patched.");

// ── Step 4: Clean glob patterns ────────────────────────────────────────────

info("Cleaning generated files...");
$globPatterns = [
    'storage/logs/*.log',
    'storage/framework/cache/data/*',
    'storage/framework/sessions/*',
    'storage/framework/testing/*',
    'storage/framework/views/*.php',
    'bootstrap/cache/*.php',
    'app/database/*.sqlite',
    'app/database/*.db',
];

foreach ($globPatterns as $pattern) {
    $full = $tempDir.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $pattern);
    foreach (glob($full) ?: [] as $match) {
        is_dir($match) ? deleteTree($match) : unlink($match);
    }
}
ok("Cleaned.");

// ── Step 5: Ensure .gitkeep in storage subdirs ─────────────────────────────

$keepDirs = [
    'storage/logs',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/testing',
    'storage/framework/views',
    'bootstrap/cache',
];
foreach ($keepDirs as $dir) {
    $full = $tempDir.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $dir);
    ensureDir($full);
    touch($full.DIRECTORY_SEPARATOR.'.gitkeep');
}

// ── Step 6: Create zip ─────────────────────────────────────────────────────

info("Creating zip archive...");
createZip($tempDir, $zipPath);
ok("Zip created.");

// ── Step 7: Cleanup temp ───────────────────────────────────────────────────

info("Cleaning up temp directory...");
deleteTree($tempDir);
ok("Done.");

// ── Summary ────────────────────────────────────────────────────────────────

$sizeMb = number_format(filesize($zipPath) / 1024 / 1024, 2);

echo "\n";
echo "  \033[32mBundle ready!\033[0m\n";
echo "  File    : .devcontainer/bundle/{$zipName}\n";
echo "  Size    : {$sizeMb} MB\n";
echo "\n";
echo "  To use:\n";
echo "    1. Extract {$zipName} to a new folder\n";
echo "    2. cd <folder> && composer run setup\n";
echo "\n";

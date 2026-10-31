<?php

declare(strict_types=1);

namespace Davingm\LaravelRuntime;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use DirectoryIterator;
use RuntimeException;
use Throwable;

final class RuntimePlugin implements PluginInterface, EventSubscriberInterface
{
    private string $runtimeSource;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->runtimeSource = dirname(__DIR__).DIRECTORY_SEPARATOR.'.laravel';

        // Materialize before Composer writes the optimized autoloader. The
        // application maps Laravel\Generators to files inside this directory.
        $this->installRuntimeDirectory();
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'post-autoload-dump' => ['installRuntime', 1000],
            'post-create-project-cmd' => 'installGlobalCli',
        ];
    }

    public function installGlobalCli(Event $event): void
    {
        $npm = $this->resolveNpmExecutable();
        $package = '@davingm/laravel-cli';
        $minimumVersion = '1.0.0';
        $installedVersion = $this->globalPackageVersion($npm, $package);

        if ($installedVersion !== null && version_compare($installedVersion, $minimumVersion, '>=')) {
            fwrite(STDOUT, "Global {$package} {$installedVersion} is already installed; skipping installation.\n");

            return;
        }

        fwrite(STDOUT, "Installing the global artisan command ({$package})...\n");

        if ($this->runNpm($npm, ['install', '--global', $package], true) !== 0) {
            fwrite(STDERR, "Unable to install {$package} globally. Install Node.js/npm and run `npm install --global {$package}` manually.\n");

            return;
        }

        $installedVersion = $this->globalPackageVersion($npm, $package);
        if ($installedVersion === null) {
            fwrite(STDERR, "npm reported success, but {$package} was not found in the global package list. Check npm's global installation and PATH.\n");

            return;
        }

        if (version_compare($installedVersion, $minimumVersion, '<')) {
            fwrite(STDERR, "npm currently provides {$package} {$installedVersion}, which is a placeholder and has no usable artisan launcher. Approve its staged release on npmjs.com, then run `npm install --global {$package}`.\n");

            return;
        }

        fwrite(STDOUT, "Global artisan command installed. Open a new terminal if `artisan` is not found in this one.\n");
    }

    private function resolveNpmExecutable(): string
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return 'npm';
        }

        foreach (explode(PATH_SEPARATOR, getenv('PATH') ?: '') as $directory) {
            $directory = trim($directory, " \t\n\r\0\x0B\"");
            if ($directory === '') {
                continue;
            }

            $candidate = rtrim($directory, '\\/').DIRECTORY_SEPARATOR.'npm.cmd';
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return 'npm.cmd';
    }

    public function installRuntime(Event $event): void
    {
        $this->installRuntimeDirectory();
    }

    private function globalPackageVersion(string $npm, string $package): ?string
    {
        $arguments = ['list', '--global', '--depth=0', '--json', $package];
        $command = escapeshellarg($npm).' '.implode(' ', array_map('escapeshellarg', $arguments));
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

        $manifest = json_decode((string) $output, true);
        $version = $manifest['dependencies'][$package]['version'] ?? null;

        return is_string($version) ? $version : null;
    }

    private function runNpm(string $npm, array $arguments, bool $inheritOutput = false): int
    {
        $command = escapeshellarg($npm).' '.implode(' ', array_map('escapeshellarg', $arguments));
        $process = proc_open($command, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['redirect', 1],
        ], $pipes);

        if (! is_resource($process)) {
            return 127;
        }

        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        if ($inheritOutput && is_string($output) && $output !== '') {
            fwrite(STDOUT, $output);
        }

        return proc_close($process);
    }

    private function installRuntimeDirectory(): void
    {
        $projectRoot = getcwd();

        if ($projectRoot === false || ! is_dir($this->runtimeSource)) {
            throw new RuntimeException('Laravel runtime source is missing from the installed Composer package.');
        }

        $destination = $projectRoot.DIRECTORY_SEPARATOR.'.laravel';

        try {
            $this->copyRuntimeDirectory($this->runtimeSource, $destination);
        } catch (Throwable $exception) {
            throw new RuntimeException('Unable to install the Laravel runtime directory: '.$exception->getMessage(), previous: $exception);
        }
    }

    private function copyRuntimeDirectory(string $source, string $destination): void
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

            if ($entry->isLink()) {
                throw new RuntimeException("Runtime source cannot contain symbolic links: {$sourcePath}");
            }

            if ($entry->isDir()) {
                $this->copyRuntimeDirectory($sourcePath, $destinationPath);
            } elseif (! copy($sourcePath, $destinationPath)) {
                throw new RuntimeException("Unable to copy runtime file: {$sourcePath}");
            }
        }
    }
}

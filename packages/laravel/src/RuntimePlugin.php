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
        ];
    }

    public function installRuntime(Event $event): void
    {
        $this->installRuntimeDirectory();
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

<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use DirectoryIterator;
use RuntimeException;

final class RuntimePlugin implements PluginInterface, EventSubscriberInterface
{
    private string $source;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->source = dirname(__DIR__).DIRECTORY_SEPARATOR.'.laravel';
        $this->installRuntime();
    }

    public function deactivate(Composer $composer, IOInterface $io): void {}
    public function uninstall(Composer $composer, IOInterface $io): void {}

    public static function getSubscribedEvents(): array
    {
        return ['post-autoload-dump' => ['installRuntime', 1000]];
    }

    public function installRuntime(?Event $event = null): void
    {
        $root = getcwd();
        if ($root === false || !is_dir($this->source)) {
            throw new RuntimeException('Laravel Devtools runtime source is missing.');
        }

        $this->copyDirectory($this->source, $root.DIRECTORY_SEPARATOR.'.laravel');
        $cache = $root.DIRECTORY_SEPARATOR.'.laravel'.DIRECTORY_SEPARATOR.'cache';
        if (!is_dir($cache) && !mkdir($cache, 0777, true) && !is_dir($cache)) {
            throw new RuntimeException("Unable to create runtime cache directory: {$cache}");
        }
    }

    private function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($destination) && !mkdir($destination, 0777, true) && !is_dir($destination)) {
            throw new RuntimeException("Unable to create directory: {$destination}");
        }

        foreach (new DirectoryIterator($source) as $entry) {
            if ($entry->isDot() || in_array($entry->getFilename(), ['node_modules', 'cache', 'generated'], true)) {
                continue;
            }
            $from = $entry->getPathname();
            $to = $destination.DIRECTORY_SEPARATOR.$entry->getFilename();
            if ($entry->isLink()) {
                throw new RuntimeException("Runtime source cannot contain symbolic links: {$from}");
            }
            if ($entry->isDir()) {
                $this->copyDirectory($from, $to);
            } elseif (!copy($from, $to)) {
                throw new RuntimeException("Unable to install runtime file: {$from}");
            }
        }
    }
}

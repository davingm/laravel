<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Crud\Generators;

use Davingm\LaravelDevtools\Crud\Exceptions\ViewGenerationException;
use InvalidArgumentException;
use RuntimeException;

final class ViewWriter
{
    public function __construct(
        private readonly string $basePath,
    ) {
    }

    public function write(string $tableDir, string $viewName, string $content, bool $overwrite = false): string
    {
        if (preg_match('/^[a-zA-Z0-9_-]+$/D', $tableDir) !== 1) {
            throw new InvalidArgumentException("View directory name [{$tableDir}] is invalid.");
        }
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D', $viewName) !== 1) {
            throw new InvalidArgumentException("View name [{$viewName}] is invalid.");
        }

        if (! is_dir($this->basePath) && ! mkdir($this->basePath, 0777, true) && ! is_dir($this->basePath)) {
            throw new RuntimeException("Unable to create views directory [{$this->basePath}].");
        }

        $baseDirectory = realpath($this->basePath);
        if ($baseDirectory === false) {
            throw new RuntimeException("Unable to resolve views directory [{$this->basePath}].");
        }

        $targetDirectory = $baseDirectory.DIRECTORY_SEPARATOR.$tableDir;
        if (! is_dir($targetDirectory) && ! mkdir($targetDirectory, 0777, true) && ! is_dir($targetDirectory)) {
            throw new RuntimeException("Unable to create table views directory [{$targetDirectory}].");
        }

        $resolvedTargetDirectory = realpath($targetDirectory);
        if ($resolvedTargetDirectory === false || dirname($resolvedTargetDirectory) !== $baseDirectory) {
            throw new RuntimeException("Unable to resolve table views directory [{$targetDirectory}].");
        }

        $path = $resolvedTargetDirectory.DIRECTORY_SEPARATOR.$viewName.'.blade.php';
        if (file_exists($path) && ! $overwrite) {
            throw ViewGenerationException::forFileAlreadyExists($path);
        }

        $temporaryPath = tempnam($resolvedTargetDirectory, '.'.$viewName.'-');
        if ($temporaryPath === false) {
            throw new RuntimeException("Unable to create temporary view file in [{$resolvedTargetDirectory}].");
        }

        try {
            $bytesWritten = file_put_contents($temporaryPath, $content, LOCK_EX);
            if ($bytesWritten === false || $bytesWritten !== strlen($content)) {
                throw new RuntimeException("Unable to write complete view content to temporary file [{$temporaryPath}].");
            }

            if (file_exists($path) && ! $overwrite) {
                throw ViewGenerationException::forFileAlreadyExists($path);
            }
            if (! rename($temporaryPath, $path)) {
                throw new RuntimeException("Unable to move generated view into place at [{$path}].");
            }
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }

        return $path;
    }
}

<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Crud\Generators;

use Davingm\LaravelDevtools\Crud\Exceptions\ModelGenerationException;
use InvalidArgumentException;
use RuntimeException;

final class ModelWriter
{
    public function __construct(
        private readonly string $basePath,
    ) {
    }

    public function write(string $className, string $content, bool $overwrite = false): string
    {
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D', $className) !== 1) {
            throw new InvalidArgumentException("Model class name [{$className}] is not a valid PHP identifier.");
        }

        if (! is_dir($this->basePath) && ! mkdir($this->basePath, 0777, true) && ! is_dir($this->basePath)) {
            throw new RuntimeException("Unable to create model directory [{$this->basePath}].");
        }

        $directory = realpath($this->basePath);
        if ($directory === false) {
            throw new RuntimeException("Unable to resolve model directory [{$this->basePath}].");
        }

        $path = $directory.DIRECTORY_SEPARATOR.$className.'.php';
        if (file_exists($path) && ! $overwrite) {
            throw ModelGenerationException::forFileAlreadyExists($path);
        }

        $temporaryPath = tempnam($directory, '.'.$className.'-');
        if ($temporaryPath === false) {
            throw new RuntimeException("Unable to create temporary model file in [{$directory}].");
        }

        try {
            $bytesWritten = file_put_contents($temporaryPath, $content, LOCK_EX);
            if ($bytesWritten === false || $bytesWritten !== strlen($content)) {
                throw new RuntimeException("Unable to write complete model content to temporary file [{$temporaryPath}].");
            }

            if (file_exists($path) && ! $overwrite) {
                throw ModelGenerationException::forFileAlreadyExists($path);
            }

            if (! rename($temporaryPath, $path)) {
                throw new RuntimeException("Unable to move generated model into place at [{$path}].");
            }
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }

        return $path;
    }
}

<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Crud\Generators;

use Davingm\LaravelDevtools\Crud\Data\CrudDefinition;
use Davingm\LaravelDevtools\Crud\Exceptions\RouteManagerException;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class RouteManager
{
    public function __construct(
        private readonly string $routesFilePath,
        private readonly RouteStub $stub = new RouteStub(),
    ) {
    }

    public function ensureResourceRoute(CrudDefinition $definition): RouteRegistrationResult
    {
        if ($definition->primaryKeyIsComposite()) {
            throw RouteManagerException::forCompositePrimaryKey($definition->table, $definition->primaryKeys);
        }
        if ($definition->primaryKey() === null) {
            throw RouteManagerException::forMissingPrimaryKey($definition->table);
        }
        if (str_contains($definition->table, "\n") || str_contains($definition->table, "\r")) {
            throw new InvalidArgumentException('Route table names cannot contain line breaks.');
        }
        if (! file_exists($this->routesFilePath) || ! is_file($this->routesFilePath)) {
            throw RouteManagerException::forRoutesFileNotFound($this->routesFilePath);
        }
        if (! is_writable($this->routesFilePath)) {
            throw RouteManagerException::forRoutesFileNotWritable($this->routesFilePath);
        }

        $originalContent = $this->readRoutesFile();
        $modelName = Str::studly(Str::singular($definition->table));
        $controllerFqcn = '\\App\\Http\\Controllers\\'.$modelName.'Controller';
        $newline = str_contains($originalContent, "\r\n") ? "\r\n" : "\n";
        $block = str_replace(PHP_EOL, $newline, $this->stub->render($definition->table, $controllerFqcn));
        $beginMarker = '// BEGIN auto-crud:'.$definition->table;
        $endMarker = '// END auto-crud:'.$definition->table;
        $beginCount = substr_count($originalContent, $beginMarker);
        $endCount = substr_count($originalContent, $endMarker);

        if ($beginCount > 1 || $endCount > 1 || (($beginCount === 0) !== ($endCount === 0))) {
            throw RouteManagerException::forMalformedMarkerBlock($this->routesFilePath, $definition->table);
        }

        if ($beginCount === 1 && $endCount === 1) {
            $beginPosition = strpos($originalContent, $beginMarker);
            $endPosition = strpos($originalContent, $endMarker);
            if ($beginPosition === false || $endPosition === false || $endPosition < $beginPosition) {
                throw RouteManagerException::forMalformedMarkerBlock($this->routesFilePath, $definition->table);
            }

            $replaceEnd = $endPosition + strlen($endMarker);
            if (substr($originalContent, $replaceEnd, 2) === "\r\n") {
                $replaceEnd += 2;
            } elseif (substr($originalContent, $replaceEnd, 1) === "\n") {
                $replaceEnd++;
            }

            $updatedContent = substr($originalContent, 0, $beginPosition)
                .$block
                .substr($originalContent, $replaceEnd);
            $this->writeAtomically($updatedContent);

            return new RouteRegistrationResult(RouteRegistrationResult::STATUS_UPDATED, $definition->table, $block);
        }

        if ($this->hasManualRoute($originalContent, $definition->table)) {
            return new RouteRegistrationResult(
                RouteRegistrationResult::STATUS_SKIPPED,
                $definition->table,
                $block,
                'manual route detected',
            );
        }

        $insertPosition = $this->closingTagPosition($originalContent);
        if ($insertPosition === null) {
            $prefix = $originalContent;
            $suffix = '';
        } else {
            $prefix = substr($originalContent, 0, $insertPosition);
            $suffix = substr($originalContent, $insertPosition);
        }

        $separator = $prefix !== '' && ! str_ends_with($prefix, "\n") && ! str_ends_with($prefix, "\r")
            ? $newline
            : '';
        $updatedContent = $prefix.$separator.$block.$suffix;
        $this->writeAtomically($updatedContent);

        return new RouteRegistrationResult(RouteRegistrationResult::STATUS_ADDED, $definition->table, $block);
    }

    private function readRoutesFile(): string
    {
        try {
            $content = file_get_contents($this->routesFilePath);
            if ($content === false) {
                throw new RuntimeException('file_get_contents returned false.');
            }

            return $content;
        } catch (Throwable $exception) {
            throw RouteManagerException::forReadFailed($this->routesFilePath, $exception);
        }
    }

    private function hasManualRoute(string $content, string $table): bool
    {
        $quotedTable = preg_quote($table, '~');
        $resourcePattern = '~Route\s*::\s*resource\s*\(\s*([\'\"])'.$quotedTable.'\\1~i';
        $namedRoutePattern = '~->\s*name\s*\(\s*[\'\"]'.$quotedTable.'\\.[^\'\"]*[\'\"]~i';

        return preg_match($resourcePattern, $content) === 1 || preg_match($namedRoutePattern, $content) === 1;
    }

    private function closingTagPosition(string $content): ?int
    {
        $position = strrpos($content, '?>');
        if ($position === false || trim(substr($content, $position + 2)) !== '') {
            return null;
        }

        return $position;
    }

    private function writeAtomically(string $content): void
    {
        $directory = dirname($this->routesFilePath);
        $temporaryPath = null;

        try {
            $temporaryPath = tempnam($directory, '.routes-');
            if ($temporaryPath === false) {
                throw new RuntimeException("Unable to create temporary file in [{$directory}].");
            }

            $bytesWritten = file_put_contents($temporaryPath, $content, LOCK_EX);
            if ($bytesWritten === false || $bytesWritten !== strlen($content)) {
                throw new RuntimeException("Unable to write complete temporary file [{$temporaryPath}].");
            }

            $permissions = fileperms($this->routesFilePath);
            if ($permissions !== false && ! chmod($temporaryPath, $permissions & 0777)) {
                throw new RuntimeException("Unable to preserve routes file permissions for [{$this->routesFilePath}].");
            }

            if (! rename($temporaryPath, $this->routesFilePath)) {
                throw new RuntimeException("Unable to move temporary file into place at [{$this->routesFilePath}].");
            }
        } catch (Throwable $exception) {
            if ($temporaryPath !== null && is_file($temporaryPath)) {
                unlink($temporaryPath);
            }

            throw RouteManagerException::forWriteFailed($this->routesFilePath, $exception);
        }
    }
}

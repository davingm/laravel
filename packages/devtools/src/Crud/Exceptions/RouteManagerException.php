<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Crud\Exceptions;

use RuntimeException;
use Throwable;

final class RouteManagerException extends RuntimeException
{
    /** @param list<string> $columns */
    public static function forCompositePrimaryKey(string $table, array $columns): self
    {
        return new self(sprintf(
            "Cannot manage routes for table '%s': composite primary key (%s) is not supported.",
            $table,
            implode(', ', $columns),
        ));
    }

    public static function forMissingPrimaryKey(string $table): self
    {
        return new self("Cannot manage routes for table '{$table}': no primary key detected.");
    }

    public static function forRoutesFileNotFound(string $path): self
    {
        return new self("Routes file not found: {$path}. Please create it first.");
    }

    public static function forRoutesFileNotWritable(string $path): self
    {
        return new self("Routes file is not writable: {$path}");
    }

    public static function forReadFailed(string $path, Throwable $previous): self
    {
        return new self("Failed to read routes file: {$path}. Reason: {$previous->getMessage()}", 0, $previous);
    }

    public static function forWriteFailed(string $path, Throwable $previous): self
    {
        return new self("Failed to write routes file: {$path}. Reason: {$previous->getMessage()}", 0, $previous);
    }

    public static function forMalformedMarkerBlock(string $path, string $table): self
    {
        return new self("Malformed auto-crud marker block for '{$table}' in {$path}: only BEGIN or only END found. Fix manually before retrying.");
    }
}

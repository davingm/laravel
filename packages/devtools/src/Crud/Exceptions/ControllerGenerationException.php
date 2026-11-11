<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Crud\Exceptions;

use RuntimeException;

final class ControllerGenerationException extends RuntimeException
{
    /** @param list<string> $columns */
    public static function forCompositePrimaryKey(string $table, array $columns): self
    {
        return new self(sprintf(
            "Cannot generate controller for table '%s': composite primary key (%s) is not supported.",
            $table,
            implode(', ', $columns),
        ));
    }

    public static function forMissingPrimaryKey(string $table): self
    {
        return new self("Cannot generate controller for table '{$table}': no primary key detected (required for route model binding).");
    }

    public static function forFileAlreadyExists(string $path): self
    {
        return new self("Refusing to overwrite existing file: {$path}");
    }
}

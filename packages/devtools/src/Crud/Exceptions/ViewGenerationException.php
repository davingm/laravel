<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Crud\Exceptions;

use RuntimeException;

final class ViewGenerationException extends RuntimeException
{
    /** @param list<string> $columns */
    public static function forCompositePrimaryKey(string $table, array $columns): self
    {
        return new self(sprintf(
            "Cannot generate views for table '%s': composite primary key (%s) is not supported.",
            $table,
            implode(', ', $columns),
        ));
    }

    public static function forMissingPrimaryKey(string $table): self
    {
        return new self("Cannot generate views for table '{$table}': no primary key detected.");
    }

    public static function forFileAlreadyExists(string $path): self
    {
        return new self("Refusing to overwrite existing file: {$path}");
    }
}

<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Crud\Data;

final class ColumnDefinition
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly bool $nullable,
        public readonly mixed $default,
        public readonly bool $autoIncrement,
        public readonly bool $primary,
        public readonly ?int $length = null,
        public readonly array $metadata = [],
    ) {
    }
}

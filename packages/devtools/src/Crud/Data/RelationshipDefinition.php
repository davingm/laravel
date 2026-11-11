<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Crud\Data;

final class RelationshipDefinition
{
    public ?string $method = null;
    public ?string $relatedModel = null;
    /**
     * @param list<string> $columns
     * @param list<string> $foreignColumns
     */
    public function __construct(
        public readonly ?string $name,
        public readonly array $columns,
        public readonly string $foreignTable,
        public readonly array $foreignColumns,
        public readonly ?string $onUpdate = null,
        public readonly ?string $onDelete = null,
    ) {
    }

    public function isComposite(): bool
    {
        return count($this->columns) !== 1 || count($this->foreignColumns) !== 1;
    }

    public function supportsEloquentRelation(): bool
    {
        return ! $this->isComposite();
    }

    public function isNamed(): bool
    {
        return $this->method !== null && $this->relatedModel !== null;
    }

    public function assignNames(string $method, string $relatedModel): void
    {
        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D', $method)) {
            throw new \InvalidArgumentException("Relationship method [{$method}] is not a valid PHP identifier.");
        }

        $this->method = $method;
        $this->relatedModel = $relatedModel;
    }
}

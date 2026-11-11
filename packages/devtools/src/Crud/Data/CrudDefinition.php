<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Crud\Data;

final class CrudDefinition
{
    /**
     * @param list<string> $primaryKeys
     * @param list<ColumnDefinition> $columns
     * @param list<RelationshipDefinition> $relationships
     */
    public function __construct(
        public readonly string $table,
        public readonly array $primaryKeys,
        public readonly array $columns,
        public readonly array $relationships,
        public readonly ?string $modelName = null,
    ) {
    }

    public function primaryKey(): ?string
    {
        return count($this->primaryKeys) === 1 ? $this->primaryKeys[0] : null;
    }

    public function primaryKeyIsComposite(): bool
    {
        return count($this->primaryKeys) > 1;
    }

    public function incrementing(): bool
    {
        return $this->primaryColumn()?->autoIncrement ?? false;
    }

    /** Eloquent accepts only `int` or `string` as key type. */
    public function keyType(): ?string
    {
        $column = $this->primaryColumn();
        if ($column === null) {
            return null;
        }
        $type = strtolower($column->type);

        return preg_match('/\b(tinyint|smallint|mediumint|int|integer|bigint)\b/', $type) === 1
            ? 'int'
            : 'string';
    }

    private function primaryColumn(): ?ColumnDefinition
    {
        $key = $this->primaryKey();
        foreach ($this->columns as $column) {
            if ($column->name === $key) {
                return $column;
            }
        }

        return null;
    }
}

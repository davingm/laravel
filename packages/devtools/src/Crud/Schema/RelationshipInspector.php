<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Crud\Schema;

use Davingm\LaravelDevtools\Crud\Data\RelationshipDefinition;
use Illuminate\Support\Str;
use UnexpectedValueException;

final class RelationshipInspector
{
    /**
     * @param list<array<string, mixed>> $foreignKeys
     * @return list<RelationshipDefinition>
     */
    public function inspect(array $foreignKeys): array
    {
        $relationships = [];

        foreach ($foreignKeys as $foreignKey) {
            if (! is_array($foreignKey)) {
                throw new UnexpectedValueException('Invalid foreign key metadata: each constraint must be an array.');
            }

            $columns = $this->stringList($foreignKey['columns'] ?? null);
            $foreignColumns = $this->stringList($foreignKey['foreign_columns'] ?? null);
            $foreignTable = $foreignKey['foreign_table'] ?? null;

            if ($columns === [] || $foreignColumns === [] || ! is_string($foreignTable) || $foreignTable === '') {
                throw new UnexpectedValueException('Invalid foreign key metadata: local columns, referenced columns, and referenced table are required.');
            }
            if (count($columns) !== count($foreignColumns)) {
                throw new UnexpectedValueException("Invalid foreign key metadata for referenced table [{$foreignTable}]: local and referenced column counts differ.");
            }
            foreach (['name', 'on_update', 'on_delete'] as $optionalString) {
                if (array_key_exists($optionalString, $foreignKey) && $foreignKey[$optionalString] !== null && ! is_string($foreignKey[$optionalString])) {
                    throw new UnexpectedValueException("Invalid foreign key metadata for referenced table [{$foreignTable}]: [{$optionalString}] must be a string when present.");
                }
            }

            $relationship = new RelationshipDefinition(
                name: is_string($foreignKey['name'] ?? null) ? $foreignKey['name'] : null,
                columns: $columns,
                foreignTable: $foreignTable,
                foreignColumns: $foreignColumns,
                onUpdate: is_string($foreignKey['on_update'] ?? null) ? $foreignKey['on_update'] : null,
                onDelete: is_string($foreignKey['on_delete'] ?? null) ? $foreignKey['on_delete'] : null,
            );

            $relationships[] = $relationship;
        }

        usort($relationships, static fn (RelationshipDefinition $left, RelationshipDefinition $right): int =>
            [$left->foreignTable, $left->columns] <=> [$right->foreignTable, $right->columns]
        );
        $tableCounts = [];
        foreach ($relationships as $relationship) {
            $tableCounts[$relationship->foreignTable] = ($tableCounts[$relationship->foreignTable] ?? 0) + 1;
        }

        $usedMethods = [];
        foreach ($relationships as $relationship) {
            $base = Str::camel(Str::singular($relationship->foreignTable));
            if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D', $base)) {
                throw new UnexpectedValueException("Cannot derive a valid relationship method from referenced table [{$relationship->foreignTable}].");
            }
            if ($tableCounts[$relationship->foreignTable] > 1) {
                $columnBase = preg_replace('/_id$/i', '', $relationship->columns[0]) ?: $relationship->columns[0];
                $base = Str::camel($columnBase);
            }

            $method = $base;
            if (isset($usedMethods[$method])) {
                $suffix = substr(sha1($relationship->foreignTable.'|'.implode('|', $relationship->columns)), 0, 8);
                $method = $base.ucfirst($suffix);
                $counter = 2;
                while (isset($usedMethods[$method])) {
                    $method = $base.ucfirst($suffix).$counter++;
                }
            }
            $usedMethods[$method] = true;
            $relationship->assignNames($method, Str::studly(Str::singular($relationship->foreignTable)));
        }

        return $relationships;
    }

    /** @return list<string> */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            throw new UnexpectedValueException('Invalid foreign key metadata: column lists must be arrays.');
        }

        $items = [];
        foreach ($value as $item) {
            if (! is_string($item) || $item === '') {
                throw new UnexpectedValueException('Invalid foreign key metadata: column lists must contain non-empty strings.');
            }

            $items[] = $item;
        }

        return $items;
    }
}

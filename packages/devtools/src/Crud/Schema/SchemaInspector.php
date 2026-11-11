<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Crud\Schema;

use Davingm\LaravelDevtools\Crud\Data\ColumnDefinition;
use Davingm\LaravelDevtools\Crud\Data\CrudDefinition;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

final class SchemaInspector
{
    public function __construct(
        private readonly RelationshipInspector $relationshipInspector = new RelationshipInspector(),
    ) {
    }

    public function inspect(string $table, ?string $modelName = null): CrudDefinition
    {
        try {
            $exists = Schema::hasTable($table);
        } catch (\Throwable $exception) {
            throw new \RuntimeException("Failed checking whether table [{$table}] exists.", 0, $exception);
        }
        if (! $exists) {
            throw new InvalidArgumentException("Table [{$table}] does not exist on the active database connection.");
        }

        $indexes = $this->readMetadata($table, 'indexes', fn () => Schema::getIndexes($table));
        $primaryKeys = $this->primaryKeys($table, $indexes);
        $rawColumns = $this->readMetadata($table, 'columns', fn () => Schema::getColumns($table));
        $columns = $this->columns($table, $rawColumns, $primaryKeys);
        $foreignKeys = $this->readMetadata($table, 'foreign keys', fn () => Schema::getForeignKeys($table));
        try {
            $relationships = $this->relationshipInspector->inspect($foreignKeys);
        } catch (\UnexpectedValueException $exception) {
            throw new \UnexpectedValueException("Invalid foreign key metadata for table [{$table}]: {$exception->getMessage()}", 0, $exception);
        }

        return new CrudDefinition($table, $primaryKeys, $columns, $relationships, $modelName);
    }

    /** @return list<array<string, mixed>> */
    private function readMetadata(string $table, string $operation, callable $reader): array
    {
        try {
            $metadata = $reader();
        } catch (\Throwable $exception) {
            throw new \RuntimeException("Failed reading {$operation} metadata for table [{$table}].", 0, $exception);
        }

        if (! is_array($metadata)) {
            throw new \UnexpectedValueException("Invalid {$operation} metadata for table [{$table}]: expected an array.");
        }

        return $metadata;
    }

    /** @param list<array<string, mixed>> $rawColumns @param list<string> $primaryKeys @return list<ColumnDefinition> */
    private function columns(string $table, array $rawColumns, array $primaryKeys): array
    {
        $columns = [];
        foreach ($rawColumns as $column) {
            if (! is_array($column) || ! is_string($column['name'] ?? null) || $column['name'] === '') {
                throw new \UnexpectedValueException("Invalid column metadata for table [{$table}]: every column needs a non-empty name.");
            }
            if (! is_string($column['type_name'] ?? $column['type'] ?? null)) {
                throw new \UnexpectedValueException("Invalid column metadata for table [{$table}], column [{$column['name']}]: type is missing or invalid.");
            }
            if (! is_bool($column['nullable'] ?? null)) {
                throw new \UnexpectedValueException("Invalid column metadata for table [{$table}], column [{$column['name']}]: nullable flag must be a boolean.");
            }
            // Laravel drivers may omit this optional flag; absence means we cannot treat the key as incrementing.
            if (array_key_exists('auto_increment', $column) && ! is_bool($column['auto_increment'])) {
                throw new \UnexpectedValueException("Invalid column metadata for table [{$table}], column [{$column['name']}]: auto_increment flag must be a boolean when provided.");
            }
            $name = $column['name'];
            $length = $column['length'] ?? null;
            $type = $column['type_name'] ?? $column['type'];
            $declaredType = strtolower(trim((string) ($column['type'] ?? $type)));
            if (preg_match('/^tinyint\s*\(\s*1\s*\)(?:\s+unsigned)?$/D', $declaredType) === 1) {
                $type = 'boolean';
            }

            $columns[] = new ColumnDefinition(
                name: $name,
                type: $type,
                nullable: $column['nullable'],
                default: $column['default'] ?? null,
                autoIncrement: $column['auto_increment'] ?? false,
                primary: in_array($name, $primaryKeys, true),
                length: is_int($length) || (is_string($length) && ctype_digit($length)) ? (int) $length : null,
                metadata: $column,
            );
        }

        foreach ($primaryKeys as $primaryKey) {
            if (! in_array($primaryKey, array_map(static fn (ColumnDefinition $column) => $column->name, $columns), true)) {
                throw new \UnexpectedValueException("Invalid primary index metadata for table [{$table}]: column [{$primaryKey}] was not returned by the column listing.");
            }
        }

        return $columns;
    }

    /**
     * @param list<array<string, mixed>> $indexes
     * @return list<string>
     */
    private function primaryKeys(string $table, array $indexes): array
    {
        foreach ($indexes as $index) {
            if (! is_array($index) || ! is_bool($index['primary'] ?? null)) {
                throw new \UnexpectedValueException("Invalid index metadata for table [{$table}]: each index requires a boolean primary flag.");
            }
            if (! $index['primary']) continue;

            $columns = $index['columns'] ?? [];
            if (! is_array($columns) || $columns === []) {
                throw new \UnexpectedValueException("Invalid primary index metadata for table [{$table}]: columns must be a non-empty array.");
            }

            $primaryKeys = [];
            foreach ($columns as $column) {
                if (is_string($column) && $column !== '') {
                    $primaryKeys[] = $column;
                } else {
                    throw new \UnexpectedValueException("Invalid primary index metadata for table [{$table}]: column names must be non-empty strings.");
                }
            }

            if (count($primaryKeys) !== count(array_unique($primaryKeys))) {
                throw new \UnexpectedValueException("Invalid primary index metadata for table [{$table}]: duplicate column name.");
            }
            return $primaryKeys;
        }

        return [];
    }
}

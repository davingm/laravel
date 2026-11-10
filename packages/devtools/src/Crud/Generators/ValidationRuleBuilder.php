<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Crud\Generators;

use Davingm\LaravelDevtools\Crud\Data\CrudDefinition;
use InvalidArgumentException;

final class ValidationRuleBuilder
{
    /** @var array<string, string> */
    private array $warnings = [];

    /** @return array<string, list<string>> */
    public function build(CrudDefinition $definition): array
    {
        $this->warnings = [];
        $rules = [];
        $timestamps = ['created_at', 'updated_at', 'deleted_at'];
        $primaryKey = $definition->primaryKey();

        foreach ($definition->columns as $column) {
            if ($column->name === $primaryKey || $column->primary || $column->autoIncrement || in_array($column->name, $timestamps, true)) {
                continue;
            }

            $type = strtolower(trim($column->type));
            $type = preg_replace('/\s*\(.*$/', '', $type) ?? $type;
            $type = explode(' ', $type, 2)[0];
            $isBoolean = in_array($type, ['bool', 'boolean'], true);
            $columnRules = [];

            if ($isBoolean) {
                $columnRules[] = 'sometimes';
            } elseif ($column->nullable) {
                $columnRules[] = 'nullable';
            } elseif ($column->default === null) {
                $columnRules[] = 'required';
            }

            $baseRule = $this->baseRule($type);
            if ($baseRule !== null) {
                $columnRules[] = $baseRule;
            }

            if (in_array($type, ['varchar', 'char'], true) && $column->length !== null) {
                $columnRules[] = 'max:'.$column->length;
            }

            foreach ($definition->relationships as $relationship) {
                if (! in_array($column->name, $relationship->columns, true)) {
                    continue;
                }

                if (! $relationship->isNamed() || ! $relationship->supportsEloquentRelation()) {
                    $this->warnings[$column->name] = 'composite foreign key: exists rule skipped';
                    continue;
                }

                if (count($relationship->columns) !== 1 || count($relationship->foreignColumns) !== 1 || $relationship->foreignTable === '') {
                    throw new InvalidArgumentException("Cannot build exists rule for column [{$column->name}]: foreign key metadata is incomplete.");
                }

                $relatedColumn = $relationship->foreignColumns[0];
                if ($relatedColumn === '') {
                    throw new InvalidArgumentException("Cannot build exists rule for column [{$column->name}]: referenced column is empty.");
                }

                $columnRules[] = 'exists:'.$relationship->foreignTable.','.$relatedColumn;
            }

            $rules[$column->name] = $columnRules;
        }

        return $rules;
    }

    /** @return array<string, string> */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    private function baseRule(string $type): ?string
    {
        return match ($type) {
            'varchar', 'string', 'char', 'text', 'mediumtext', 'longtext', 'enum' => 'string',
            'integer', 'bigint', 'smallint', 'tinyint' => 'integer',
            'decimal', 'float', 'double', 'numeric' => 'numeric',
            'boolean', 'bool' => 'boolean',
            'date', 'datetime', 'timestamp' => 'date',
            'time' => 'date_format:H:i:s',
            default => null,
        };
    }
}

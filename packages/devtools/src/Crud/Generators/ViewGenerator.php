<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Crud\Generators;

use Davingm\LaravelDevtools\Crud\Data\ColumnDefinition;
use Davingm\LaravelDevtools\Crud\Data\CrudDefinition;
use Davingm\LaravelDevtools\Crud\Data\RelationshipDefinition;
use Davingm\LaravelDevtools\Crud\Exceptions\ViewGenerationException;
use Illuminate\Support\Str;

final class ViewGenerator
{
    public function __construct(
        private readonly ViewStub $stub = new ViewStub(),
        private readonly ViewFieldRenderer $fieldRenderer = new ViewFieldRenderer(),
    ) {
    }

    /** @return array<string, string> Map of view name to Blade content. */
    public function generate(CrudDefinition $definition): array
    {
        if ($definition->primaryKeyIsComposite()) {
            throw ViewGenerationException::forCompositePrimaryKey($definition->table, $definition->primaryKeys);
        }
        if ($definition->primaryKey() === null) {
            throw ViewGenerationException::forMissingPrimaryKey($definition->table);
        }

        $table = $definition->table;
        $modelName = Str::studly(Str::singular($table));
        $modelVar = Str::camel(Str::singular($table));
        $modelPluralVar = Str::camel($table);
        $timestamps = ['created_at', 'updated_at', 'deleted_at'];
        $formFieldsCreate = [];
        $formFieldsEdit = [];
        $indexColumns = [];

        foreach ($definition->columns as $column) {
            $relationship = $this->relationshipFor($definition, $column->name);
            $excludedFromForm = $column->name === $definition->primaryKey()
                || $column->primary
                || $column->autoIncrement
                || in_array($column->name, $timestamps, true);

            if (! $excludedFromForm) {
                $formFieldsCreate[] = $this->fieldRenderer->renderField($column, $relationship, $modelVar, false);
                $formFieldsEdit[] = $this->fieldRenderer->renderField($column, $relationship, $modelVar, true);
            }

            if ($column->name === $definition->primaryKey() || $column->primary || in_array($column->name, $timestamps, true)) {
                continue;
            }

            $indexColumns[] = [
                'name' => $column->name,
                'label' => Str::headline($column->name),
                'blade_expr' => $this->indexExpression($column, $relationship, $modelVar),
            ];
        }

        return [
            'index' => $this->stub->renderIndex(
                table: $table,
                modelVar: $modelVar,
                modelPluralVar: $modelPluralVar,
                title: $modelName.' List',
                createButtonLabel: 'Create '.$modelName,
                columns: $indexColumns,
                editRoute: $table.'.edit',
                destroyRoute: $table.'.destroy',
                hasPagination: true,
            ),
            'create' => $this->stub->renderCreate(
                table: $table,
                title: 'Create '.$modelName,
                formFields: $formFieldsCreate,
                storeRoute: $table.'.store',
                backRoute: $table.'.index',
            ),
            'edit' => $this->stub->renderEdit(
                table: $table,
                modelVar: $modelVar,
                title: 'Edit '.$modelName,
                formFields: $formFieldsEdit,
                updateRoute: $table.'.update',
                backRoute: $table.'.index',
            ),
        ];
    }

    private function relationshipFor(CrudDefinition $definition, string $column): ?RelationshipDefinition
    {
        foreach ($definition->relationships as $relationship) {
            if (in_array($column, $relationship->columns, true)) {
                return $relationship;
            }
        }

        return null;
    }

    private function indexExpression(ColumnDefinition $column, ?RelationshipDefinition $relationship, string $modelVar): string
    {
        $columnAccess = $this->propertyAccess($column->name);
        $type = $this->normalizedType($column->type);
        if (in_array($type, ['bool', 'boolean'], true)) {
            return '$'.$modelVar.$columnAccess." ? 'Yes' : 'No'";
        }

        if ($relationship !== null && $relationship->isNamed() && $relationship->supportsEloquentRelation()) {
            if (count($relationship->foreignColumns) === 1) {
                $relatedColumn = $this->propertyAccess($relationship->foreignColumns[0]);

                return '$'.$modelVar.$this->propertyAccess($relationship->method).$relatedColumn." ?? '-'";
            }
        }

        return '$'.$modelVar.$columnAccess;
    }

    private function normalizedType(string $type): string
    {
        $type = strtolower(trim($type));
        $type = preg_replace('/\s*\(.*$/', '', $type) ?? $type;

        return explode(' ', $type, 2)[0];
    }

    private function propertyAccess(string $name): string
    {
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D', $name) === 1) {
            return '->'.$name;
        }

        return '->{'.var_export($name, true).'}';
    }
}

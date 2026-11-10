<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Crud\Generators;

use Davingm\LaravelDevtools\Crud\Data\CrudDefinition;
use Davingm\LaravelDevtools\Crud\Exceptions\ControllerGenerationException;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ControllerGenerator
{
    public function __construct(
        private readonly ControllerStub $stub = new ControllerStub(),
        private readonly ValidationRuleBuilder $rules = new ValidationRuleBuilder(),
        private readonly int $perPage = 15,
    ) {
        if ($this->perPage < 1) {
            throw new InvalidArgumentException('Controller pagination size must be a positive integer.');
        }
    }

    /** Builds dropdown data for each named, single-column belongsTo relationship. */
    public function generate(CrudDefinition $definition): string
    {
        if ($definition->primaryKeyIsComposite()) {
            throw ControllerGenerationException::forCompositePrimaryKey($definition->table, $definition->primaryKeys);
        }
        if ($definition->primaryKey() === null) {
            throw ControllerGenerationException::forMissingPrimaryKey($definition->table);
        }

        $modelName = Str::studly(Str::singular($definition->table));
        $className = $modelName.'Controller';
        $modelVar = Str::camel(Str::singular($definition->table));
        $collectionVar = Str::camel($definition->table);

        foreach ([$className, $modelName, $modelVar, $collectionVar] as $identifier) {
            if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D', $identifier) !== 1) {
                throw new InvalidArgumentException("Cannot generate controller for table [{$definition->table}]: derived PHP identifier [{$identifier}] is invalid.");
            }
        }

        $validationRules = $this->rules->build($definition);
        $dropdownData = [];
        foreach ($definition->relationships as $relationship) {
            if (! $relationship->isNamed() || ! $relationship->supportsEloquentRelation()) {
                continue;
            }

            $variable = Str::camel($relationship->foreignTable);
            if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D', $variable) !== 1) {
                throw new InvalidArgumentException("Cannot generate controller for table [{$definition->table}]: dropdown variable [{$variable}] is invalid.");
            }

            $relatedModel = $relationship->relatedModel;
            if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D', $relatedModel) !== 1) {
                throw new InvalidArgumentException("Cannot generate controller for table [{$definition->table}]: related model [{$relatedModel}] is invalid.");
            }

            if (! array_key_exists($variable, $dropdownData)) {
                $dropdownData[$variable] = '\\App\\Models\\'.$relatedModel;
            }
        }

        $content = $this->stub->render(
            namespace: 'App\\Http\\Controllers',
            className: $className,
            modelFqcn: '\\App\\Models\\'.$modelName,
            modelVar: $modelVar,
            table: $definition->table,
            validationRules: $validationRules,
            perPage: (string) $this->perPage,
            dropdownData: $dropdownData,
        );

        $warnings = $this->rules->getWarnings();
        if ($warnings !== []) {
            $warningLines = [];
            foreach ($warnings as $column => $warning) {
                $warningLines[] = '    // WARNING: '.$column.': '.$warning;
            }

            $marker = '    // TODO: extract to FormRequest if business logic grows'.PHP_EOL.'    public function store';
            $position = strpos($content, $marker);
            if ($position === false) {
                throw new InvalidArgumentException('Controller stub is missing the store method marker required for validation warnings.');
            }

            $content = substr_replace($content, implode(PHP_EOL, $warningLines).PHP_EOL.$marker, $position, strlen($marker));
        }

        return $content;
    }
}

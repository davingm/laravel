<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Crud\Generators;

use Davingm\LaravelDevtools\Crud\Data\CrudDefinition;
use Davingm\LaravelDevtools\Crud\Exceptions\ModelGenerationException;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ModelGenerator
{
    public function __construct(
        private readonly ModelStub $stub = new ModelStub(),
    ) {
    }

    public function generate(CrudDefinition $definition): string
    {
        if ($definition->primaryKeyIsComposite()) {
            throw ModelGenerationException::forCompositePrimaryKey($definition->table, $definition->primaryKeys);
        }

        $primaryKey = $definition->primaryKey();
        if ($primaryKey === null) {
            throw ModelGenerationException::forMissingPrimaryKey($definition->table);
        }

        $className = Str::studly(Str::singular($definition->table));
        $this->assertIdentifier($className, 'model class');

        $fillable = [];
        $casts = [];
        foreach ($definition->columns as $column) {
            if ($column->name !== $primaryKey && ! $column->primary && ! $column->autoIncrement) {
                $fillable[] = $column->name;
            }

            $cast = $this->castFor($column->type);
            if ($cast !== null) {
                $casts[$column->name] = $cast;
            }
        }

        $relations = [];
        $skippedRelations = [];
        foreach ($definition->relationships as $relationship) {
            if (! $relationship->isNamed() || ! $relationship->supportsEloquentRelation()) {
                $skippedRelations[] = 'TODO: composite relationship skipped — not supported by this generator yet';
                continue;
            }

            $method = $relationship->method;
            $relatedModel = $relationship->relatedModel;
            $this->assertIdentifier($method, 'relationship method');
            $this->assertIdentifier($relatedModel, 'related model');

            $relations[] = [
                'method' => $method,
                'relatedModel' => 'App\\Models\\'.$relatedModel,
            ];
        }

        $explicitPrimaryKey = $primaryKey === 'id' ? null : $primaryKey;
        $explicitIncrementing = $definition->incrementing() ? null : false;
        $explicitKeyType = $definition->keyType() === 'string' ? 'string' : null;

        return $this->stub->render(
            namespace: 'App\\Models',
            className: $className,
            fillable: $fillable,
            casts: $casts,
            primaryKey: $explicitPrimaryKey,
            incrementing: $explicitIncrementing,
            keyType: $explicitKeyType,
            relations: $relations,
            skippedRelations: $skippedRelations,
        );
    }

    private function castFor(string $type): ?string
    {
        $type = strtolower(trim($type));
        $type = preg_replace('/\s*\(.*$/', '', $type) ?? $type;
        $type = explode(' ', $type, 2)[0];

        return match ($type) {
            'bool', 'boolean' => 'bool',
            'json', 'array' => 'array',
            'decimal' => 'decimal:2',
            'date' => 'date',
            'datetime', 'timestamp' => 'datetime',
            default => null,
        };
    }

    private function assertIdentifier(string $identifier, string $kind): void
    {
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D', $identifier) !== 1) {
            throw new InvalidArgumentException("Cannot generate model: {$kind} [{$identifier}] is not a valid PHP identifier.");
        }
    }
}

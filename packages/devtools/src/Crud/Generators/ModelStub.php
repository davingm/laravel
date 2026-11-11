<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Crud\Generators;

final class ModelStub
{
    /**
     * @param list<string> $fillable
     * @param array<string, string> $casts
     * @param list<array{method: string, relatedModel: string}> $relations
     * @param list<string> $skippedRelations
     */
    public function render(
        string $namespace,
        string $className,
        array $fillable,
        array $casts,
        ?string $primaryKey,
        ?bool $incrementing,
        ?string $keyType,
        array $relations,
        array $skippedRelations,
    ): string {
        $uses = ['use Illuminate\\Database\\Eloquent\\Model;'];
        if ($relations !== []) {
            $uses[] = 'use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;';
        }

        $lines = [
            '<?php',
            '',
            'declare(strict_types=1);',
            '',
            "namespace {$namespace};",
            '',
            ...$uses,
            '',
            "class {$className} extends Model",
            '{',
            '    protected $fillable = '.$this->listLiteral(array_values($fillable)).';',
        ];

        if ($casts !== []) {
            $lines[] = '';
            $lines[] = '    protected $casts = '.$this->mapLiteral($casts).';';
        }
        if ($primaryKey !== null) {
            $lines[] = '';
            $lines[] = '    protected $primaryKey = '.var_export($primaryKey, true).';';
        }
        if ($incrementing !== null) {
            $lines[] = '';
            $lines[] = '    public $incrementing = '.($incrementing ? 'true' : 'false').';';
        }
        if ($keyType !== null) {
            $lines[] = '';
            $lines[] = '    protected $keyType = '.var_export($keyType, true).';';
        }

        foreach ($skippedRelations as $reason) {
            $lines[] = '';
            $lines[] = '    // '.$reason;
        }

        foreach ($relations as $relation) {
            $relatedModel = ltrim($relation['relatedModel'], '\\');
            $lines[] = '';
            $lines[] = "    public function {$relation['method']}(): BelongsTo";
            $lines[] = '    {';
            $lines[] = "        return \$this->belongsTo(\\{$relatedModel}::class);";
            $lines[] = '    }';
        }

        $lines[] = '}';

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    /** @param list<string> $values */
    private function listLiteral(array $values): string
    {
        return '['.implode(', ', array_map(static fn (string $value): string => var_export($value, true), $values)).']';
    }

    /** @param array<string, string> $values */
    private function mapLiteral(array $values): string
    {
        $items = [];
        foreach ($values as $key => $value) {
            $items[] = var_export($key, true).' => '.var_export($value, true);
        }

        return '['.implode(', ', $items).']';
    }
}

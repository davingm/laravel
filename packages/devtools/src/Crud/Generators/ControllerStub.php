<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Crud\Generators;

use Illuminate\Support\Str;

final class ControllerStub
{
    /**
     * @param array<string, list<string>> $validationRules
     * @param array<string, string> $dropdownData Variable name to related model FQCN.
     */
    public function render(
        string $namespace,
        string $className,
        string $modelFqcn,
        string $modelVar,
        string $table,
        array $validationRules,
        string $perPage,
        array $dropdownData = [],
    ): string {
        $modelFqcn = '\\'.ltrim($modelFqcn, '\\');
        $collectionVar = Str::camel($table);
        $modelName = Str::studly(Str::singular($table));
        $viewDirectory = 'pages.'.$table;
        $dropdownVariables = array_keys($dropdownData);
        $editVariables = array_merge([$modelVar], $dropdownVariables);
        $routeIndex = var_export($table.'.index', true);
        $lines = [
            '<?php',
            '',
            'declare(strict_types=1);',
            '',
            "namespace {$namespace};",
            '',
            'use App\\Http\\Controllers\\Controller;',
            'use Illuminate\\Http\\RedirectResponse;',
            'use Illuminate\\Http\\Request;',
            'use Illuminate\\View\\View;',
            '',
            "class {$className} extends Controller",
            '{',
            '    public function index(): View',
            '    {',
            "        \${$collectionVar} = {$modelFqcn}::query()->paginate({$perPage});",
            "        return view(".var_export($viewDirectory.'.index', true).", ['{$collectionVar}' => \${$collectionVar}]);",
            '    }',
            '',
            '    public function create(): View',
            '    {',
            ...$this->dropdownQueries($dropdownData),
            $dropdownData === []
                ? '        return view('.var_export($viewDirectory.'.create', true).');'
                : '        return view('.var_export($viewDirectory.'.create', true).', '.$this->compactCall($dropdownVariables).');',
            '    }',
            '',
            '    // TODO: extract to FormRequest if business logic grows',
            '    public function store(Request $request): RedirectResponse',
            '    {',
            '        $validated = $request->validate('.$this->rulesLiteral($validationRules).');',
            "        {$modelFqcn}::create(\$validated);",
            "        return redirect()->route({$routeIndex})->with('success', ".var_export($modelName.' created successfully.', true).');',
            '    }',
            '',
            "    public function edit({$modelFqcn} \${$modelVar}): View",
            '    {',
            ...$this->dropdownQueries($dropdownData),
            $dropdownData === []
                ? "        return view(".var_export($viewDirectory.'.edit', true).", ['{$modelVar}' => \${$modelVar}]);"
                : '        return view('.var_export($viewDirectory.'.edit', true).', '.$this->compactCall($editVariables).');',
            '    }',
            '',
            '    // TODO: extract to FormRequest if business logic grows',
            "    public function update(Request \$request, {$modelFqcn} \${$modelVar}): RedirectResponse",
            '    {',
            '        $validated = $request->validate('.$this->rulesLiteral($validationRules).');',
            "        \${$modelVar}->update(\$validated);",
            "        return redirect()->route({$routeIndex})->with('success', ".var_export($modelName.' updated successfully.', true).');',
            '    }',
            '',
            "    public function destroy({$modelFqcn} \${$modelVar}): RedirectResponse",
            '    {',
            "        \${$modelVar}->delete();",
            "        return redirect()->route({$routeIndex})->with('success', ".var_export($modelName.' deleted successfully.', true).');',
            '    }',
            '}',
        ];

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    /** @param array<string, list<string>> $rules */
    private function rulesLiteral(array $rules): string
    {
        $items = [];
        foreach ($rules as $column => $columnRules) {
            $items[] = var_export($column, true).' => '.$this->stringListLiteral($columnRules);
        }

        return '['.implode(', ', $items).']';
    }

    /** @param list<string> $values */
    private function stringListLiteral(array $values): string
    {
        return '['.implode(', ', array_map(static fn (string $value): string => var_export($value, true), $values)).']';
    }

    /** @param array<string, string> $dropdownData */
    private function dropdownQueries(array $dropdownData): array
    {
        $lines = [];
        foreach ($dropdownData as $variable => $modelFqcn) {
            $lines[] = '        // TODO: order dropdown by display column when metadata available';
            $lines[] = '        $'.$variable.' = \\'.ltrim($modelFqcn, '\\').'::query()->orderBy(\'id\')->get();';
        }

        return $lines;
    }

    /** @param list<string> $variables */
    private function compactCall(array $variables): string
    {
        return 'compact('.implode(', ', array_map(static fn (string $variable): string => var_export($variable, true), $variables)).')';
    }
}

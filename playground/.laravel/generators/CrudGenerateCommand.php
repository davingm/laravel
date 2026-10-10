<?php

namespace Laravel\Generators;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class CrudGenerateCommand extends Command
{
    protected $signature = 'crud:generate {model : Nama Model} {--role= : Nama role untuk middleware}';

    protected $description = 'Generate CRUD files from the active database schema';

    private const IGNORED_COLUMNS = ['id', 'created_at', 'updated_at', 'deleted_at'];

    public function handle(): int
    {
        $inputName = Str::snake(trim((string) $this->argument('model')));
        $modelInput = Str::endsWith($inputName, 's') ? substr($inputName, 0, -1) : $inputName;
        $modelName = Str::studly($modelInput);
        if (! preg_match('/^[A-Z][A-Za-z0-9]*$/', $modelName)) {
            $this->error('Invalid model name. Use a simple class name such as Product.');

            return self::FAILURE;
        }

        $role = $this->option('role');
        $table = Str::snake(Str::pluralStudly($modelName));
        $routePrefix = Str::kebab($table);
        $pageDirectory = Str::kebab($table);

        $tableCandidates = Str::endsWith($inputName, 's')
            ? [$inputName, $modelInput]
            : [$inputName.'s', $inputName];
        $modelVariable = Str::camel($modelName);
        $stubDirectory = base_path('.laravel/crud');
        $routeFile = base_path('app/routes/web.php');

        foreach (['model', 'controller', 'view_index', 'view_form', 'edit'] as $stubName) {
            if (! File::exists("{$stubDirectory}/{$stubName}.stub")) {
                $this->error("CRUD stub not found: .laravel/crud/{$stubName}.stub");

                return self::FAILURE;
            }
        }

        if (! File::exists($routeFile)) {
            $this->error('Route file not found: app/routes/web.php');

            return self::FAILURE;
        }

        try {
            $table = collect(array_unique($tableCandidates))
                ->first(fn (string $candidate) => Schema::hasTable($candidate));

            if ($table === null) {
                $this->error("Table \"{$tableCandidates[0]}\" does not exist.");

                return self::FAILURE;
            }

            $columns = Schema::getColumns($table);
            $foreignKeys = Schema::getForeignKeys($table);
        } catch (Throwable $exception) {
            $this->error('Unable to inspect database schema: '.$exception->getMessage());

            return self::FAILURE;
        }

        $routePrefix = Str::kebab($table);
        $pageDirectory = Str::kebab($table);

        if ($columns === []) {
            $this->error("No supported column metadata was returned for table \"{$table}\".");

            return self::FAILURE;
        }

        $relationships = $this->relationships($foreignKeys);
        $relationshipByColumn = [];
        foreach ($relationships as $relationship) {
            $relationshipByColumn[$relationship['column']] = $relationship;
        }

        $fields = [];
        foreach ($columns as $column) {
            $name = (string) ($column['name'] ?? '');
            if ($name === '' || in_array($name, self::IGNORED_COLUMNS, true) || ($column['auto_increment'] ?? false)) {
                continue;
            }

            $field = [
                'name' => $name,
                'label' => Str::headline($name),
                'type' => $this->inputType($column),
                'column_type' => $this->columnType($column),
                'nullable' => (bool) ($column['nullable'] ?? false),
                'has_default' => array_key_exists('default', $column) && $column['default'] !== null,
            ];

            if (isset($relationshipByColumn[$name])) {
                $field['relationship'] = $relationshipByColumn[$name];
            }

            $fields[] = $field;
        }

        $createContext = $this->buildContext(
            $modelName,
            $modelVariable,
            $table,
            $routePrefix,
            $pageDirectory,
            $columns,
            $fields,
            $relationships,
            false,
        );
        $editContext = $this->buildContext(
            $modelName,
            $modelVariable,
            $table,
            $routePrefix,
            $pageDirectory,
            $columns,
            $fields,
            $relationships,
            true,
        );
        $files = [
            app_path("Models/{$modelName}.php") => 'model',
            app_path("Http/Controllers/{$modelName}Controller.php") => 'controller',
            base_path("src/pages/{$pageDirectory}/index.blade.php") => 'view_index',
            base_path("src/pages/{$pageDirectory}/create.blade.php") => 'view_form',
            base_path("src/pages/{$pageDirectory}/edit.blade.php") => 'edit',
        ];

        $this->newLine();
        $this->line('  <fg=red;options=bold>CRUD Generator</>');
        $this->line("  Model: {$modelName}");
        $this->line("  Table: {$table}");
        $this->line('  Schema: '.count($columns).' columns, '.count($relationships).' foreign key(s)');
        $this->newLine();

        foreach ($files as $path => $stubName) {
            $relativePath = Str::after($path, base_path().DIRECTORY_SEPARATOR);
            if (File::exists($path)) {
                $this->line("  <fg=yellow>!</> {$relativePath} already exists. Skipping...");

                continue;
            }

            File::ensureDirectoryExists(dirname($path));
            $templateContext = in_array($stubName, ['view_form', 'edit'], true)
                ? ($stubName === 'edit' ? $editContext : $createContext)
                : $createContext;
            $contents = strtr(File::get("{$stubDirectory}/{$stubName}.stub"), $templateContext);
            File::put($path, $contents);
            $this->line("  <fg=green>✓</> {$relativePath} created");
        }

        [$routeAdded, $routeError] = $this->registerResourceRoute($routeFile, $routePrefix, $modelName, $role);
        if ($routeError !== null) {
            $this->error($routeError);

            return self::FAILURE;
        }

        $this->line($routeAdded
            ? "  <fg=green>✓</> Route::resource('{$routePrefix}', ... )"
            : "  <fg=yellow>!</> Resource route '{$routePrefix}' already exists. Skipping...");
        $this->newLine();
        $this->info('CRUD generation completed successfully.');

        return self::SUCCESS;
    }

    /** @return array<int, array<string, string>> */
    private function relationships(array $foreignKeys): array
    {
        $relationships = [];
        $usedMethods = [];
        $usedVariables = [];

        foreach ($foreignKeys as $foreignKey) {
            $columns = $foreignKey['columns'] ?? [];
            $foreignColumns = $foreignKey['foreign_columns'] ?? [];
            $foreignTable = (string) ($foreignKey['foreign_table'] ?? '');
            if (count($columns) !== 1 || count($foreignColumns) !== 1 || $foreignTable === '') {
                $this->warn('Skipping an unsupported composite or incomplete foreign key.');

                continue;
            }

            $column = (string) $columns[0];
            $foreignColumn = (string) $foreignColumns[0];
            $relatedClass = Str::studly(Str::singular($foreignTable));
            $method = Str::camel(Str::singular($foreignTable));
            if (isset($usedMethods[$method])) {
                $method = Str::camel(preg_replace('/_id$/', '', $column) ?: $column);
            }
            if (isset($usedMethods[$method])) {
                $method .= $relatedClass;
            }
            $usedMethods[$method] = true;

            $variable = Str::plural($method);
            $baseVariable = $variable;
            for ($suffix = 2; isset($usedVariables[$variable]); $suffix++) {
                $variable = $baseVariable.$suffix;
            }
            $usedVariables[$variable] = true;

            $displayColumn = $foreignColumn;
            try {
                foreach (Schema::getColumns($foreignTable) as $relatedColumn) {
                    if (in_array(strtolower((string) ($relatedColumn['name'] ?? '')), ['name', 'title', 'label'], true)) {
                        $displayColumn = (string) $relatedColumn['name'];
                        break;
                    }
                }
            } catch (Throwable) {
                $this->warn("Unable to inspect display columns for '{$foreignTable}'; using '{$foreignColumn}'.");
            }

            $relationships[] = [
                'column' => $column,
                'foreign_table' => $foreignTable,
                'foreign_column' => $foreignColumn,
                'related_class' => $relatedClass,
                'method' => $method,
                'variable' => $variable,
                'display_column' => $displayColumn,
            ];
        }

        return $relationships;
    }

    /** @return array<string, string> */
    private function buildContext(
        string $modelName,
        string $modelVariable,
        string $table,
        string $routePrefix,
        string $pageDirectory,
        array $columns,
        array $fields,
        array $relationships,
        bool $isEdit = false,
    ): array {
        $fillable = [];
        $columnNames = array_column($columns, 'name');
        foreach ($columns as $column) {
            $name = (string) ($column['name'] ?? '');
            if ($name !== '' && ! in_array($name, self::IGNORED_COLUMNS, true) && ! ($column['auto_increment'] ?? false)) {
                $fillable[] = '        '.var_export($name, true).',';
            }
        }

        $relationshipCode = [];
        $foreignData = [];
        foreach ($relationships as $relationship) {
            $relationshipCode[] = "    public function {$relationship['method']}(): BelongsTo\n    {\n        return \$this->belongsTo(\\App\\Models\\{$relationship['related_class']}::class, ".var_export($relationship['column'], true).', '.var_export($relationship['foreign_column'], true).');' ."\n    }";
            $foreignData[] = "            '{$relationship['variable']}' => DB::table(".var_export($relationship['foreign_table'], true).')->orderBy('.var_export($relationship['display_column'], true).')->get(),';
        }

        $validationRules = [];
        foreach ($fields as $field) {
            $rules = [];
            $rules[] = $field['nullable'] ? "'nullable'" : ($field['has_default'] ? "'sometimes'" : "'required'");
            $typeRule = $this->validationType($field['column_type']);
            if ($typeRule !== null) {
                $rules[] = "'{$typeRule}'";
            }
            if (isset($field['relationship'])) {
                $relationship = $field['relationship'];
                $rules[] = var_export('exists:'.$relationship['foreign_table'].','.$relationship['foreign_column'], true);
            }
            $validationRules[] = '            '.var_export($field['name'], true).' => ['.implode(', ', $rules).'],';
        }

        $formFields = $this->formFields($fields, $modelVariable, $isEdit);
        $indexHeaders = [];
        $indexCells = [];
        foreach ($fields as $field) {
            $indexHeaders[] = '                    <th>'.e($field['label']).'</th>';
            $indexCells[] = "                    <td>{{ \${$modelVariable}->{".var_export($field['name'], true).'} }}</td>';
        }

        return [
            '{{MODEL}}' => $modelName,
            '{{MODEL_VARIABLE}}' => $modelVariable,
            '{{MODEL_PLURAL}}' => Str::plural($modelVariable),
            '{{TABLE}}' => $table,
            '{{ROUTE_PREFIX}}' => $routePrefix,
            '{{PAGE_DIRECTORY}}' => $pageDirectory,
            '{{FILLABLE}}' => implode("\n", $fillable),
            '{{TIMESTAMPS}}' => in_array('created_at', $columnNames, true) && in_array('updated_at', $columnNames, true)
                ? ''
                : "\n    public \$timestamps = false;\n",
            '{{RELATIONSHIP_IMPORT}}' => $relationships !== [] ? "\nuse Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;\n" : '',
            '{{RELATIONSHIPS}}' => $relationshipCode === [] ? '' : "\n\n".implode("\n\n", $relationshipCode)."\n",
            '{{FOREIGN_IMPORTS}}' => $relationships !== [] ? "\nuse Illuminate\\Support\\Facades\\DB;\n" : '',
            '{{FOREIGN_DATA}}' => implode("\n", $foreignData),
            '{{VALIDATION_RULES}}' => implode("\n", $validationRules),
            '{{FORM_FIELDS}}' => implode("\n", $formFields),
            '{{INDEX_HEADERS}}' => implode("\n", $indexHeaders),
            '{{INDEX_CELLS}}' => implode("\n", $indexCells),
            '{{INDEX_COLUMN_COUNT}}' => (string) (count($fields) + 1),
        ];
    }

    private function formFields(array $fields, string $modelVariable, bool $isEdit = false): array
    {
        $lines = [];
        foreach ($fields as $field) {
            $name = $field['name'];
            $escapedName = e($name);
            $phpName = var_export($name, true);
            $label = e($field['label']);
            $valueExpression = $isEdit
                ? "old({$phpName}, \${$modelVariable}->{$name})"
                : "old({$phpName})";
            $oldValue = $field['type'] === 'checkbox'
                ? ($isEdit ? "old({$phpName}, \${$modelVariable}->{$name})" : "old({$phpName}, false)")
                : $valueExpression;
            $required = $field['nullable'] || $field['has_default'] || $field['type'] === 'checkbox' ? '' : ' required';

            if (isset($field['relationship'])) {
                $relationship = $field['relationship'];
                $relatedVariable = Str::singular($relationship['variable']);
                $foreignColumn = $relationship['foreign_column'];
                $displayColumn = $relationship['display_column'];
                $selected = $isEdit
                    ? "@selected(old({$phpName}, \${$modelVariable}->{$name}) == \${$relatedVariable}->{$foreignColumn})"
                    : "@selected(old({$phpName}) == \${$relatedVariable}->{$foreignColumn})";
                $lines[] = "    <label class=\"crud-field\">\n        {$label}\n        <select name=\"{$escapedName}\"{$required} class=\"crud-input\">\n            <option value=\"\">Pilih {$label}</option>\n            @foreach(\${$relationship['variable']} as \${$relatedVariable})\n                <option value=\"{{ \${$relatedVariable}->{$foreignColumn} }}\" {$selected}>{{ \${$relatedVariable}->{$displayColumn} }}</option>\n            @endforeach\n        </select>\n        @error({$phpName})<span class=\"form-error\">{{ \$message }}</span>@enderror\n    </label>";

                continue;
            }

            if (in_array($field['column_type'], ['text', 'tinytext', 'longtext', 'mediumtext'], true)) {
                $control = "<textarea name=\"{$escapedName}\"{$required} rows=\"4\" class=\"crud-input\">{{ {$valueExpression} }}</textarea>";
            } elseif ($field['type'] === 'checkbox') {
                $lines[] = "    <label class=\"crud-checkbox-field\">\n        <input type=\"hidden\" name=\"{$escapedName}\" value=\"0\">\n        <input type=\"checkbox\" name=\"{$escapedName}\" value=\"1\" class=\"crud-checkbox\" @checked({$oldValue})>\n        {$label}\n        @error({$phpName})<span class=\"form-error\">{{ \$message }}</span>@enderror\n    </label>";

                continue;
            } else {
                $control = "<input type=\"{$field['type']}\" name=\"{$escapedName}\" value=\"{{ {$valueExpression} }}\"{$required} class=\"crud-input\">";
            }

            $lines[] = "    <label class=\"crud-field\">\n        {$label}\n        {$control}\n        @error({$phpName})<span class=\"form-error\">{{ \$message }}</span>@enderror\n    </label>";
        }

        return $lines;
    }

    private function columnType(array $column): string
    {
        $type = strtolower((string) ($column['type_name'] ?? $column['type'] ?? ''));

        return preg_replace('/\s*\([^)]*\)/', '', $type) ?? $type;
    }

    private function inputType(array $column): string
    {
        $type = $this->columnType($column);
        $name = strtolower((string) ($column['name'] ?? ''));

        return match (true) {
            in_array($type, ['bool', 'boolean'], true) => 'checkbox',
            $name === 'email' => 'email',
            $type === 'date' => 'date',
            in_array($type, ['datetime', 'datetime2', 'timestamp'], true) => 'datetime-local',
            in_array($type, ['integer', 'int', 'bigint', 'smallint', 'mediumint', 'tinyint', 'decimal', 'numeric', 'float', 'double', 'real'], true) => 'number',
            default => 'text',
        };
    }

    private function validationType(string $type): ?string
    {
        return match (true) {
            in_array($type, ['varchar', 'char', 'text', 'longtext', 'mediumtext', 'tinytext'], true) => 'string',
            in_array($type, ['integer', 'int', 'bigint', 'smallint', 'mediumint', 'tinyint'], true) => 'integer',
            in_array($type, ['decimal', 'numeric', 'float', 'double', 'real'], true) => 'numeric',
            in_array($type, ['bool', 'boolean'], true) => 'boolean',
            $type === 'date' => 'date',
            in_array($type, ['datetime', 'datetime2', 'timestamp'], true) => 'date_format:Y-m-d\TH:i',
            default => null,
        };
    }

    /** @return array{bool, string|null} */
    private function registerResourceRoute(string $routeFile, string $routePrefix, string $modelName, ?string $role = null): array
    {
        $original = File::get($routeFile);
        $content = $original;
        $roleLabel = trim((string) ($role ?? ''));
        $modelMarker = "// CRUD Routes for {$modelName}";
        $routeExists = str_contains($content, $modelMarker);

        $routePattern = '/Route\s*::\s*resource\s*\(\s*([\'\"])'.preg_quote($routePrefix, '/').'\1\s*,/';
        if (! $routeExists) {
            $routeExists = preg_match($routePattern, $content) === 1;
        }

        $registerPosition = strpos($content, 'PageRouter::register');

        if ($registerPosition !== false) {
            $openBracket = strpos($content, '[', $registerPosition);
            $closeBracket = $openBracket === false ? false : $this->matchingArrayBracket($content, $openBracket);
            if ($openBracket === false || $closeBracket === false) {
                return [false, 'Unable to safely update PageRouter options in app/routes/web.php.'];
            }

            $options = substr($content, $openBracket + 1, $closeBracket - $openBracket - 1);
            $excludePattern = '/([\'\"])exclude\1\s*=>\s*\[([^\]]*)\]/';
            $excludePaths = [$routePrefix, $routePrefix.'/*'];
            if (preg_match($excludePattern, $options, $excludeMatch) === 1) {
                $existing = trim($excludeMatch[2]);
                foreach ($excludePaths as $excludePath) {
                    if (str_contains($existing, "'{$excludePath}'") || str_contains($existing, '"'.$excludePath.'"')) {
                        continue;
                    }

                    $separator = $existing === '' ? '' : (str_ends_with($existing, ',') ? ' ' : ', ');
                    $existing .= $separator."'{$excludePath}'";
                }
                if ($existing !== trim($excludeMatch[2])) {
                    $replacement = str_replace($excludeMatch[2], $existing, $excludeMatch[0]);
                    $options = str_replace($excludeMatch[0], $replacement, $options);
                    $content = substr_replace($content, $options, $openBracket + 1, $closeBracket - $openBracket - 1);
                }
            } else {
                $content = substr_replace($content, "\n    'exclude' => ['{$routePrefix}', '{$routePrefix}/*'],".$options, $openBracket + 1, $closeBracket - $openBracket - 1);
            }
        }

        if (! $routeExists) {
            if ($roleLabel !== '') {
                $roleAlias = 'role.'.Str::snake($roleLabel);
                $routeBlock = "// CRUD Routes for {$modelName} (Role: {$roleLabel})\nRoute::middleware(['auth', '{$roleAlias}'])->group(function () {\n    Route::resource('{$routePrefix}', \\App\\Http\\Controllers\\{$modelName}Controller::class);\n});";
            } else {
                $routeBlock = "// CRUD Routes for {$modelName}\nRoute::resource('{$routePrefix}', \\App\\Http\\Controllers\\{$modelName}Controller::class);";
            }

            $content = rtrim($content)."\n\n{$routeBlock}\n";
        }

        if ($content !== $original) {
            File::put($routeFile, $content);
        }

        return [! $routeExists, null];
    }

    private function matchingArrayBracket(string $content, int $openBracket): int|false
    {
        $depth = 0;
        $quote = null;
        $length = strlen($content);

        for ($index = $openBracket; $index < $length; $index++) {
            $character = $content[$index];
            if ($quote !== null) {
                if ($character === '\\') {
                    $index++;
                } elseif ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === "'" || $character === '"') {
                $quote = $character;
            } elseif ($character === '[') {
                $depth++;
            } elseif ($character === ']' && --$depth === 0) {
                return $index;
            }
        }

        return false;
    }
}

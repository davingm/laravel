<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

final class AutoCrudCommand extends Command
{
    protected $signature = 'crud:generate {model : Model name matching a migrated table}';
    protected $aliases = ['devtools:crud'];
    protected $description = 'Generate a conventional Laravel CRUD from the active database schema';

    public function handle(): int
    {
        $model = Str::studly(trim((string) $this->argument('model')));
        if ($model === '' || !preg_match('/^[A-Z][A-Za-z0-9]*$/', $model)) {
            $this->error('Use a simple model name, for example Product.');
            return self::FAILURE;
        }

        $table = Str::snake(Str::pluralStudly($model));
        try {
            if (!Schema::hasTable($table)) {
                $this->error("Table {$table} does not exist. Run its migration first.");
                return self::FAILURE;
            }
            $columns = Schema::getColumns($table);
        } catch (Throwable $exception) {
            $this->error('Unable to inspect the active database: '.$exception->getMessage());
            return self::FAILURE;
        }

        $fillable = array_values(array_filter(array_map(
            static fn (array $column): string => (string) ($column['name'] ?? ''),
            $columns,
        ), static fn (string $name): bool => $name !== '' && !in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'], true)));
        $rules = [];
        foreach ($columns as $column) {
            $name = (string) ($column['name'] ?? '');
            if ($name === '' || !in_array($name, $fillable, true)) continue;
            $type = strtolower((string) ($column['type_name'] ?? $column['type'] ?? ''));
            $typeRule = match (true) {
                str_contains($type, 'int') => 'integer',
                str_contains($type, 'decimal'), str_contains($type, 'numeric'), str_contains($type, 'float'), str_contains($type, 'double') => 'numeric',
                str_contains($type, 'bool') => 'boolean',
                str_contains($type, 'date'), str_contains($type, 'time') => 'date',
                default => 'string',
            };
            $presenceRule = ($column['nullable'] ?? false) ? 'nullable' : (array_key_exists('default', $column) && $column['default'] !== null ? 'sometimes' : 'required');
            $rules[$name] = $presenceRule.'|'.$typeRule;
        }
        $routeFile = base_path('routes/web.php');
        if (!File::exists($routeFile)) {
            $this->error('routes/web.php was not found.');
            return self::FAILURE;
        }

        $this->create(app_path("Models/{$model}.php"), $this->modelSource($model, $fillable));
        $this->create(app_path("Http/Controllers/{$model}Controller.php"), $this->controllerSource($model, $table, $rules));
        $viewDirectory = resource_path('views/'.$this->slug($table));
        File::ensureDirectoryExists($viewDirectory);
        $this->create($viewDirectory.'/index.blade.php', $this->indexView($model, $table, $fillable));
        $this->create($viewDirectory.'/form.blade.php', $this->formView($model, $table, $fillable));

        $routes = File::get($routeFile);
        $uri = $this->slug($table);
        if (!str_contains($routes, "Route::resource('{$uri}'")) {
            File::append($routeFile, "\n\\Illuminate\\Support\\Facades\\Route::resource('{$uri}', \\App\\Http\\Controllers\\{$model}Controller::class);\n");
            $this->info("Added resource route: {$uri}");
        }
        $this->info("CRUD generated for {$model} from {$table}.");
        return self::SUCCESS;
    }

    private function create(string $path, string $contents): void
    {
        if (File::exists($path)) {
            $this->warn($path.' exists; skipped.');
            return;
        }
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $contents);
        $this->line('Created '.Str::after($path, base_path().DIRECTORY_SEPARATOR));
    }

    private function slug(string $table): string
    {
        return Str::kebab(str_replace('_', ' ', $table));
    }

    private function modelSource(string $model, array $fillable): string
    {
        return strtr(<<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class __MODEL__ extends Model
{
    protected $fillable = __FILLABLE__;
}
PHP, ['__MODEL__' => $model, '__FILLABLE__' => var_export($fillable, true)]);
    }

    private function controllerSource(string $model, string $table, array $rules): string
    {
        return strtr(<<<'PHP'
<?php

namespace App\Http\Controllers;

use App\Models\__MODEL__;
use Illuminate\Http\Request;

class __MODEL__Controller extends Controller
{
    public function index() { return view('__VIEW__.index', ['__PLURAL__' => __MODEL__::query()->latest()->paginate(15)]); }
    public function create() { return view('__VIEW__.form'); }
    public function store(Request $request) { __MODEL__::create($request->validate(__RULES__)); return redirect()->route('__ROUTE__.index'); }
    public function show(__MODEL__ $__VAR__) { return redirect()->route('__ROUTE__.edit', $__VAR__); }
    public function edit(__MODEL__ $__VAR__) { return view('__VIEW__.form', compact('__VAR__')); }
    public function update(Request $request, __MODEL__ $__VAR__) { $__VAR__->update($request->validate(__RULES__)); return redirect()->route('__ROUTE__.index'); }
    public function destroy(__MODEL__ $__VAR__) { $__VAR__->delete(); return redirect()->route('__ROUTE__.index'); }
}
PHP, [
            '__MODEL__' => $model,
            '__VIEW__' => $this->slug($table),
            '__PLURAL__' => Str::plural(Str::camel($model)),
            '__ROUTE__' => $this->slug($table),
            '__VAR__' => Str::camel($model),
            '__RULES__' => var_export($rules, true),
        ]);
    }

    private function indexView(string $model, string $table, array $fields): string
    {
        $var = Str::camel($model);
        $headers = implode("\n", array_map(static fn (string $field): string => '            <th>'.e(Str::headline($field)).'</th>', $fields));
        $cells = implode("\n", array_map(static fn (string $field): string => '                <td>{{ $'.$var.'->'.$field.' }}</td>', $fields));
        return strtr(<<<'BLADE'
<h1>__TITLE__</h1>
<a href="{{ route('__ROUTE__.create') }}">Create</a>
<table><thead><tr>__HEADERS__<th></th></tr></thead><tbody>
@foreach($__PLURAL__ as $__VAR__)
<tr>__CELLS__<td><a href="{{ route('__ROUTE__.edit', $__VAR__) }}">Edit</a></td></tr>
@endforeach
</tbody></table>
{{ $__PLURAL__->links() }}
BLADE, [
            '__TITLE__' => e(Str::headline(Str::plural($model))),
            '__ROUTE__' => $this->slug($table),
            '__HEADERS__' => $headers,
            '__PLURAL__' => '$'.Str::plural(Str::camel($model)),
            '__VAR__' => '$'.$var,
            '__CELLS__' => $cells,
        ]);
    }

    private function formView(string $model, string $table, array $fields): string
    {
        $var = Str::camel($model);
        $inputs = implode("\n", array_map(static fn (string $field): string => '    <label>'.e(Str::headline($field)).' <input name="'.$field.'" value="{{ old(\''.$field.'\', $'.$var.'->'.$field.' ?? \'\') }}"></label>', $fields));
        return strtr(<<<'BLADE'
<h1>{{ isset($__VAR__) ? 'Edit' : 'Create' }} __MODEL__</h1>
<form method="post" action="{{ isset($__VAR__) ? route('__ROUTE__.update', $__VAR__) : route('__ROUTE__.store') }}">
@csrf
@isset($__VAR__) @method('PUT') @endisset
__INPUTS__
<button type="submit">Save</button></form>
BLADE, [
            '__VAR__' => '$'.$var,
            '__MODEL__' => e($model),
            '__ROUTE__' => Str::kebab($table),
            '__INPUTS__' => $inputs,
        ]);
    }
}

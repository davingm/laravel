<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class MakeControllerCommand extends Command
{
    protected $signature = 'make:controller {name : Controller name} {--resource : Generate resource actions} {--model= : Model class to use} {--plain : Generate an empty controller}';
    protected $description = 'Create a Laravel controller';

    public function handle(): int
    {
        $base = Str::studly(Str::beforeLast(trim((string) $this->argument('name')), 'Controller'));
        if ($base === '') {
            $this->error('Controller name is required.');
            return self::FAILURE;
        }
        $class = $base.'Controller';
        $path = app_path("Http/Controllers/{$class}.php");
        if (File::exists($path)) {
            $this->error("Controller already exists: {$path}");
            return self::FAILURE;
        }

        $model = Str::studly((string) ($this->option('model') ?: $base));
        $source = $this->option('plain') ? $this->plainSource($class) : $this->resourceSource($class, $model);
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $source);
        $this->info("Controller created: app/Http/Controllers/{$class}.php");
        return self::SUCCESS;
    }

    private function plainSource(string $class): string
    {
        return strtr(<<<'PHP'
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class __CLASS__ extends Controller
{
    public function index() {}
}
PHP, ['__CLASS__' => $class]);
    }

    private function resourceSource(string $class, string $model): string
    {
        $variable = Str::camel($model);
        $plural = Str::plural($variable);
        $view = Str::kebab(str_replace('_', ' ', Str::snake(Str::pluralStudly($model))));
        $route = $view;
        return strtr(<<<'PHP'
<?php

namespace App\Http\Controllers;

use App\Models\__MODEL__;
use Illuminate\Http\Request;

class __CLASS__ extends Controller
{
    public function index() { return view('__VIEW__.index', ['__PLURAL__' => __MODEL__::query()->latest()->paginate(15)]); }
    public function create() { return view('__VIEW__.form'); }
    public function store(Request $request) { __MODEL__::create($request->all()); return redirect()->route('__ROUTE__.index'); }
    public function show(__MODEL__ $__VAR__) { return redirect()->route('__ROUTE__.edit', $__VAR__); }
    public function edit(__MODEL__ $__VAR__) { return view('__VIEW__.form', compact('__VAR__')); }
    public function update(Request $request, __MODEL__ $__VAR__) { $__VAR__->update($request->all()); return redirect()->route('__ROUTE__.index'); }
    public function destroy(__MODEL__ $__VAR__) { $__VAR__->delete(); return redirect()->route('__ROUTE__.index'); }
}
PHP, ['__MODEL__' => $model, '__CLASS__' => $class, '__VIEW__' => $view, '__PLURAL__' => $plural, '__ROUTE__' => $route, '__VAR__' => $variable]);
    }
}

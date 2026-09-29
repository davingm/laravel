<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeControllerCommand extends Command
{
    protected $signature = 'make:controller
        {name           : Controller name, e.g. "BarangController" or "Barang"}
        {--resource     : Generate a resource controller (default when using make:all)}
        {--model=       : The model class for type-hints (inferred from name when omitted)}
        {--plain        : Generate an empty controller without any methods}';

    protected $description = 'Create a new controller (davingm style with Frontend imports)';

    public function handle(): int
    {
        $name = $this->argument('name');

        // Normalise: ensure "Controller" suffix
        $baseName = Str::studly(Str::beforeLast($name, 'Controller') ?: $name);
        $className = $baseName.'Controller';

        $targetPath = app_path("Http/Controllers/{$className}.php");

        if (File::exists($targetPath)) {
            $this->line("  <fg=yellow>!</> Controller already exists: <fg=cyan>app/Http/Controllers/{$className}.php</>");

            return self::FAILURE;
        }

        // Model name — infer from controller name if not provided
        $modelName = $this->option('model')
            ? Str::studly($this->option('model'))
            : $baseName;

        $isResource = $this->option('resource');
        $isPlain = $this->option('plain');

        $stub = match (true) {
            $isPlain => $this->stubPlain($className),
            $isResource => $this->stubResource($className, $modelName),
            default => $this->stubResource($className, $modelName),
        };

        File::ensureDirectoryExists(dirname($targetPath));
        File::put($targetPath, $stub);

        $this->newLine();
        $this->line("  <fg=green>✓</> Controller created: <fg=cyan>app/Http/Controllers/{$className}.php</>");
        $this->newLine();

        return self::SUCCESS;
    }

    private function stubPlain(string $className): string
    {
        return <<<PHP
        <?php

        namespace App\Http\Controllers;

        use App\Support\Frontend;
        use Illuminate\Http\Request;

        class {$className} extends Controller
        {
            //
        }
        PHP;
    }

    private function stubResource(string $className, string $modelName): string
    {
        $modelVar = Str::camel($modelName);
        $modelPlural = Str::plural($modelVar);
        $routePrefix = Str::kebab(Str::plural($modelName));
        $viewPrefix = Str::kebab(Str::plural($modelName));
        $titleSingular = Str::headline($modelName);
        $titlePlural = Str::headline(Str::plural($modelName));
        $storeRequest = "Store{$modelName}Request";
        $updateRequest = "Update{$modelName}Request";

        return <<<PHP
        <?php

        namespace App\Http\Controllers;

        use App\Http\Requests\\{$storeRequest};
        use App\Http\Requests\\{$updateRequest};
        use App\Models\\{$modelName};
        use App\Support\Frontend;
        use Illuminate\Http\RedirectResponse;
        use Illuminate\View\View;

        class {$className} extends Controller
        {
            /**
             * Display a listing of the resource.
             */
            public function index(): View
            {
                return Frontend::render('{$viewPrefix}.index', [
                    'title' => '{$titlePlural} | '.config('app.name', 'Laravel'),
                    'description' => 'Kelola daftar {$titlePlural}.',
                    '{$modelPlural}' => {$modelName}::query()->latest()->paginate(10),
                ]);
            }

            /**
             * Show the form for creating a new resource.
             */
            public function create(): View
            {
                return Frontend::render('{$viewPrefix}.create', [
                    'title' => 'Tambah {$titleSingular} | '.config('app.name', 'Laravel'),
                ]);
            }

            /**
             * Store a newly created resource in storage.
             */
            public function store({$storeRequest} \$request): RedirectResponse
            {
                \${$modelVar} = {$modelName}::create(\$request->validated());

                return redirect()->route('{$routePrefix}.show', \${$modelVar})
                    ->with('success', '{$titleSingular} berhasil ditambahkan.');
            }

            /**
             * Display the specified resource.
             */
            public function show({$modelName} \${$modelVar}): View
            {
                return Frontend::render('{$viewPrefix}.show', [
                    'title' => \${$modelVar}->name.' | {$titleSingular}',
                    '{$modelVar}' => \${$modelVar},
                ]);
            }

            /**
             * Show the form for editing the specified resource.
             */
            public function edit({$modelName} \${$modelVar}): View
            {
                return Frontend::render('{$viewPrefix}.edit', [
                    'title' => 'Edit '.\${$modelVar}->name.' | {$titleSingular}',
                    '{$modelVar}' => \${$modelVar},
                ]);
            }

            /**
             * Update the specified resource in storage.
             */
            public function update({$updateRequest} \$request, {$modelName} \${$modelVar}): RedirectResponse
            {
                \${$modelVar}->update(\$request->validated());

                return redirect()->route('{$routePrefix}.show', \${$modelVar})
                    ->with('success', '{$titleSingular} berhasil diperbarui.');
            }

            /**
             * Remove the specified resource from storage.
             */
            public function destroy({$modelName} \${$modelVar}): RedirectResponse
            {
                \${$modelVar}->delete();

                return redirect()->route('{$routePrefix}.index')
                    ->with('success', '{$titleSingular} berhasil dihapus.');
            }
        }
        PHP;
    }
}

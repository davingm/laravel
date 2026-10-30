<?php

namespace Laravel\Generators;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeDashboardCommand extends Command
{
    protected $signature = 'make:dashboard {--layout=sidebar : Pilih tipe layout (sidebar atau navbar)}';

    protected $description = 'Generate a sidebar or navbar Blade layout and auto dashboard stats';

    public function handle(): int
    {
        $layout = (string) ($this->option('layout') ?: 'sidebar');
        if (! in_array($layout, ['sidebar', 'navbar'], true)) {
            $this->error('Layout must be sidebar or navbar.');

            return self::FAILURE;
        }


        if (! $this->generateLayout($layout)) {
            return self::FAILURE;
        }

        if (! $this->generateDashboardPage()) {
            return self::FAILURE;
        }

        $this->registerDashboardRoute($layout);

        return self::SUCCESS;
    }

    private function generateLayout(string $layout): bool
    {
        $layoutPath = base_path("src/layouts/{$layout}.blade.php");

        if (File::exists($layoutPath)) {
            $this->warn("src/layouts/{$layout}.blade.php already exists. Skipping to avoid overwriting it.");

            return true;
        }

        $stubPath = base_path("stubs/layouts/{$layout}.stub");
        $stubPath = base_path(".laravel/layouts/{$layout}.stub");
        if (! File::exists($stubPath)) {
            $this->error("Layout stub not found: .laravel/layouts/{$layout}.stub");

            return false;
        }

        File::ensureDirectoryExists(dirname($layoutPath));
        File::put($layoutPath, File::get($stubPath));

        $this->info("Dashboard layout generated at src/layouts/{$layout}.blade.php.");

        return true;
    }

    private function generateDashboardPage(): bool
    {
        $dashboardPath = base_path('src/pages/dashboard.blade.php');
        $widgetStubPath = base_path('stubs/layouts/dashboard_widget.stub');

        if (File::exists($dashboardPath)) {
            $this->warn('src/pages/dashboard.blade.php already exists. Skipping to avoid overwriting it.');

            return true;
        }

        if (! File::exists($widgetStubPath)) {
            $widgetStubPath = base_path('.laravel/layouts/dashboard_widget.stub');
        }

        if (! File::exists($widgetStubPath)) {
            $this->error('Widget stub not found: stubs/layouts/dashboard_widget.stub');

            return false;
        }

        $models = $this->discoverModels();
        $widgetStub = trim(File::get($widgetStubPath));
        $widgets = $this->buildWidgetsSection($models, $widgetStub);

        $content = <<<BLADE
<pages>
    <section class="space-y-8">
        <div>
            <h1 class="text-2xl font-semibold text-neutral-900">Dashboard</h1>
            <p class="mt-1 text-sm text-neutral-500">Ringkasan data otomatis dari model aplikasi.</p>
        </div>

{$widgets}
    </section>
</pages>
BLADE;

        File::ensureDirectoryExists(dirname($dashboardPath));
        File::put($dashboardPath, $content);

        $this->info('Dashboard stats page generated at src/pages/dashboard.blade.php.');

        if ($models === []) {
            $this->comment('No models found in app/Models (User.php is excluded). Empty stats grid generated.');
        } else {
            $this->comment('Stats widgets: '.implode(', ', $models));
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private function discoverModels(): array
    {
        $modelsPath = base_path('app/Models');

        if (! File::isDirectory($modelsPath)) {
            return [];
        }

        $models = [];

        foreach (File::files($modelsPath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $name = $file->getFilenameWithoutExtension();

            if ($name === 'User') {
                continue;
            }

            $models[] = $name;
        }

        sort($models);

        return $models;
    }

    /**
     * @param  list<string>  $models
     */
    private function buildWidgetsSection(array $models, string $widgetStub): string
    {
        if ($models === []) {
            return <<<'BLADE'
    <div class="rounded-lg border border-dashed border-neutral-300 bg-neutral-50 p-8 text-center text-sm text-neutral-500">
        Belum ada model untuk ditampilkan. Buat model di app/Models lalu jalankan ulang make:dashboard.
    </div>
BLADE;
        }

        $modelClasses = array_map(
            fn (string $model) => '            \\App\\Models\\'.$model.'::class,',
            $models,
        );
        $modelsList = implode("\n", $modelClasses);
        $indentedWidget = collect(explode("\n", $widgetStub))
            ->map(fn (string $line) => '            '.$line)
            ->implode("\n");

        return <<<BLADE
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ([
{$modelsList}
        ] as \$model)
{$indentedWidget}
        @endforeach
    </div>
BLADE;
    }

    private function registerDashboardRoute(string $layout): void
    {
        $routeFile = base_path('app/routes/web.php');

        if (! File::exists($routeFile)) {
            $this->warn('app/routes/web.php not found. Skipping dashboard route registration.');

            return;
        }

        $content = File::get($routeFile);

        if (preg_match("/Route\s*::\s*get\s*\(\s*['\"]\\/dashboard['\"]/", $content) === 1
            || str_contains($content, "->name('dashboard')")
            || str_contains($content, '->name("dashboard")')) {
            $this->comment('Dashboard route already exists. Skipping.');

            return;
        }

        $content = $this->injectDashboardExclude($content);

        $routeBlock = <<<PHP

// Auto Dashboard Stats
Route::get('/dashboard', fn () => page('dashboard', [], 'layouts.{$layout}'))->name('dashboard');
PHP;

        File::put($routeFile, rtrim($content)."\n{$routeBlock}\n");

        $this->info('Dashboard route registered at /dashboard.');
    }

    private function injectDashboardExclude(string $content): string
    {
        if (str_contains($content, "'dashboard'") || str_contains($content, '"dashboard"')) {
            return $content;
        }

        if (preg_match("/'exclude'\s*=>\s*\[([^\]]*)\]/", $content, $matches) === 1) {
            $existing = $matches[1];
            $new = rtrim(trim($existing), ',').($existing !== '' ? ', ' : '')."'dashboard'";

            return str_replace($matches[0], "'exclude' => [{$new}]", $content);
        }

        if (str_contains($content, 'PageRouter::register([')) {
            return preg_replace(
                '/(PageRouter::register\(\[)/',
                "$1\n    'exclude' => ['dashboard'],",
                $content,
                1,
            ) ?? $content;
        }

        return $content;
    }
}

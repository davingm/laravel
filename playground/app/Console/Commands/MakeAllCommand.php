<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeAllCommand extends Command
{
    protected $signature = 'make:all {name : Model name, e.g. "Barang" or "BlogPost"}';

    protected $description = 'Scaffold model, migration, factory, seeder, requests, controller, and resource route';

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));
        $routePrefix = Str::kebab(Str::plural($name));

        $this->newLine();
        $this->line("  <fg=red;options=bold>Scaffolding {$name}…</>");
        $this->newLine();

        // ── 1. Model + Migration + Factory + Seeder ───────────────────────────
        $this->step('Model, migration, factory, seeder');
        $this->callSilent('make:model', [
            'name' => $name,
            '--migration' => true,
            '--factory' => true,
            '--seed' => true,
            '--no-interaction' => true,
        ]);
        $this->stepDone('Model, migration, factory, seeder');

        // ── 2. Form Requests ──────────────────────────────────────────────────
        $this->step('Form requests');
        $this->callSilent('make:request', ['name' => "Store{$name}Request", '--no-interaction' => true]);
        $this->callSilent('make:request', ['name' => "Update{$name}Request", '--no-interaction' => true]);
        $this->stepDone('Form requests');

        // ── 3. Resource Controller (our version) ──────────────────────────────
        $this->step('Resource controller');
        $this->callSilent('make:controller', [
            'name' => "{$name}Controller",
            '--resource' => true,
            '--model' => $name,
        ]);
        $this->stepDone('Resource controller');

        // ── 4. Auto-inject Route::resource into web.php ───────────────────────
        $this->step('Resource route');
        $routeInjected = $this->injectRoute($name, $routePrefix);
        if ($routeInjected) {
            $this->stepDone('Resource route', "Route::resource('{$routePrefix}', ...)");
        } else {
            $this->stepSkipped('Resource route', 'already exists');
        }

        // ── Summary ───────────────────────────────────────────────────────────
        $this->newLine();
        $this->line("  <fg=green;options=bold>✓ Done!</> Files created for <fg=cyan>{$name}</>:");
        $this->newLine();
        $this->line("  <fg=gray>  app/Models/{$name}.php</>");
        $this->line('  <fg=gray>  database/migrations/*_create_'.Str::snake(Str::plural($name)).'_table.php</>');
        $this->line("  <fg=gray>  database/factories/{$name}Factory.php</>");
        $this->line("  <fg=gray>  database/seeders/{$name}Seeder.php</>");
        $this->line("  <fg=gray>  app/Http/Requests/Store{$name}Request.php</>");
        $this->line("  <fg=gray>  app/Http/Requests/Update{$name}Request.php</>");
        $this->line("  <fg=gray>  app/Http/Controllers/{$name}Controller.php</>");
        $this->newLine();
        $this->line('  <fg=yellow>Next steps:</>');
        $this->line('  <fg=gray>  1. Fill in the migration columns</>');
        $this->line("  <fg=gray>  2. Add #[Fillable([...])] to app/Models/{$name}.php</>");
        $this->line('  <fg=gray>  3. Add validation rules to Store/Update requests</>');
        $this->line('  <fg=gray>  4. Run: php artisan migrate</>');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Inject a Route::resource line into routes/web.php.
     * Inserts just before PageRouter::register() so the route is registered first.
     * Returns false if the route already exists.
     */
    private function injectRoute(string $modelName, string $routePrefix): bool
    {
        $webPhpPath = base_path('app/routes/web.php');
        $content = File::get($webPhpPath);

        // Check if already exists
        if (str_contains($content, "Route::resource('{$routePrefix}'")) {
            return false;
        }

        $controllerClass = "App\\Http\\Controllers\\{$modelName}Controller";
        $routeLine = "Route::resource('{$routePrefix}', {$controllerClass}::class);";

        // Append resource route to routes/web.php after PageRouter::register
        $content = rtrim($content)."\n".$routeLine."\n";

        // Also inject exclude for this resource into PageRouter::register options
        $content = $this->injectExclude($content, $routePrefix);

        File::put($webPhpPath, $content);

        return true;
    }

    /**
     * Add the route prefix to PageRouter::register exclude list.
     * Handles both empty and existing exclude arrays.
     */
    private function injectExclude(string $content, string $routePrefix): string
    {
        // Already excluded
        if (str_contains($content, "'{$routePrefix}'")) {
            return $content;
        }

        // Has existing exclude array: 'exclude' => ['foo', 'bar']
        if (preg_match("/'exclude'\s*=>\s*\[([^\]]*)\]/", $content, $matches)) {
            $existing = $matches[1];
            $new = rtrim(trim($existing), ',').($existing !== '' ? ', ' : '')."'{$routePrefix}'";

            return str_replace($matches[0], "'exclude' => [{$new}]", $content);
        }

        // No exclude option yet — add it
        // Find PageRouter::register([ and inject after the opening bracket
        return preg_replace(
            "/(PageRouter::register\(\[)/",
            "$1\n    'exclude' => ['{$routePrefix}'],",
            $content,
        ) ?? $content;
    }

    private function step(string $label): void
    {
        $this->line("  <fg=gray>…</> {$label}");
    }

    private function stepDone(string $label, string $extra = ''): void
    {
        $suffix = $extra !== '' ? " <fg=gray>{$extra}</>" : '';
        $this->output->write("\x1b[1A\r");
        $this->line("  <fg=green>✓</> {$label}{$suffix}");
    }

    private function stepSkipped(string $label, string $extra = ''): void
    {
        $suffix = $extra !== '' ? " <fg=gray>{$extra}</>" : '';
        $this->output->write("\x1b[1A\r");
        $this->line("  <fg=gray>–</> {$label} <fg=gray>skipped</>{$suffix}");
    }
}

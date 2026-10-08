<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeRolesCommand extends Command
{
    protected $signature = 'make:roles {roles* : One or more role names, e.g. admin author editor}';

    protected $description = 'Generate role-based middleware files and register their route aliases';

    public function handle(): int
    {
        $roles = array_values(array_filter(array_map(fn ($role) => trim((string) $role), $this->argument('roles'))));

        if ($roles === []) {
            $this->error('Please provide at least one role name. Example: php artisan make:roles admin author editor');

            return self::FAILURE;
        }

        $stubPath = base_path('stubs/roles/middleware.stub');
        if (! File::exists($stubPath)) {
            $this->error('Middleware stub not found: stubs/roles/middleware.stub');

            return self::FAILURE;
        }

        $middlewareDirectory = app_path('Http/Middleware');
        File::ensureDirectoryExists($middlewareDirectory);

        $newAliases = [];

        foreach ($roles as $roleName) {
            $className = 'EnsureUserIs'.Str::studly($roleName).'Middleware';
            $roleKey = Str::snake($roleName);
            $alias = 'role.'.$roleKey;
            $filePath = $middlewareDirectory.'/'.$className.'.php';

            if (File::exists($filePath)) {
                $this->line("  <fg=yellow>!</> {$className}.php already exists. Skipping...");
            } else {
                $contents = strtr(File::get($stubPath), [
                    '{{CLASS_NAME}}' => $className,
                    '{{ROLE}}' => strtolower($roleName),
                ]);

                File::put($filePath, $contents);
                $this->line("  <fg=green>✓</> {$className}.php created");
            }

            $newAliases[$alias] = '\\App\\Http\\Middleware\\'.$className.'::class';
        }

        $this->registerAliases($newAliases);
        $this->newLine();
        $this->info('Role middleware generated successfully.');

        return self::SUCCESS;
    }

    /** @param  array<string, string>  $aliases */
    private function registerAliases(array $aliases): void
    {
        $bootstrapPath = base_path('bootstrap/app.php');
        if (! File::exists($bootstrapPath)) {
            $this->error('bootstrap/app.php not found. Cannot register middleware aliases.');

            return;
        }

        $content = File::get($bootstrapPath);
        $pending = [];
        foreach ($aliases as $name => $class) {
            $pattern = "/['\"]".preg_quote($name, '/')."['\"]\s*=>/";
            if (preg_match($pattern, $content) === 1) {
                continue;
            }

            $pending[] = "            '{$name}' => {$class},";
        }

        if ($pending === []) {
            return;
        }

        $marker = '->withMiddleware(function (Middleware $middleware): void {';
        if (! str_contains($content, $marker)) {
            $this->warn('The Laravel bootstrap middleware closure was not found. No aliases were registered.');

            return;
        }

        $replacement = $marker."\n        \$middleware->alias([\n".implode("\n", $pending)."\n        ]);";
        $updated = str_replace($marker, $replacement, $content, $count);

        if ($count === 0) {
            $this->warn('Middleware alias block could not be inserted safely.');

            return;
        }

        File::put($bootstrapPath, $updated);
        $this->line('  <fg=green>✓</> Middleware aliases registered in bootstrap/app.php');
    }
}

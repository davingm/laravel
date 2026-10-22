<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class MakePageCommand extends Command
{
    protected $signature = 'make:page {name : View path, for example about or admin/users} {--force : Overwrite an existing view}';
    protected $description = 'Create a Blade page view and its web route';

    public function handle(): int
    {
        $name = trim(str_replace('\\', '/', (string) $this->argument('name')), '/');
        $segments = array_filter(explode('/', str_replace('.', '/', $name)), static fn (string $part): bool => $part !== '');
        if ($segments === [] || in_array('..', $segments, true)) {
            $this->error('Provide a valid view name.');
            return self::FAILURE;
        }

        $view = implode('.', $segments);
        $path = resource_path('views/'.implode('/', $segments).'.blade.php');
        if (File::exists($path) && !$this->option('force')) {
            $this->error("View already exists: {$path}");
            return self::FAILURE;
        }
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $this->viewSource((string) end($segments)));

        $routeFile = base_path('routes/web.php');
        if (!File::exists($routeFile)) {
            $this->error('routes/web.php was not found.');
            return self::FAILURE;
        }
        $uri = implode('/', array_map(static fn (string $part): string => Str::kebab($part), $segments));
        $routeName = str_replace('/', '.', $uri);
        $routeLine = "\\Illuminate\\Support\\Facades\\Route::view('{$uri}', '{$view}')->name('{$routeName}');";
        if (!str_contains(File::get($routeFile), $routeLine)) File::append($routeFile, "\n{$routeLine}\n");

        $this->info("Created resources/views/".implode('/', $segments).'.blade.php');
        $this->info("Registered GET /{$uri}");
        return self::SUCCESS;
    }

    private function viewSource(string $name): string
    {
        $title = e(Str::headline($name));
        return "<!doctype html>\n<html lang=\"{{ str_replace('_', '-', app()->getLocale()) }}\">\n<head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"><title>{$title}</title></head>\n<body><main><h1>{$title}</h1></main></body>\n</html>\n";
    }
}

<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class MakeAllCommand extends Command
{
    protected $signature = 'make:all {name : Model name}';
    protected $description = 'Scaffold model, migration, factory, seeder, requests, controller, and resource route';

    public function handle(): int
    {
        $name = Str::studly(trim((string) $this->argument('name')));
        if ($name === '') {
            $this->error('Model name is required.');
            return self::FAILURE;
        }
        $results = [
            Artisan::call('make:model', ['name' => $name, '--migration' => true, '--factory' => true, '--seed' => true]),
            Artisan::call('make:request', ['name' => "Store{$name}Request"]),
            Artisan::call('make:request', ['name' => "Update{$name}Request"]),
            Artisan::call('make:controller', ['name' => "{$name}Controller", '--resource' => true, '--model' => $name]),
        ];
        foreach ($results as $result) if ($result !== self::SUCCESS) return self::FAILURE;

        $route = Str::kebab(Str::pluralStudly($name));
        $routeFile = base_path('routes/web.php');
        if (!File::exists($routeFile)) {
            $this->error('routes/web.php was not found. Generated classes remain in place.');
            return self::FAILURE;
        }
        $line = "\\Illuminate\\Support\\Facades\\Route::resource('{$route}', \\App\\Http\\Controllers\\{$name}Controller::class);";
        if (!str_contains(File::get($routeFile), $line)) File::append($routeFile, "\n{$line}\n");
        $this->info("Scaffolding completed for {$name}. Run php artisan migrate when the migration is ready.");
        return self::SUCCESS;
    }
}

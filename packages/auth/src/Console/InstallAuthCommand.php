<?php

namespace AutoLaravel\Auth\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class InstallAuthCommand extends Command
{
    protected $signature = 'auth:install {--force : Replace existing package files}';

    protected $description = 'Install AutoLaravel username and password authentication';

    public function handle(Filesystem $files): int
    {
        $configPath = base_path('config/auto-auth.php');
        $migrationDirectory = database_path('migrations');
        $existingMigrations = $files->glob($migrationDirectory.'/*_add_username_to_users_table.php');
        $migrationPath = $existingMigrations[0] ?? $migrationDirectory.'/'.now()->format('Y_m_d_His').'_add_username_to_users_table.php';

        if (($files->exists($configPath) || $existingMigrations !== []) && ! $this->option('force')) {
            $this->components->error('Authentication is already installed. Use --force to replace the package configuration.');

            return self::FAILURE;
        }

        $files->ensureDirectoryExists(dirname($configPath));
        $files->put($configPath, $files->get(__DIR__.'/../../stubs/auto-auth.php'));
        $files->put($migrationPath, $files->get(__DIR__.'/../../stubs/add_username_to_users_table.php'));

        $this->components->info('Authentication installed. Run `php artisan migrate`, then add a unique username to each user.');
        $this->line('  Login: /login');
        $this->line('  Protected page: /dashboard');

        return self::SUCCESS;
    }
}

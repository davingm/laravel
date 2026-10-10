<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeDashboardCommand extends Command
{
    protected $signature = 'make:dashboard {--layout=sidebar : Pilih tipe layout (sidebar atau navbar)}';

    protected $description = 'Generate a sidebar or navbar Blade layout';

    public function handle(): int
    {
        $layoutPath = base_path('src/layouts/app.blade.php');

        if (File::exists($layoutPath)) {
            $this->warn('src/layouts/app.blade.php already exists. Skipping to avoid overwriting it.');

            return self::SUCCESS;
        }

        $layout = (string) ($this->option('layout') ?: 'sidebar');
        if (! in_array($layout, ['sidebar', 'navbar'], true)) {
            $this->error('Layout must be sidebar or navbar.');

            return self::FAILURE;
        }

        $stubPath = base_path("stubs/layouts/{$layout}.stub");
        if (! File::exists($stubPath)) {
            $this->error("Layout stub not found: stubs/layouts/{$layout}.stub");

            return self::FAILURE;
        }

        File::ensureDirectoryExists(dirname($layoutPath));
        File::put($layoutPath, File::get($stubPath));

        $this->info('Dashboard layout generated at src/layouts/app.blade.php.');

        return self::SUCCESS;
    }
}
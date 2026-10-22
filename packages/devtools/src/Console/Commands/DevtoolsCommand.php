<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Console\Commands;

use Illuminate\Console\Command;

final class DevtoolsCommand extends Command
{
    protected $signature = 'devtools';
    protected $description = 'Open the interactive Laravel Devtools menu';

    public function handle(): int
    {
        while (true) {
            $choices = [
                'Start development tools', 'Generate CRUD from database', 'Generate full model scaffold',
                'Generate controller', 'Generate migration', 'Generate Blade page', 'Generate dashboard layout',
                'Generate role middleware', 'List routes', 'Clear application caches', 'Exit',
            ];
            $choice = $this->choice('Laravel Devtools', $choices, 'Exit');
            if ($choice === 'Exit') return self::SUCCESS;
            if ($choice === 'Start development tools') {
                $this->call('dev', ['--no-tui' => $this->confirm('Disable terminal dashboard?', false)]);
                return self::SUCCESS;
            }
            if ($choice === 'Generate CRUD from database') {
                $this->call('crud:generate', ['model' => $this->ask('Model name (for example Product)')]);
            } elseif ($choice === 'Generate full model scaffold') {
                $this->call('make:all', ['name' => $this->ask('Model name')]);
            } elseif ($choice === 'Generate controller') {
                $name = $this->ask('Controller name');
                $this->call('make:controller', ['name' => $name, '--resource' => true]);
            } elseif ($choice === 'Generate migration') {
                $this->call('make:migration', ['name' => $this->ask('Table name')]);
            } elseif ($choice === 'Generate Blade page') {
                $this->call('make:page', ['name' => $this->ask('Page path, for example about/team')]);
            } elseif ($choice === 'Generate dashboard layout') {
                $this->call('make:dashboard', ['--layout' => $this->choice('Layout', ['sidebar', 'navbar'], 'sidebar')]);
            } elseif ($choice === 'Generate role middleware') {
                $roles = $this->ask('Role names separated by spaces');
                $this->call('make:roles', ['roles' => preg_split('/\s+/', trim((string) $roles))]);
            } elseif ($choice === 'List routes') {
                $this->call('route:list');
            } else {
                $this->call('optimize:clear');
            }
            $this->newLine();
        }
    }
}

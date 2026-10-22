<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

final class DevtoolsCommand extends Command
{
    protected $signature = 'devtools';
    protected $description = 'Open the interactive Laravel Devtools menu';

    public function handle(): int
    {
        while (true) {
            $choice = $this->choice('Laravel Devtools', ['Generate CRUD from database', 'List routes', 'Clear application caches', 'Exit'], 'Exit');
            if ($choice === 'Exit') return self::SUCCESS;
            if ($choice === 'Generate CRUD from database') {
                $model = $this->ask('Model name (for example Product)');
                if ($model) $this->call('crud:generate', ['model' => $model]);
            } elseif ($choice === 'List routes') {
                $this->call('route:list');
            } else {
                $this->call('optimize:clear');
            }
            $this->newLine();
        }
    }
}

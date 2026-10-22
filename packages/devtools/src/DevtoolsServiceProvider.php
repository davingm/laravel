<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools;

use Davingm\LaravelDevtools\Console\Commands\AutoCrudCommand;
use Davingm\LaravelDevtools\Console\Commands\DevtoolsCommand;
use Illuminate\Support\ServiceProvider;

final class DevtoolsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([AutoCrudCommand::class, DevtoolsCommand::class]);
        }
    }
}

<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools;

use Davingm\LaravelDevtools\Console\Commands\AutoCrudCommand;
use Davingm\LaravelDevtools\Console\Commands\DevCommand;
use Davingm\LaravelDevtools\Console\Commands\DevtoolsCommand;
use Davingm\LaravelDevtools\Console\Commands\MakeAllCommand;
use Davingm\LaravelDevtools\Console\Commands\MakeControllerCommand;
use Davingm\LaravelDevtools\Console\Commands\MakeDashboardCommand;
use Davingm\LaravelDevtools\Console\Commands\MakeMigrationCommand;
use Davingm\LaravelDevtools\Console\Commands\MakePageCommand;
use Davingm\LaravelDevtools\Console\Commands\MakeRolesCommand;
use Illuminate\Support\ServiceProvider;

final class DevtoolsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AutoCrudCommand::class,
                DevCommand::class,
                DevtoolsCommand::class,
                MakeAllCommand::class,
                MakeControllerCommand::class,
                MakeDashboardCommand::class,
                MakeMigrationCommand::class,
                MakePageCommand::class,
                MakeRolesCommand::class,
            ]);
        }
    }
}

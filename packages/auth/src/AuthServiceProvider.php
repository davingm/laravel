<?php

namespace AutoLaravel\Auth;

use AutoLaravel\Auth\Console\InstallAuthCommand;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Package configuration is activated explicitly by auth:install.
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'auto-auth');

        if (is_dir(base_path('src'))) {
            $this->loadViewsFrom(base_path('src'), 'auto-src');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([InstallAuthCommand::class]);
        }

        if (config('auto-auth.enabled', false)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }
    }
}

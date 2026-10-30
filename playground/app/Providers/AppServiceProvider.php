<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register src/components for x-component syntax (anonymous components)
        Blade::anonymousComponentPath(base_path('src/components'));

        // Register the package's anonymous component as <x-welcomepage />.
        foreach ([
            base_path('node_modules/@davingm/welcome/blade'),
            base_path('node_modules/davingm-welcome/blade'),
        ] as $welcomeBladePath) {
            if (is_dir($welcomeBladePath)) {
                Blade::anonymousComponentPath($welcomeBladePath);
                break;
            }
        }

    }
}

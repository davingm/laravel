<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FrameworkPackageDiscoveryTest extends TestCase
{
    public function test_framework_commands_and_default_routes_are_registered_by_the_package(): void
    {
        $commands = Artisan::all();

        foreach (['build', 'preview', 'frontend:generate', 'make:page', 'make:dashboard', 'crud:generate'] as $name) {
            $this->assertArrayHasKey($name, $commands);
        }

        $this->assertTrue(Route::has('pages'));
        $this->assertTrue(Route::has('laravel.cli.telemetry'));
        $this->assertFalse(class_exists(\App\Providers\AppServiceProvider::class));
    }
}

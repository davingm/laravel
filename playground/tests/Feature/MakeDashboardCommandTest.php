<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MakeDashboardCommandTest extends TestCase
{
    public function test_it_generates_sidebar_layout_by_default(): void
    {
        $originalBasePath = $this->app->basePath();
        $temporaryBasePath = $this->createTemporaryProject();

        try {
            $this->app->setBasePath($temporaryBasePath);

            $this->artisan('make:dashboard')
                ->expectsOutputToContain('Dashboard layout generated')
                ->assertExitCode(0);

            $layout = File::get($temporaryBasePath.'/src/layouts/app.blade.php');
            $this->assertStringContainsString('<aside', $layout);
            $this->assertStringContainsString('id="page-view"', $layout);
            $this->assertStringContainsString("@yield('content')", $layout);
        } finally {
            $this->app->setBasePath($originalBasePath);
            File::deleteDirectory($temporaryBasePath);
        }
    }

    public function test_it_generates_navbar_layout_when_selected(): void
    {
        $originalBasePath = $this->app->basePath();
        $temporaryBasePath = $this->createTemporaryProject();

        try {
            $this->app->setBasePath($temporaryBasePath);

            $this->artisan('make:dashboard', ['--layout' => 'navbar'])
                ->expectsOutputToContain('Dashboard layout generated')
                ->assertExitCode(0);

            $layout = File::get($temporaryBasePath.'/src/layouts/app.blade.php');
            $this->assertStringContainsString('<header', $layout);
            $this->assertStringContainsString('id="page-view"', $layout);
            $this->assertStringContainsString("@yield('content')", $layout);
        } finally {
            $this->app->setBasePath($originalBasePath);
            File::deleteDirectory($temporaryBasePath);
        }
    }

    public function test_it_does_not_overwrite_an_existing_layout(): void
    {
        $originalBasePath = $this->app->basePath();
        $temporaryBasePath = $this->createTemporaryProject();
        $layoutPath = $temporaryBasePath.'/src/layouts/app.blade.php';
        $existingLayout = 'Existing user layout';
        File::ensureDirectoryExists(dirname($layoutPath));
        File::put($layoutPath, $existingLayout);

        try {
            $this->app->setBasePath($temporaryBasePath);

            $this->artisan('make:dashboard', ['--layout' => 'navbar'])
                ->expectsOutputToContain('already exists')
                ->assertExitCode(0);

            $this->assertSame($existingLayout, File::get($layoutPath));
        } finally {
            $this->app->setBasePath($originalBasePath);
            File::deleteDirectory($temporaryBasePath);
        }
    }

    private function createTemporaryProject(): string
    {
        $temporaryBasePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'auto-dashboard-'.bin2hex(random_bytes(8));
        File::ensureDirectoryExists($temporaryBasePath);
        File::copyDirectory(base_path('stubs/layouts'), $temporaryBasePath.'/stubs/layouts');

        return $temporaryBasePath;
    }
}
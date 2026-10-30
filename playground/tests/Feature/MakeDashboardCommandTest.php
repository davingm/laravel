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

    public function test_it_generates_dashboard_stats_from_models(): void
    {
        $originalBasePath = $this->app->basePath();
        $temporaryBasePath = $this->createTemporaryProject([
            'Product' => <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
}
PHP,
            'Order' => <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
}
PHP,
            'User' => <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
}
PHP,
        ]);

        try {
            $this->app->setBasePath($temporaryBasePath);

            $this->artisan('make:dashboard')
                ->expectsOutputToContain('Dashboard stats page generated')
                ->assertExitCode(0);

            $dashboardPath = $temporaryBasePath.'/src/pages/dashboard.blade.php';
            $this->assertTrue(File::exists($dashboardPath));

            $dashboard = File::get($dashboardPath);
            $this->assertStringContainsString("@extends('layouts.app')", $dashboard);
            $this->assertStringContainsString('\\App\\Models\\Product::class', $dashboard);
            $this->assertStringContainsString('\\App\\Models\\Order::class', $dashboard);
            $this->assertStringContainsString('{{ $model::count() }}', $dashboard);
            $this->assertStringNotContainsString('\\App\\Models\\User::class', $dashboard);

            $routes = File::get($temporaryBasePath.'/app/routes/web.php');
            $this->assertStringContainsString("Route::get('/dashboard'", $routes);
            $this->assertStringContainsString("->name('dashboard')", $routes);
            $this->assertStringContainsString("'dashboard'", $routes);
        } finally {
            $this->app->setBasePath($originalBasePath);
            File::deleteDirectory($temporaryBasePath);
        }
    }

    public function test_it_does_not_overwrite_an_existing_dashboard_page(): void
    {
        $originalBasePath = $this->app->basePath();
        $temporaryBasePath = $this->createTemporaryProject([
            'Product' => <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
}
PHP,
        ]);
        $dashboardPath = $temporaryBasePath.'/src/pages/dashboard.blade.php';
        $existingDashboard = 'Existing dashboard page';
        File::ensureDirectoryExists(dirname($dashboardPath));
        File::put($dashboardPath, $existingDashboard);

        try {
            $this->app->setBasePath($temporaryBasePath);

            $this->artisan('make:dashboard')
                ->expectsOutputToContain('src/pages/dashboard.blade.php already exists')
                ->assertExitCode(0);

            $this->assertSame($existingDashboard, File::get($dashboardPath));
        } finally {
            $this->app->setBasePath($originalBasePath);
            File::deleteDirectory($temporaryBasePath);
        }
    }

    /**
     * @param  array<string, string>  $models
     */
    private function createTemporaryProject(array $models = []): string
    {
        $temporaryBasePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'auto-dashboard-'.bin2hex(random_bytes(8));
        File::ensureDirectoryExists($temporaryBasePath);
        File::copyDirectory(base_path('.laravel/layouts'), $temporaryBasePath.'/.laravel/layouts');

        File::ensureDirectoryExists($temporaryBasePath.'/app/Models');
        foreach ($models as $name => $contents) {
            File::put($temporaryBasePath.'/app/Models/'.$name.'.php', $contents);
        }

        File::ensureDirectoryExists($temporaryBasePath.'/app/routes');
        File::put($temporaryBasePath.'/app/routes/web.php', <<<'PHP'
<?php

use Davingm\LaravelRuntime\App\Support\PageRouter;

PageRouter::register([
    'exclude' => ['mahasiswas', 'mahasiswas/*'],
    'middleware' => ['web'],
]);
PHP);

        return $temporaryBasePath;
    }
}

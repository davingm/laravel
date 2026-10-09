<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PageRouterTest extends TestCase
{
    public function test_page_router_resolves_nested_page_paths(): void
    {
        $this->assertSame(
            ['/about', 'about.index', 'about', 'pages.about'],
            \App\Support\PageRouter::resolve(
                base_path('src/pages/about/index.blade.php'),
                base_path('src/pages'),
            ),
        );
    }

    public function test_page_router_supports_exact_and_wildcard_exclusions(): void
    {
        $this->assertTrue(\App\Support\PageRouter::isExcluded('admin', '/admin', ['admin']));
        $this->assertFalse(\App\Support\PageRouter::isExcluded('admin.users', '/admin/users', ['admin']));
        $this->assertTrue(\App\Support\PageRouter::isExcluded('admin.users', '/admin/users', ['admin/*']));
    }

    public function test_make_page_preserves_an_existing_route_cache(): void
    {
        $pagePath = base_path('src/pages/generated-cache-test.blade.php');
        $routesCachePath = app()->getCachedRoutesPath();
        $originalRoutesCache = File::exists($routesCachePath) ? File::get($routesCachePath) : null;
        File::put($routesCachePath, 'stale route cache');

        try {
            $this->artisan('make:page', ['name' => 'generated-cache-test'])
                ->expectsOutputToContain('Route cache was preserved and may be stale')
                ->assertExitCode(0);

            $generatedPage = File::get($pagePath);
            $this->assertStringContainsString("'ogTitle' => 'Generated Cache Test'", $generatedPage);
            $this->assertStringContainsString('<x-seo-meta :seo="$seo" />', $generatedPage);
            $this->assertStringNotContainsString('<meta property=', $generatedPage);
            $this->assertSame('stale route cache', File::get($routesCachePath));
        } finally {
            File::delete($pagePath);

            if ($originalRoutesCache !== null) {
                File::put($routesCachePath, $originalRoutesCache);
            } else {
                File::delete($routesCachePath);
            }
        }
    }

    public function test_make_page_does_not_warn_when_no_route_cache_exists(): void
    {
        $pagePath = base_path('src/pages/generated-no-cache-test.blade.php');
        $routesCachePath = app()->getCachedRoutesPath();
        $originalRoutesCache = File::exists($routesCachePath) ? File::get($routesCachePath) : null;
        File::delete($routesCachePath);

        try {
            $this->artisan('make:page', ['name' => 'generated-no-cache-test'])
                ->expectsOutputToContain('Page created:')
                ->assertExitCode(0);

            $this->assertFileDoesNotExist($routesCachePath);
        } finally {
            File::delete($pagePath);

            if ($originalRoutesCache !== null) {
                File::put($routesCachePath, $originalRoutesCache);
            } else {
                File::delete($routesCachePath);
            }
        }
    }

}

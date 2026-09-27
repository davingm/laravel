<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PageRouterTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_is_accessible(): void
    {
        $response = $this->get('/home');

        $response->assertOk();
    }

    public function test_help_page_is_accessible(): void
    {
        $response = $this->get('/help');

        $response->assertOk();
    }

    public function test_help_page_renders_when_frontend_manifest_is_missing(): void
    {
        $manifestPath = storage_path('../.davingm/cache/manifest.json');
        $originalManifest = File::exists($manifestPath) ? File::get($manifestPath) : null;
        File::delete($manifestPath);

        try {
            $response = $this->get('/help');

            $response->assertOk();
            $response->assertSee('<title>Help</title>', false);
            $response->assertSee('<meta property="og:title" content="Help">', false);
            $response->assertSee('Halaman bantuan dan informasi.');
        } finally {
            if ($originalManifest !== null) {
                File::ensureDirectoryExists(dirname($manifestPath));
                File::put($manifestPath, $originalManifest);
            }
        }
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

    public function test_preview_mode_minifies_html_while_dev_mode_does_not(): void
    {
        // Dev mode: multi-line HTML, no aggressive minification
        $devResponse = $this->get('/help');
        $devResponse->assertOk();
        $this->assertStringContainsString("\n", $devResponse->getContent());

        // Preview mode: single-line minified HTML
        putenv('DAVINGM_PREVIEW=1');
        $_SERVER['DAVINGM_PREVIEW'] = '1';

        $previewResponse = $this->get('/help');
        $previewResponse->assertOk();
        $this->assertStringNotContainsString('id="browser-logger-active"', $previewResponse->getContent());

        putenv('DAVINGM_PREVIEW');
        unset($_SERVER['DAVINGM_PREVIEW']);
    }
}

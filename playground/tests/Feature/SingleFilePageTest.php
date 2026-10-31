<?php

namespace Tests\Feature;

use Davingm\LaravelRuntime\Compiler\Runtime\SingleFilePageCompiler;
use Davingm\LaravelRuntime\App\Middleware\MinifyHtmlMiddleware;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class SingleFilePageTest extends TestCase
{
    public function test_single_file_page_keeps_blade_expressions_and_adds_its_scope(): void
    {
        $source = <<<'BLADE'
<script setup lang="ts">const count = 0</script>
<style scoped>.count { color: red; }</style>
<pages><p>{{ $name }}</p></pages>
BLADE;

        $compiled = SingleFilePageCompiler::prepareBlade($source);

        $this->assertStringContainsString('<div data-page-scope="'.substr(hash('sha256', $source), 0, 12).'">', $compiled);
        $this->assertStringNotContainsString('<pages', $compiled);
        $this->assertStringContainsString('{{ $name }}', $compiled);
        $this->assertStringNotContainsString('<script', $compiled);
        $this->assertStringNotContainsString('<style', $compiled);
    }

    public function test_application_entry_routes_the_page_through_the_selected_layout(): void
    {
        $compiled = SingleFilePageCompiler::prepareBlade(<<<'BLADE'
<pages>
    <laravelLayoute>
        <PageRouter />
    </laravelLayoute>
</pages>
BLADE);

        $this->assertSame("@extends(\$frontendLayout)\n@section('content')\n{!! \$pageContent !!}\n@endsection", $compiled);
    }

    public function test_legacy_app_layout_reference_falls_back_to_the_registered_default(): void
    {
        $this->assertSame(
            "@extends('layouts.default')\n@section('content')legacy content@endsection",
            SingleFilePageCompiler::prepareBlade("@extends('layouts.app')\n@section('content')legacy content@endsection"),
        );
    }

    public function test_single_file_page_can_omit_typescript_setup(): void
    {
        $compiled = SingleFilePageCompiler::prepareBlade('<pages><p>Plain page</p></pages>');

        $this->assertStringContainsString('<div data-page-scope="', $compiled);
        $this->assertStringContainsString('<p>Plain page</p>', $compiled);
        $this->assertStringNotContainsString('<pages', $compiled);
    }

    public function test_home_single_file_page_renders_inside_the_framework_layout_with_default_seo(): void
    {
        $response = $this->get('/home');

        $response
            ->assertOk()
            ->assertSee('<title>Home | Laravel</title>', false)
            ->assertSee('<meta name="description" content="Halaman Home.">', false)
            ->assertSee('<meta property="og:title" content="Home">', false)
            ->assertSee('<meta property="og:image" content="'.asset('Laravel.svg').'">', false)
            ->assertSee('<html lang="en">', false)
            ->assertSee('<div data-page-scope="', false)
            ->assertDontSee('<pages', false)
            ->assertSee('Klik saya')
            ->assertDontSee('<script setup lang="ts">', false)
            ->assertDontSee('<style scoped>', false)
            ->assertDontSee('@extends', false);

    }

    public function test_typescript_seo_composable_metadata_is_rendered_server_side(): void
    {
        $path = base_path('.laravel/generated/pages-seo.json');
        $hadManifest = File::exists($path);
        $originalManifest = $hadManifest ? File::get($path) : null;
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode([
            'about.me' => [
                'title' => 'Frontend Engineer & Web Developer',
                'description' => 'Portofolio Davin GM.',
                'ogTitle' => 'Davin GM — Web Developer',
                'ogImage' => 'https://example.com/og-home.png',
                'ogUrl' => 'https://example.com/',
                'twitterCard' => 'summary_large_image',
            ],
        ]));

        try {
            $this->get('/about/me')
                ->assertOk()
                ->assertSee('<title>Frontend Engineer &amp; Web Developer</title>', false)
                ->assertSee('<meta name="description" content="Portofolio Davin GM.">', false)
                ->assertSee('<meta property="og:title" content="Davin GM — Web Developer">', false)
                ->assertSee('<meta property="og:image" content="https://example.com/og-home.png">', false)
                ->assertSee('<meta property="og:url" content="https://example.com/">', false)
                ->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
        } finally {
            if ($hadManifest) {
                File::put($path, $originalManifest);
            } else {
                File::delete($path);
            }
        }
    }

    public function test_production_html_is_minified_to_one_line(): void
    {
        config(['app.env' => 'production']);
        $middleware = new MinifyHtmlMiddleware;
        $response = $middleware->handle(
            Request::create('/'),
            fn () => new Response("<!doctype html>\n<html>\n<body>\n<p>Hello</p>\n</body>\n</html>\n", 200, ['Content-Type' => 'text/html; charset=UTF-8']),
        );

        $this->assertSame('<!doctype html><html><body><p>Hello</p></body></html>', $response->getContent());
    }

    public function test_single_file_page_can_select_another_registered_layout(): void
    {
        $layoutPath = base_path('src/layouts/test-layout.blade.php');
        File::put($layoutPath, '<div data-selected-layout>@yield(\'content\')</div>');
        Route::get('/home-with-test-layout', fn () => page('home', [], 'test-layout'));

        try {
            $this->get('/home-with-test-layout')
                ->assertOk()
                ->assertSee('<div data-selected-layout>', false)
                ->assertSee('Klik saya');
        } finally {
            File::delete($layoutPath);
        }
    }
}

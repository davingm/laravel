<?php

namespace Tests\Feature;

use Davingm\LaravelRuntime\Compiler\Runtime\SingleFilePageCompiler;
use InvalidArgumentException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
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

        $this->assertStringContainsString('<pages data-page-scope="'.substr(hash('sha256', $source), 0, 12).'">', $compiled);
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

    public function test_single_file_page_requires_typescript_setup(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SingleFilePageCompiler::prepareBlade('<pages><p>Plain page</p></pages>');
    }

    public function test_home_single_file_page_renders_inside_the_framework_layout_with_default_seo(): void
    {
        $this->get('/home')
            ->assertOk()
            ->assertSee('<title>Home | Laravel</title>', false)
            ->assertSee('<meta name="description" content="Halaman Home.">', false)
            ->assertSee('<meta property="og:title" content="Home">', false)
            ->assertSee('<meta property="og:image" content="'.asset('Laravel.svg').'">', false)
            ->assertSee('<html lang="en">', false)
            ->assertSee('<pages data-page-scope="', false)
            ->assertSee('Klik saya')
            ->assertDontSee('<script setup lang="ts">', false)
            ->assertDontSee('<style scoped>', false)
            ->assertDontSee('@extends', false);
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

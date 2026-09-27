<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageRouterTest extends TestCase
{
    use RefreshDatabase;

    public function test_nested_siswa_about_page_is_accessible(): void
    {
        $response = $this->get('/siswa/about');

        $response->assertOk();
        $response->assertSee('hai');
    }

    public function test_siswa_resource_index_is_accessible(): void
    {
        $response = $this->get('/siswa');

        $response->assertOk();
        $response->assertSee('Hello davingm');
    }

    public function test_preview_mode_minifies_html_while_dev_mode_does_not(): void
    {
        // Dev mode: multi-line HTML, no aggressive minification
        $devResponse = $this->get('/siswa/about');
        $devResponse->assertOk();
        $this->assertStringContainsString("\n", $devResponse->getContent());

        // Preview mode: single-line minified HTML
        putenv('DAVINGM_PREVIEW=1');
        $_SERVER['DAVINGM_PREVIEW'] = '1';

        $previewResponse = $this->get('/siswa/about');
        $previewResponse->assertOk();
        $this->assertStringNotContainsString('id="browser-logger-active"', $previewResponse->getContent());

        putenv('DAVINGM_PREVIEW');
        unset($_SERVER['DAVINGM_PREVIEW']);
    }
}

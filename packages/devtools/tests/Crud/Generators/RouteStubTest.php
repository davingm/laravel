<?php

declare(strict_types=1);

namespace Tests\Crud\Generators;

use Davingm\LaravelDevtools\Crud\Generators\RouteStub;
use PHPUnit\Framework\TestCase;

final class RouteStubTest extends TestCase
{
    public function test_renders_block_with_markers(): void
    {
        $block = (new RouteStub())->render('products', '\\App\\Http\\Controllers\\ProductController');

        self::assertStringContainsString('// BEGIN auto-crud:products', $block);
        self::assertStringContainsString('// END auto-crud:products', $block);
        self::assertStringEndsWith(PHP_EOL, $block);
    }

    public function test_renders_resource_route_with_except_show(): void
    {
        $block = (new RouteStub())->render('products', '\\App\\Http\\Controllers\\ProductController');

        self::assertStringContainsString("Route::resource('products', \\App\\Http\\Controllers\\ProductController::class)->except(['show']);", $block);
    }

    public function test_uses_fqcn_for_controller(): void
    {
        $block = (new RouteStub())->render('products', '\\App\\Http\\Controllers\\ProductController');

        self::assertStringContainsString('\\App\\Http\\Controllers\\ProductController::class', $block);
    }
}

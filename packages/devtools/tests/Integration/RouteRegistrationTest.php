<?php

declare(strict_types=1);

namespace Tests\Integration;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Artisan;

final class RouteRegistrationTest extends IntegrationTestCase
{
    public function test_generated_route_appears_in_route_collection(): void
    {
        $this->generateAndLoadRoutes();

        foreach ([
            'products.index',
            'products.create',
            'products.store',
            'products.edit',
            'products.update',
            'products.destroy',
        ] as $name) {
            self::assertNotNull($this->app['router']->getRoutes()->getByName($name), "Route [{$name}] was not registered.");
        }
    }

    public function test_show_route_is_not_registered(): void
    {
        $this->generateAndLoadRoutes();

        self::assertNull($this->app['router']->getRoutes()->getByName('products.show'));
    }

    public function test_generated_route_uris_are_correct(): void
    {
        $this->generateAndLoadRoutes();

        $expectedUris = [
            'products.index' => '/products',
            'products.create' => '/products/create',
            'products.store' => '/products',
            'products.edit' => '/products/{product}/edit',
            'products.update' => '/products/{product}',
            'products.destroy' => '/products/{product}',
        ];

        foreach ($expectedUris as $name => $uri) {
            $route = $this->app['router']->getRoutes()->getByName($name);
            self::assertInstanceOf(Route::class, $route);
            self::assertSame($uri, '/'.$route->uri(), "Unexpected URI for route [{$name}].");
        }
    }

    public function test_generated_route_methods_are_correct(): void
    {
        $this->generateAndLoadRoutes();

        $expectedMethods = [
            'products.index' => ['GET', 'HEAD'],
            'products.create' => ['GET', 'HEAD'],
            'products.store' => ['POST'],
            'products.edit' => ['GET', 'HEAD'],
            'products.update' => ['PUT', 'PATCH'],
            'products.destroy' => ['DELETE'],
        ];

        foreach ($expectedMethods as $name => $methods) {
            $route = $this->app['router']->getRoutes()->getByName($name);
            self::assertInstanceOf(Route::class, $route);
            self::assertSame($methods, $route->methods(), "Unexpected HTTP methods for route [{$name}].");
        }
    }

    private function generateAndLoadRoutes(): void
    {
        self::assertSame(0, Artisan::call('crud:generate', ['model' => 'Product']));

        $routesFile = $this->appBasePath.DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR.'web.php';
        $this->app['router']->middleware('web')->group(function () use ($routesFile): void {
            require $routesFile;
        });
    }
}

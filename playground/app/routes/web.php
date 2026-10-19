<?php

use App\Support\PageRouter;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| File-based Auto Routes (pages/**)
|--------------------------------------------------------------------------
|
| PageRouter scans src/pages/** and registers a GET route for every Blade
| file automatically. Conventions:
|
|   pages/home.blade.php           →  GET /home        (name: pages.home)
|   pages/index.blade.php          →  GET /            (name: pages)
|   pages/about/index.blade.php    →  GET /about       (name: pages.about)
|   pages/about/us-me.blade.php    →  GET /about/us-me (name: pages.about.us-me)
|   pages/blog/[slug].blade.php    →  GET /blog/{slug} (name: pages.blog.slug)
|
| You can still define manual routes BELOW to override any auto-generated
| route — Laravel processes routes in registration order, and named manual
| routes will win because they're explicit.
|
*/

PageRouter::register([
    'exclude' => ['mahasiswas', 'mahasiswas/*'],
    'middleware' => ['web'],
]);

Route::post('/__laravel/cli/telemetry', function (Request $request) {
    $telemetryFile = base_path('.laravel/cache/cli-telemetry-url');
    $telemetryUrl = is_file($telemetryFile) ? trim((string) file_get_contents($telemetryFile)) : '';
    $event = json_decode($request->getContent(), true);

    if ($telemetryUrl && is_array($event)) {
        try {
            Http::timeout(1)->post($telemetryUrl, $event);
        } catch (\Throwable) {
            // CLI telemetry must never interrupt an application response.
        }
    }

    return response()->noContent();
})->withoutMiddleware(PreventRequestForgery::class)->name('laravel.cli.telemetry');

/*
|--------------------------------------------------------------------------
| Manual Route Overrides
|--------------------------------------------------------------------------
|
| Place any route that needs custom data, middleware, or logic here.
| Manual routes registered after PageRouter::register() will override
| the auto-generated equivalent.
|
| Example:
|
|   Route::get('/home', fn () => page('home', [
|       'title' => config('app.name').' | Welcome',
|       'description' => 'The best app ever.',
|   ]))->name('pages.home');
|
*/

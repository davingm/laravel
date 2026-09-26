<?php

use App\Http\Controllers\BarangController;
use App\Support\PageRouter;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| File-based Auto Routes (pages/**)
|--------------------------------------------------------------------------
|
| PageRouter scans resources/views/pages/** and registers a GET route for
| every Blade file automatically. Conventions:
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

Route::resource('barang', BarangController::class);

PageRouter::register([
    'middleware' => ['web'],
    'exclude' => ['barang'],
]);

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

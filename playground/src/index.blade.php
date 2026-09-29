{{--
|--------------------------------------------------------------------------
| src/index.blade.php — Application Entry View
|--------------------------------------------------------------------------
|
| This is the root view entry point for davingm/laravel, inspired by
| Nuxt's app.vue convention. By default it delegates rendering to the
| page files inside src/pages/.
|
| When published via Composer, only this file ships — src/pages/,
| src/layouts/, and src/components/ are intentionally absent and created
| by the user (or auto-generated via `php artisan make:page`).
|
| You can extend this file to wrap a global shell, or leave it as-is
| and let PageRouter handle the routing automatically.
|
--}}
@extends('layouts.app')

@section('content')
    @yield('page')
@endsection

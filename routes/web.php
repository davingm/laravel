<?php

use App\Support\Frontend;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Frontend::render('home', [
        'title' => config('app.name', 'Davingm').' | Blade frontend mode',
        'description' => 'Nuxt-like frontend conventions for Laravel Blade.',
    ]);
});

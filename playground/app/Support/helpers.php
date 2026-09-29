<?php

use App\Support\Frontend;
use Illuminate\Contracts\View\View;

if (! function_exists('page')) {
    /**
     * Render a page from resources/views/pages using the Frontend engine.
     *
     * Shorthand for Frontend::render() — for use in controllers and closures.
     *
     * Examples:
     *   return page('home');              // pages/home.blade.php
     *   return page('about.index');       // pages/about/index.blade.php
     *   return page('about.us-me', [...]) // pages/about/us-me.blade.php
     *
     * @param  array<string, mixed>  $data
     */
    function page(string $viewKey, array $data = [], ?string $layout = 'layouts.app'): View
    {
        return Frontend::render($viewKey, null, $data, $layout);
    }
}

<?php

use Davingm\LaravelRuntime\App\Support\Frontend;
use Illuminate\Contracts\View\View;

if (! function_exists('page')) {
    /**
     * Render a page from src/pages using the framework frontend engine.
     *
     * @param  array<string, mixed>  $data
     */
    function page(string $viewKey, array $data = [], ?string $layout = 'layouts.default'): View
    {
        return Frontend::render($viewKey, null, $data, $layout);
    }
}

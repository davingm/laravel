<?php

use Davingm\LaravelRuntime\App\Support\PageRouter;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

PageRouter::register();

Route::middleware('web')->post('/__laravel/cli/telemetry', function (Request $request) {
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

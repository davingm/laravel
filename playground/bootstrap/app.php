<?php

use App\Http\Middleware\MinifyHtmlMiddleware;
use App\Console\Commands\MakeMigrationCommand;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../app/routes/web.php',
        commands: __DIR__.'/../app/routes/console.php',
        health: '/up',
    )
    ->withCommands([MakeMigrationCommand::class])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            MinifyHtmlMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

$app->useDatabasePath($app->basePath('app/database'));

return $app;

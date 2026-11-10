<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Crud\Generators;

use InvalidArgumentException;

final class RouteStub
{
    public function render(string $table, string $controllerFqcn): string
    {
        if (str_contains($table, "\n") || str_contains($table, "\r")) {
            throw new InvalidArgumentException('Route table names cannot contain line breaks.');
        }
        if (preg_match('/^\\\\[A-Za-z_][A-Za-z0-9_\\\\]*$/D', $controllerFqcn) !== 1) {
            throw new InvalidArgumentException("Controller class [{$controllerFqcn}] must be a fully-qualified class name.");
        }

        $routeName = var_export($table, true);
        $markerName = $table;

        return "// BEGIN auto-crud:{$markerName}".PHP_EOL
            ."Route::resource({$routeName}, {$controllerFqcn}::class)->except(['show']);".PHP_EOL
            ."// END auto-crud:{$markerName}".PHP_EOL;
    }
}

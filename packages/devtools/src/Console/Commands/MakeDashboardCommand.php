<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class MakeDashboardCommand extends Command
{
    protected $signature = 'make:dashboard {--layout=sidebar : Layout type: sidebar or navbar} {--force : Replace an existing layout}';
    protected $description = 'Generate a standard Blade dashboard layout';

    public function handle(): int
    {
        $layout = (string) $this->option('layout');
        if (!in_array($layout, ['sidebar', 'navbar'], true)) {
            $this->error('Layout must be sidebar or navbar.');
            return self::FAILURE;
        }
        $path = resource_path('views/layouts/app.blade.php');
        if (File::exists($path) && !$this->option('force')) {
            $this->error('resources/views/layouts/app.blade.php already exists. Use --force to replace it.');
            return self::FAILURE;
        }
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $this->layoutSource($layout));
        $this->info('Dashboard layout generated: resources/views/layouts/app.blade.php');
        return self::SUCCESS;
    }

    private function layoutSource(string $layout): string
    {
        $navigation = $layout === 'sidebar'
            ? '<aside class="w-64 bg-slate-900 p-6 text-white"><a href="{{ url(\'/\') }}">{{ config(\'app.name\') }}</a><nav class="mt-8"><a href="{{ url(\'/\') }}">Dashboard</a></nav></aside>'
            : '<header class="bg-slate-900 p-4 text-white"><a href="{{ url(\'/\') }}">{{ config(\'app.name\') }}</a><nav class="mt-3"><a href="{{ url(\'/\') }}">Dashboard</a></nav></header>';
        $wrapper = $layout === 'sidebar' ? 'flex min-h-screen' : 'min-h-screen';
        return strtr(<<<'BLADE'
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-900">
<div class="__WRAPPER__">
    __NAVIGATION__
    <main class="min-w-0 flex-1 p-6">@yield('content')</main>
</div>
</body>
</html>
BLADE, ['__WRAPPER__' => $wrapper, '__NAVIGATION__' => $navigation]);
    }
}

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @hasSection('seo')
        @yield('seo')
    @else
        @hasSection('title')
            <title>@yield('title') | {{ config('app.name', 'Laravel') }}</title>
        @endif
        @pageMeta
    @endif
    @stack('head')

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite([
            'src/assets/css/app.css',
            'node_modules/@davingm/welcome/dist/welcomepage.css',
            '.laravel/generated/pages.css',
            '.laravel/frontend/framework.js',
            'src/assets/ts/app.ts',
            '.laravel/generated/pages.ts',
        ])
    @endif
</head>
<body class="min-h-screen antialiased">
    <main id="page-view" data-page="{{ $frontendPage ?? '' }}">
        @yield('content')
    </main>
    @payload
</body>
</html>

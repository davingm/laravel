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
    @endif
    <link rel="stylesheet" href="{{ asset('css/devtools-auth.css') }}">
    @stack('head')
    <script src="{{ asset('js/devtools-auth.js') }}" defer></script>
</head>
<body class="@yield('body-class')">
    <main id="page-view">
        @yield('content')
    </main>
</body>
</html>

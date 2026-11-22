<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>@yield('title', 'Argon') · {{ config('app.name', 'Argon') }}</title>
    <link rel="icon" href="{{ asset('assets/img/brand/favicon.png') }}" type="image/png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700">
    <link rel="stylesheet" href="{{ asset('assets/js/plugins/nucleo/css/nucleo.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/js/plugins/@fortawesome/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/argon-dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/argon-peminjaman.css') }}">
    @stack('head')
</head>
<body class="{{ trim($__env->yieldContent('body-class')) }}">
@yield('content')
<script src="{{ asset('assets/js/plugins/jquery/dist/jquery.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/chart.js/dist/Chart.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/chart.js/dist/Chart.extension.js') }}"></script>
<script src="{{ asset('assets/js/argon-dashboard.min.js') }}"></script>
@stack('scripts')
<script src="{{ asset('js/peminjaman.js') }}"></script>
</body>
</html>

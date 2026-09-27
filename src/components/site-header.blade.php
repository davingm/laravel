<header class="site-header">
    <a class="brand" href="{{ url('/') }}" data-navigate="{{ url('/') }}" aria-label="{{ config('app.name', 'Davingm') }} home">
        <span class="brand-mark">D</span>
        <span>{{ config('app.name', 'Davingm') }}</span>
    </a>
    <nav class="site-nav" aria-label="Primary navigation">
        <a href="{{ url('/') }}" data-navigate="{{ url('/') }}">Home</a>
        <a href="https://laravel.com/docs" target="_blank" rel="noreferrer">Docs</a>
    </nav>
</header>

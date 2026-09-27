@extends('layouts.app')

@section('content')
<section class="hero-shell">
    <div class="hero-copy" data-reveal>
        <p class="eyebrow">Blade frontend</p>
        <h1>Nuxt-like conventions.<br><em>Still Blade.</em></h1>
        <p class="lede">File-based pages, auto routing, payload hydration, and client navigation — without Vue.</p>
        <div class="hero-actions">
            <a class="button button-primary" href="{{ route('pages.about') }}" @navigate(route('pages.about'))>About <span>→</span></a>
            <a class="button button-quiet" href="https://laravel.com/docs" target="_blank" rel="noreferrer">Laravel docs</a>
        </div>
    </div>
    <div class="hero-panel" data-reveal data-delay="120">
        <div class="panel-topline"><span class="status-dot"></span> auto routing</div>
        <div class="terminal-line"><span>$</span> pages/index.blade.php → /</div>
        <div class="terminal-line"><span>$</span> pages/about/index.blade.php → /about</div>
        <div class="terminal-line"><span>$</span> pages/blog/[slug].blade.php → /blog/{slug}</div>
        <div class="terminal-result">routes registered automatically</div>
    </div>
</section>
@endsection

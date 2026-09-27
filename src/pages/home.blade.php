@extends('layouts.app')

@section('content')
<section class="hero-shell">
    <div class="hero-copy" data-reveal>
        <p class="eyebrow">Blade frontend mode</p>
        <h1>Nuxt-like conventions.<br><em>Still Blade.</em></h1>
        <p class="lede">File-based pages, layouts, payload hydration, client navigation, and generated frontend cache without introducing Vue.</p>
        <div class="hero-actions">
            <a class="button button-primary" href="{{ url('/up') }}" data-navigate="{{ url('/up') }}">Check health <span>↗</span></a>
            <a class="button button-quiet" href="https://laravel.com/docs" target="_blank" rel="noreferrer">Read Laravel docs</a>
        </div>
    </div>
    <div class="hero-panel" data-reveal data-delay="120">
        <div class="panel-topline"><span class="status-dot"></span> frontend runtime</div>
        <div class="terminal-line"><span>$</span> artisan frontend:generate</div>
        <div class="terminal-result">manifest ready</div>
        <div class="runtime-grid">
            <div><strong>Blade</strong><small>render mode</small></div>
            <div><strong>SSR</strong><small>first response</small></div>
            <div><strong>JSON</strong><small>page payload</small></div>
            <div><strong>Vite</strong><small>asset pipeline</small></div>
        </div>
    </div>
</section>

<section class="feature-strip" data-reveal data-delay="220">
    <article>
        <span class="feature-number">01</span>
        <h2>Pages directory</h2>
        <p>Keep route views in <code>resources/views/pages</code> and render them with a predictable frontend contract.</p>
    </article>
    <article>
        <span class="feature-number">02</span>
        <h2>Payload cache</h2>
        <p>Every frontend response can expose JSON state and write a local cache under <code>.davingm/cache</code>.</p>
    </article>
    <article>
        <span class="feature-number">03</span>
        <h2>Soft navigation</h2>
        <p>Links marked with <code>data-navigate</code> use fetch and swap the page shell without Vue.</p>
    </article>
</section>
@endsection

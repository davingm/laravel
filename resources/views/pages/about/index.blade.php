@extends('layouts.app')

@section('content')
<section class="hero-shell">
    <div class="hero-copy" data-reveal>
        <p class="eyebrow">About</p>
        <h1>About this project.</h1>
        <p class="lede">This page lives at <code>pages/about/index.blade.php</code> and is served at <code>/about</code> — no route registration needed.</p>
        <div class="hero-actions">
            <a class="button button-quiet" href="{{ route('pages') }}" @navigate(route('pages'))>← Back home</a>
        </div>
    </div>
</section>
@endsection

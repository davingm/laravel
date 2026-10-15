@extends('layouts.app')

@section('title', config('app.name', 'Laravel'))

@section('content')
<main class="starter-page" data-starter-page>
    <canvas class="starter-page__field" data-particle-field aria-hidden="true"></canvas>
    <div class="starter-page__shade" aria-hidden="true"></div>

    <section class="starter-center" aria-labelledby="starter-title">
        <p class="starter-name">{{ config('app.name', 'Laravel') }}</p>
        <h1 id="starter-title">A clean start<br>for your next idea.</h1>
        <p class="starter-description">Your application is ready. Make it yours.</p>
        <a class="starter-link" href="https://laravel.com/docs" target="_blank" rel="noreferrer">
            Read the documentation <span aria-hidden="true">↗</span>
        </a>
    </section>
</main>
@endsection

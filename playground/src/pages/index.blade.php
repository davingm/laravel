@extends('layouts.app')

@section('title', config('app.name', 'Laravel'))

@section('content')
<section class="mx-auto flex min-h-screen max-w-5xl flex-col justify-center px-6 py-20">
    <p class="text-sm font-semibold uppercase tracking-widest text-gray-500">{{ config('app.name', 'Laravel') }}</p>
    <h1 class="mt-5 max-w-3xl text-5xl font-semibold tracking-tight text-gray-950 sm:text-7xl">
        A clean start for your next idea.
    </h1>
    <p class="mt-6 max-w-2xl text-lg leading-8 text-gray-600">
        Your Laravel application is ready. Start building pages, models, and features for your project.
    </p>
    <div class="mt-10 flex flex-wrap gap-4">
        <a class="rounded-lg bg-gray-950 px-5 py-3 text-sm font-semibold text-white hover:bg-gray-700" href="https://laravel.com/docs" target="_blank" rel="noreferrer">
            Read the Laravel docs
        </a>
        <a class="rounded-lg border border-gray-300 px-5 py-3 text-sm font-semibold text-gray-800 hover:bg-gray-50" href="https://github.com/davingm/laravel" target="_blank" rel="noreferrer">
            Explore the framework
        </a>
    </div>
</section>
@endsection

@extends('layouts.app')

@php
$seo = [
    'title' => 'Home',
    'description' => 'Halaman Home.',
    'ogTitle' => 'Home',
    'ogDescription' => 'Halaman Home.',
    'ogImage' => asset('images/og-image.jpg'),
];
@endphp

@section('seo')
    <x-seo-meta :seo="$seo" />
@endsection

@section('content')
<section class="crud-shell">
    <h1 class="crud-title">Home</h1>
    <p class="crud-description">Contoh interaksi TypeScript di halaman Blade.</p>
    <div class="mt-6 flex items-center gap-4">
        <button class="button button-primary" type="button" data-home-counter>
            Klik saya
        </button>
        <p class="m-0 text-sm text-neutral-600" aria-live="polite">
            Jumlah klik: <output data-home-counter-value>0</output>
        </p>
    </div>
</section>
@endsection

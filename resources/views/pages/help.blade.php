@extends('layouts.app')

@php
    $seo = [
        'title' => 'Help',
        'description' => 'Halaman bantuan dan informasi.',
        'ogTitle' => 'Help',
        'ogDescription' => 'Halaman bantuan dan informasi.',
        'ogImage' => asset('images/og-image.jpg'),
    ];
@endphp

@section('seo')
    <x-seo-meta :seo="$seo" />
@endsection

@section('content')
<section class="crud-shell">
    <nav aria-label="Breadcrumb" class="page-breadcrumb">
        <a href="{{ url('/') }}" data-navigate="{{ url('/') }}">Home</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Help</span>
    </nav>

    <header class="page-header">
        <div>
            <p class="eyebrow">Page</p>
            <h1 class="crud-title">Help</h1>
            <p class="lede">Halaman bantuan dan informasi.</p>
        </div>
        <div class="hero-actions">
            <a href="{{ url('/') }}" data-navigate="{{ url('/') }}" class="button button-quiet">Kembali</a>
        </div>
    </header>

    <div class="hero-panel">
        <div class="panel-topline">
            <span class="status-dot"></span>Help
        </div>
        <p>Temukan informasi dan bantuan yang kamu perlukan.</p>
    </div>
</section>
@endsection
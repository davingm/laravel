@extends('layouts.app')

{{--
|--------------------------------------------------------------------------
| @section('seo') — Nuxt-like Head Management
|--------------------------------------------------------------------------
|
| Blade pages menggunakan @section('seo') untuk override blok <head>
| secara penuh, mirip konsep useHead() / useSeoMeta() di Nuxt 3.
|
| Yang tersedia di sini:
|   - Primary Meta Tags  (title, description, keywords, robots, canonical)
|   - Open Graph         (og:type, og:url, og:title, og:description, og:image)
|   - Twitter Cards      (twitter:card, summary_large_image)
|   - Schema.org JSON-LD (WebPage + BreadcrumbList)
|
| @stack('head') tersedia di layout untuk inject elemen <head> tambahan
| dari halaman ini tanpa override seluruh @section('seo').
|
--}}
@section('seo')
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- Primary Meta Tags                                                   --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <title>Siswa Detail | {{ config('app.name') }}</title>
    <meta name="title"       content="Siswa Detail | {{ config('app.name') }}">
    <meta name="description" content="Halaman Siswa Detail — akses informasi dan detail lengkap di {{ config('app.name') }}.">
    <meta name="keywords"    content="siswa, detail, davingm, laravel">
    <meta name="author"      content="{{ config('app.name') }}">
    <meta name="robots"      content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <link rel="canonical"    href="{{ url()->current() }}">

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- Open Graph / Facebook / WhatsApp                                    --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <meta property="og:type"        content="website">
    <meta property="og:url"         content="{{ url()->current() }}">
    <meta property="og:title"       content="Siswa Detail | {{ config('app.name') }}">
    <meta property="og:description" content="Halaman Siswa Detail — akses informasi dan detail lengkap di {{ config('app.name') }}.">
    <meta property="og:image"       content="{{ asset('images/og-image.jpg') }}">
    <meta property="og:image:alt"   content="Siswa Detail">
    <meta property="og:site_name"   content="{{ config('app.name') }}">
    <meta property="og:locale"      content="{{ str_replace('_', '-', app()->getLocale()) }}">

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- Twitter / X Cards                                                   --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:url"         content="{{ url()->current() }}">
    <meta name="twitter:title"       content="Siswa Detail | {{ config('app.name') }}">
    <meta name="twitter:description" content="Halaman Siswa Detail — akses informasi dan detail lengkap di {{ config('app.name') }}.">
    <meta name="twitter:image"       content="{{ asset('images/og-image.jpg') }}">

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- Structured Data — Schema.org JSON-LD                                --}}
    {{-- Digunakan Google untuk rich results dan Knowledge Graph             --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@graph": [
            {
                "@type": "WebPage",
                "@id": "{{ url()->current() }}#webpage",
                "url": "{{ url()->current() }}",
                "name": "Siswa Detail",
                "description": "Halaman Siswa Detail — akses informasi dan detail lengkap di {{ config('app.name') }}.",
                "isPartOf": {
                    "@type": "WebSite",
                    "@id": "{{ url('/') }}#website",
                    "url": "{{ url('/') }}",
                    "name": "{{ config('app.name') }}"
                },
                "inLanguage": "{{ str_replace('_', '-', app()->getLocale()) }}"
            },
            {
                "@type": "BreadcrumbList",
                "@id": "{{ url()->current() }}#breadcrumb",
                "itemListElement": [
                    {
                        "@type": "ListItem",
                        "position": 1,
                        "name": "Home",
                        "item": "{{ url('/') }}"
                    },
                    {
                        "@type": "ListItem",
                        "position": 2,
                        "name": "Siswa",
                        "item": "{{ url('/siswa') }}"
                    },
                    {
                        "@type": "ListItem",
                        "position": 3,
                        "name": "Detail",
                        "item": "{{ url('/siswa/detail') }}"
                    }
                ]
            }
        ]
    }
    </script>

    {{-- Slot untuk inject elemen <head> tambahan dari halaman ini --}}
    @stack('head')
@endsection

@section('content')
<section class="crud-shell">

    {{-- ─── Breadcrumb Navigation ─────────────────────────────────────── --}}
    <nav aria-label="Breadcrumb" class="page-breadcrumb">
        <a href="{{ url('/') }}" data-navigate="{{ url('/') }}">Home</a>
            <span aria-hidden="true">/</span>
            <a href="{{ url('/siswa') }}" data-navigate="{{ url('/siswa') }}">Siswa</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">Detail</span>
    </nav>

    {{-- ─── Page Header ────────────────────────────────────────────────── --}}
    <header class="page-header">
        <div>
            <p class="eyebrow">Siswa</p>
            <h1 class="crud-title">Siswa Detail</h1>
            <p class="lede">Halaman Siswa Detail — akses informasi dan detail lengkap.</p>
        </div>
        <div class="hero-actions">
            <a href="{{ url('/siswa') }}" data-navigate="{{ url('/siswa') }}" class="button button-quiet">
                ← Kembali
            </a>
        </div>
    </header>

    {{-- ─── Main Content ────────────────────────────────────────────────── --}}
    {{-- TODO: Ganti bagian ini dengan konten halaman yang sebenarnya --}}
    <div class="hero-panel">
        <div class="panel-topline">
            <span class="status-dot"></span>Siswa Detail
        </div>
        <div class="runtime-grid" style="margin-top: 20px;">
            <div>
                <strong>SEO Ready</strong>
                <small>Meta, OG, Twitter, JSON-LD</small>
            </div>
            <div>
                <strong>Soft Nav</strong>
                <small>data-navigate support</small>
            </div>
        </div>
    </div>

</section>
@endsection
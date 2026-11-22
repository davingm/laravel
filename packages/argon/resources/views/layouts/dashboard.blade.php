@extends('layouts.app')

@push('head')
    @if (file_exists(public_path('css/peminjaman.css')))
        <link rel="stylesheet" href="{{ asset('css/peminjaman.css') }}">
    @endif
    @if (file_exists(public_path('js/peminjaman.js')))
        <script src="{{ asset('js/peminjaman.js') }}" defer></script>
    @endif
@endpush

@section('body-class', 'dashboard-page')

@section('content')
<div class="dashboard-shell">
    @include('components.app.dashboard-header')

    <div class="dashboard-workspace">
        @include('components.app.dashboard-sidebar')

        <main class="dashboard-main" aria-label="Konten dashboard">
            @yield('dashboard-content')
        </main>
    </div>
</div>
@endsection

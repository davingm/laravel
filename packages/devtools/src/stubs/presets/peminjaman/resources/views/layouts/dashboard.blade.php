@extends('layouts.app')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/devtools-preset-peminjaman.css') }}">
@endpush

@section('body-class', 'dashboard-page')

@section('content')
<div class="dashboard-shell">
    @include('devtools-peminjaman.components.dashboard-header')

    <div class="dashboard-workspace">
        @include('devtools-peminjaman.components.dashboard-sidebar')

        <main class="dashboard-main" aria-label="Konten dashboard">
            @yield('dashboard-content')
        </main>
    </div>
</div>
@endsection

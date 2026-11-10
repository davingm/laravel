@extends('layouts.app')

@section('body-class', 'dashboard-page')

@section('content')
<div class="dashboard-shell">
    @include('components.dashboard-header')

    <div class="dashboard-workspace">
        @include('components.dashboard-sidebar')

        <main class="dashboard-main" aria-label="Konten dashboard">
            @yield('dashboard-content')
        </main>
    </div>
</div>
@endsection

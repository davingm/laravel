@extends('devtools-peminjaman.layouts.dashboard')

@section('title', 'Dashboard')
@section('body-class', 'dashboard-page')
@section('dashboard-content')
<section class="crud-shell">
    <header class="crud-heading">
        <div>
            <h1 class="crud-title">Dashboard</h1>
            <p class="crud-description">Ringkasan peminjaman alat. Selamat datang, {{ auth()->user()->name }}.</p>
        </div>
    </header>

    <div class="overview-grid">
        <article class="overview-card"><span>Stok alat tersedia</span><strong>{{ number_format($totalAlat) }}</strong></article>
        @if ($totalUser !== null)<article class="overview-card"><span>Pengguna</span><strong>{{ number_format($totalUser) }}</strong></article>@endif
        <article class="overview-card"><span>Menunggu persetujuan</span><strong>{{ number_format($menunggu) }}</strong></article>
        <article class="overview-card"><span>Sedang dipinjam</span><strong>{{ number_format($dipinjam) }}</strong></article>
        <article class="overview-card"><span>Menunggu konfirmasi kembali</span><strong>{{ number_format($menungguKembali) }}</strong></article>
    </div>

    <div class="overview-actions">
        @if (auth()->user()->role === 'user')
            <a href="{{ route('peminjaman.alats.index') }}" class="button button-primary">Lihat dan pinjam alat</a>
        @endif
        <a href="{{ route('peminjaman.peminjamans.index') }}" class="button button-secondary">Buka peminjaman</a>
    </div>
</section>
@endsection

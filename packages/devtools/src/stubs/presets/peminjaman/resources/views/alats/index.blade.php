@extends('devtools-peminjaman.layouts.dashboard')

@section('title', 'Daftar Alat')
@section('body-class', 'dashboard-page')
@section('dashboard-content')
<section class="crud-shell">
    <header class="crud-heading">
        <div class="crud-heading-copy">
            <p class="eyebrow">Peminjaman alat</p>
            <h1 class="crud-title">Daftar Alat</h1>
            <p class="crud-description">Lihat ketersediaan alat yang bisa dipinjam.</p>
        </div>
        @if (auth()->user()->role === 'administrator')
            <a href="{{ route('peminjaman.alats.create') }}" class="button button-primary">Tambah Alat</a>
        @endif
    </header>

    @if (session('success'))
        <p class="flash-success" role="status">{{ session('success') }}</p>
    @endif
    @if (session('error'))
        <p class="flash-error" role="alert">{{ session('error') }}</p>
    @endif
    @if ($errors->any())
        <div class="form-error-summary" role="alert">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <div class="crud-summary">
        <span><strong class="crud-summary-count">{{ $alats->total() }}</strong> data</span>
        <label class="crud-search-wrap">
            <span class="sr-only">Cari data pada halaman ini</span>
            <input type="search" data-table-search placeholder="Cari…" class="crud-search">
        </label>
    </div>

    <div class="table-wrap">
        <table class="barang-table">
            <caption class="sr-only">Daftar alats</caption>
            <thead>
                <tr>
                    <th>Nama Alat</th>
                    <th>Kategori</th>
                    <th>Stok tersedia</th>
                    @if (auth()->user()->role !== 'user')<th>Deskripsi</th>@endif
                    <th class="actions-heading">Aksi</th>
                </tr>
            </thead>
            <tbody data-table-body>
                @forelse ($alats as $alat)
                    <tr data-table-row>
                    <td>{{ $alat->nama_alat }}</td>
                    <td>{{ $alat->kategori->nama_kategori }}</td>
                    <td>{{ $alat->stok }}</td>
                    @if (auth()->user()->role !== 'user')<td>{{ $alat->deskripsi ?: '—' }}</td>@endif
                        <td>
                            <div class="table-actions">
                                @if (auth()->user()->role === 'administrator')
                                    <a href="{{ route('peminjaman.alats.edit', $alat) }}" class="button button-secondary button-small">Edit</a>
                                    <form method="POST" action="{{ route('peminjaman.alats.destroy', $alat) }}" data-confirm="Yakin ingin menghapus alat ini?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="button button-secondary button-small">Hapus</button>
                                    </form>
                                @elseif (auth()->user()->role === 'user')
                                    <form method="POST" action="{{ route('peminjaman.peminjamans.request') }}" class="table-actions">
                                        @csrf
                                        <input type="hidden" name="alat_id" value="{{ $alat->id }}">
                                        <input type="hidden" name="jumlah" value="1">
                                        <button type="submit" class="button button-primary button-small" @disabled($alat->stok < 1)>Ajukan pinjam</button>
                                    </form>
                                @else
                                    <span class="table-muted">Lihat saja</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty-state" colspan="{{ auth()->user()->role === 'user' ? 4 : 5 }}">
                            <div class="crud-empty-state">
                                <h2>Belum ada data</h2>
                                <p>Belum ada alat yang terdaftar.</p>
                                @if (auth()->user()->role === 'administrator')
                                    <a href="{{ route('peminjaman.alats.create') }}" class="button button-primary">Tambah Alat</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
                <tr data-table-no-results hidden>
                    <td class="empty-state" colspan="{{ auth()->user()->role === 'user' ? 4 : 5 }}">Tidak ada data yang cocok.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="crud-pagination">{{ $alats->links() }}</div>
</section>
@endsection
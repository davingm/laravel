@extends('layouts.dashboard')

@section('title', 'Kategori Alat')
@section('body-class', 'dashboard-page')
@section('dashboard-content')
<section class="crud-shell">
    <header class="crud-heading">
        <div class="crud-heading-copy">
            <p class="eyebrow">Pengaturan alat</p>
            <h1 class="crud-title">Kategori Alat</h1>
            <p class="crud-description">Kelola kategori untuk daftar alat.</p>
        </div>
        <a href="{{ route('peminjaman.kategoris.create') }}" class="button button-primary">Tambah Kategori</a>
    </header>

    @if (session('success'))
        <p class="flash-success" role="status">{{ session('success') }}</p>
    @endif
    @if (session('error'))
        <p class="flash-error" role="alert">{{ session('error') }}</p>
    @endif

    <div class="crud-summary">
        <span><strong class="crud-summary-count">{{ $kategoris->total() }}</strong> data</span>
        <label class="crud-search-wrap">
            <span class="sr-only">Cari data pada halaman ini</span>
            <input type="search" data-table-search placeholder="Cari…" class="crud-search">
        </label>
    </div>

    <div class="table-wrap">
        <table class="barang-table">
            <caption class="sr-only">Daftar kategoris</caption>
            <thead>
                <tr>
                    <th>Nama Kategori</th>
                    <th>Deskripsi</th>
                    <th class="actions-heading">Aksi</th>
                </tr>
            </thead>
            <tbody data-table-body>
                @forelse ($kategoris as $kategori)
                    <tr data-table-row>
                    <td>{{ $kategori->nama_kategori }}</td>
                    <td>{{ $kategori->deskripsi ?: '—' }}</td>
                        <td>
                            <div class="table-actions">
                                <a href="{{ route('peminjaman.kategoris.edit', $kategori) }}" class="button button-secondary button-small">Edit</a>
                                <form method="POST" action="{{ route('peminjaman.kategoris.destroy', $kategori) }}" data-confirm="Yakin ingin menghapus data ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="button button-secondary button-small">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty-state" colspan="3">
                            <div class="crud-empty-state">
                                <h2>Belum ada data</h2>
                                <p>Tambahkan Kategori pertama.</p>
                                <a href="{{ route('peminjaman.kategoris.create') }}" class="button button-primary">Tambah Kategori</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
                <tr data-table-no-results hidden>
                    <td class="empty-state" colspan="3">Tidak ada data yang cocok.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="crud-pagination">{{ $kategoris->links() }}</div>
</section>
@endsection
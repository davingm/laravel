@extends('layouts.dashboard')

@section('title', 'Kategori Alat')
@section('body-class', 'dashboard-page developer-dashboard')
@section('dashboard-content')
<section class="crud-shell">
    <header class="crud-heading"><div><p class="eyebrow">Inventaris</p><h1 class="crud-title">Kategori alat</h1><p class="crud-description">Pilih baris untuk mengubah kategori.</p></div><a href="{{ route('peminjaman.kategoris.create') }}" class="button button-primary">Tambah kategori</a></header>
    @if (session('success'))<p class="flash-success" role="status">{{ session('success') }}</p>@endif
    @if (session('error'))<p class="flash-error" role="alert">{{ session('error') }}</p>@endif
    <div class="crud-summary"><span><strong class="crud-summary-count">{{ $kategoris->total() }}</strong> kategori</span><label class="crud-search-wrap"><span class="sr-only">Cari kategori</span><input type="search" data-table-search placeholder="Cari kategori…" class="crud-search"></label></div>
    <div class="table-wrap"><table class="barang-table"><thead><tr><th class="number-column">No.</th><th>Nama kategori</th><th>Deskripsi</th><th class="actions-heading">Aksi</th></tr></thead><tbody data-table-body>
        @forelse ($kategoris as $kategori)
            <tr data-table-row data-edit-url="{{ route('peminjaman.kategoris.edit', $kategori) }}" tabindex="0"><td class="number-column">{{ $loop->iteration + (($kategoris->currentPage() - 1) * $kategoris->perPage()) }}</td><td>{{ $kategori->nama_kategori }}</td><td>{{ $kategori->deskripsi ?: '—' }}</td><td class="actions-cell"><form method="POST" action="{{ route('peminjaman.kategoris.destroy', $kategori) }}" data-confirm="Hapus kategori {{ $kategori->nama_kategori }}?">@csrf @method('DELETE')<button class="button button-delete button-small" type="submit">Hapus</button></form></td></tr>
        @empty <tr><td colspan="4" class="empty-state">Belum ada kategori.</td></tr>@endforelse
        <tr data-table-no-results hidden><td colspan="4" class="empty-state">Tidak ada data yang cocok.</td></tr>
    </tbody></table></div>
    <div class="crud-pagination">{{ $kategoris->links() }}</div>
</section>
@endsection

@extends('layouts.dashboard')

@section('title', 'Kategori Alat')
@section('body-class', 'dashboard-page developer-dashboard')
@section('dashboard-content')
<section class="crud-shell">
    <header class="crud-heading"><div><p class="eyebrow">Inventaris</p><h1 class="crud-title">Kategori alat</h1><p class="crud-description">{{ auth()->user()->role === 'administrator' ? 'Pilih baris untuk mengubah kategori.' : 'Kelola daftar kategori alat.' }}</p></div>@if (in_array(auth()->user()->role, ['administrator', 'operator'], true))<a href="{{ route('peminjaman.kategoris.create') }}" class="button button-primary">Tambah kategori</a>@endif</header>
    @if (session('success'))<p class="flash-success" role="status">{{ session('success') }}</p>@endif
    @if (session('error'))<p class="flash-error" role="alert">{{ session('error') }}</p>@endif
    <div class="crud-summary"><span><strong class="crud-summary-count">{{ $kategoris->total() }}</strong> kategori</span><label class="crud-search-wrap"><span class="sr-only">Cari kategori</span><input type="search" data-table-search placeholder="Cari kategori…" class="crud-search"></label></div>
    <div class="table-wrap"><table class="barang-table"><thead><tr><th class="number-column">No.</th><th>Nama kategori</th><th>Deskripsi</th>@if (auth()->user()->role === 'administrator')<th class="actions-heading">Aksi</th>@endif</tr></thead><tbody data-table-body>
        @forelse ($kategoris as $kategori)
            <tr data-table-row @if (auth()->user()->role === 'administrator') data-edit-url="{{ route('peminjaman.kategoris.edit', $kategori) }}" tabindex="0" @endif><td class="number-column">{{ $loop->iteration + (($kategoris->currentPage() - 1) * $kategoris->perPage()) }}</td><td>{{ $kategori->nama_kategori }}</td><td>{{ $kategori->deskripsi ?: '—' }}</td>@if (auth()->user()->role === 'administrator')<td class="actions-cell"><form method="POST" action="{{ route('peminjaman.kategoris.destroy', $kategori) }}" data-confirm="Hapus kategori {{ $kategori->nama_kategori }}?">@csrf @method('DELETE')<button class="button button-delete button-small" type="submit">Hapus</button></form></td>@endif</tr>
        @empty <tr><td colspan="{{ auth()->user()->role === 'administrator' ? 4 : 3 }}" class="empty-state">Belum ada kategori.</td></tr>@endforelse
        <tr data-table-no-results hidden><td colspan="{{ auth()->user()->role === 'administrator' ? 4 : 3 }}" class="empty-state">Tidak ada data yang cocok.</td></tr>
    </tbody></table></div>
    <div class="crud-pagination">{{ $kategoris->links() }}</div>
</section>
@endsection

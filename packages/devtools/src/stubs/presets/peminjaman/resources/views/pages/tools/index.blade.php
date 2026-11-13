@extends('layouts.dashboard')

@section('title', 'Daftar Alat')
@section('body-class', 'dashboard-page developer-dashboard')
@section('dashboard-content')
<section class="crud-shell">
    <header class="crud-heading">
        <div>
            <p class="eyebrow">Inventaris</p>
            <h1 class="crud-title">Daftar Alat</h1>
            <p class="crud-description">Pilih satu atau beberapa alat dan tentukan jumlah yang ingin dipinjam.</p>
        </div>
        @if (in_array(auth()->user()->role, ['administrator', 'operator'], true))
            <a href="{{ route('peminjaman.alats.create') }}" class="button button-primary">Tambah alat</a>
        @endif
    </header>

    @if (session('success'))<p class="flash-success" role="status">{{ session('success') }}</p>@endif
    @if (session('error'))<p class="flash-error" role="alert">{{ session('error') }}</p>@endif
    @if ($errors->any())<div class="form-error-summary" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

    <div class="crud-summary">
        <span><strong class="crud-summary-count">{{ $alats->total() }}</strong> alat</span>
        <label class="crud-search-wrap"><span class="sr-only">Cari alat</span><x-ui.input type="search" data-table-search placeholder="Cari alat…" class="crud-search" /></label>
    </div>

    @if (auth()->user()->role === 'user')
        <form method="POST" action="{{ route('peminjaman.peminjamans.request') }}" class="loan-request-form">
            @csrf
    @endif
    <div class="table-wrap">
        <table class="barang-table">
            <caption class="sr-only">Daftar alat dan stok tersedia</caption>
            <thead><tr><th class="number-column">No.</th><th>Nama alat</th><th>Kategori</th><th>Stok tersedia</th><th>Deskripsi</th>@if (auth()->user()->role === 'user')<th>Jumlah pinjam</th>@elseif (auth()->user()->role === 'administrator')<th class="actions-heading">Aksi</th>@endif</tr></thead>
            <tbody data-table-body>
                @forelse ($alats as $alat)
                    <tr data-table-row @if (auth()->user()->role === 'administrator') data-edit-url="{{ route('peminjaman.alats.edit', $alat) }}" tabindex="0" @endif>
                        <td class="number-column">{{ $loop->iteration + (($alats->currentPage() - 1) * $alats->perPage()) }}</td>
                        <td>{{ $alat->nama_alat }}</td>
                        <td>{{ $alat->kategori->nama_kategori }}</td>
                        <td><span class="stock-count">{{ $alat->stok }}</span></td>
                        <td>{{ $alat->deskripsi ?: '—' }}</td>
                        @if (auth()->user()->role === 'user')
                            <td class="quantity-cell"><label class="quantity-control"><span class="sr-only">Jumlah pinjam untuk {{ $alat->nama_alat }}</span><x-ui.input class="quantity-input" type="number" name="items[{{ $alat->id }}]" value="{{ old('items.'.$alat->id, 0) }}" min="0" max="{{ $alat->stok }}" :disabled="$alat->stok < 1" aria-label="Jumlah pinjam untuk {{ $alat->nama_alat }}" /></label></td>
                        @elseif (auth()->user()->role === 'administrator')
                            <td class="actions-cell"><form method="POST" action="{{ route('peminjaman.alats.destroy', $alat) }}" data-confirm="Hapus alat {{ $alat->nama_alat }}?">@csrf @method('DELETE')<button type="submit" class="button button-delete button-small">Hapus</button></form></td>
                        @endif
                    </tr>
                @empty
                    <tr><td class="empty-state" colspan="{{ auth()->user()->role === 'user' || auth()->user()->role === 'administrator' ? 6 : 5 }}">Belum ada alat yang terdaftar.</td></tr>
                @endforelse
                <tr data-table-no-results hidden><td class="empty-state" colspan="{{ auth()->user()->role === 'user' || auth()->user()->role === 'administrator' ? 6 : 5 }}">Tidak ada data yang cocok.</td></tr>
            </tbody>
        </table>
    </div>

    @if (auth()->user()->role === 'user')
        <div class="loan-request-controls">
            <label class="crud-field">Rencana tanggal kembali<x-ui.input type="date" name="tanggal_kembali" min="{{ now()->toDateString() }}" value="{{ old('tanggal_kembali') }}" /></label>
            <label class="crud-field">Catatan (opsional)<x-ui.input type="text" name="catatan" maxlength="1000" value="{{ old('catatan') }}" /></label>
            <button class="button button-primary" type="submit">Ajukan peminjaman</button>
        </div>
        </form>
    @endif

    <div class="crud-pagination">{{ $alats->links() }}</div>
</section>
@endsection

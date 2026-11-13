@extends('layouts.dashboard')

@section('title', 'Peminjaman')
@section('body-class', 'dashboard-page developer-dashboard')
@section('dashboard-content')
<section class="crud-shell">
    <header class="crud-heading">
        <div><p class="eyebrow">Transaksi inventaris</p><h1 class="crud-title">Peminjaman</h1><p class="crud-description">Buka baris untuk melihat detail dan tindakan yang tersedia.</p></div>
        @if (auth()->user()->role === 'administrator')<a href="{{ route('peminjaman.peminjamans.create') }}" class="button button-primary">Tambah peminjaman</a>@endif
    </header>

    @if (session('success'))<p class="flash-success" role="status">{{ session('success') }}</p>@endif
    @if (session('error'))<p class="flash-error" role="alert">{{ session('error') }}</p>@endif

    <div class="crud-summary"><span><strong class="crud-summary-count">{{ $peminjamans->total() }}</strong> pengajuan</span><label class="crud-search-wrap"><span class="sr-only">Cari peminjaman</span><input type="search" data-table-search placeholder="Cari peminjaman…" class="crud-search"></label></div>
    <div class="table-wrap">
        <table class="barang-table">
            <thead><tr><th class="number-column">No.</th>@if (auth()->user()->role !== 'user')<th>Peminjam</th>@endif<th>Alat</th><th>Dipinjam</th><th>Rencana kembali</th><th>Dikembalikan</th><th>Status</th>@if (auth()->user()->role === 'administrator')<th class="actions-heading">Aksi</th>@endif</tr></thead>
            <tbody data-table-body>
                @forelse ($peminjamans as $peminjaman)
                    <tr data-table-row data-edit-url="{{ route('peminjaman.peminjamans.show', $peminjaman) }}" tabindex="0">
                        <td class="number-column">{{ $loop->iteration + (($peminjamans->currentPage() - 1) * $peminjamans->perPage()) }}</td>
                        @if (auth()->user()->role !== 'user')<td>{{ $peminjaman->user->name }}</td>@endif
                        <td><span class="loan-item-summary">@foreach ($peminjaman->items as $item){{ $item->alat->nama_alat }} × {{ $item->jumlah }}@if (! $loop->last), @endif @endforeach</span></td>
                        <td>{{ $peminjaman->tanggal_dipinjam?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>{{ $peminjaman->tanggal_kembali?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $peminjaman->dikembalikan_pada?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td><span class="status-badge status-{{ $peminjaman->status }}">{{ ['menunggu' => 'Menunggu', 'disetujui' => 'Dipinjam', 'ditolak' => 'Ditolak', 'menunggu_pengembalian' => 'Menunggu pengembalian', 'dikembalikan' => 'Dikembalikan'][$peminjaman->status] ?? $peminjaman->status }}</span></td>
                        @if (auth()->user()->role === 'administrator')
                            <td class="actions-cell"><form method="POST" action="{{ route('peminjaman.peminjamans.destroy', $peminjaman) }}" data-confirm="Hapus pengajuan ini?">@csrf @method('DELETE')<button class="button button-delete button-small" type="submit">Hapus</button></form></td>
                        @endif
                    </tr>
                @empty
                    <tr><td class="empty-state" colspan="{{ auth()->user()->role === 'administrator' ? 8 : (auth()->user()->role === 'user' ? 6 : 7) }}">Belum ada data peminjaman.</td></tr>
                @endforelse
                <tr data-table-no-results hidden><td class="empty-state" colspan="{{ auth()->user()->role === 'administrator' ? 8 : (auth()->user()->role === 'user' ? 6 : 7) }}">Tidak ada data yang cocok.</td></tr>
            </tbody>
        </table>
    </div>
    <div class="crud-pagination">{{ $peminjamans->links() }}</div>
</section>
@endsection

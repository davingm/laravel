@extends('layouts.dashboard')

@section('title', 'Laporan Peminjaman')
@section('body-class', 'dashboard-page developer-dashboard')
@section('dashboard-content')
<section class="crud-shell">
    <header class="crud-heading"><div><p class="eyebrow">Ringkasan inventaris</p><h1 class="crud-title">Laporan Peminjaman</h1><p class="crud-description">Ringkasan transaksi peminjaman dan pengembalian alat.</p></div><a class="button button-primary" href="{{ route('peminjaman.reports.export') }}">Export CSV</a></header>
    <div class="report-summary-grid">
        <div class="report-summary-card"><span>Total peminjaman</span><strong>{{ $totalPeminjaman }}</strong></div>
        <div class="report-summary-card"><span>Sedang dipinjam</span><strong>{{ $sedangDipinjam }}</strong></div>
        <div class="report-summary-card"><span>Total denda tercatat</span><strong>Rp {{ number_format($totalDenda, 0, ',', '.') }}</strong></div>
    </div>
    <div class="table-wrap"><table class="barang-table"><thead><tr><th class="number-column">No.</th><th>Peminjam</th><th>Alat</th><th>Jumlah</th><th>Dipinjam</th><th>Dikembalikan</th><th>Status</th><th>Denda</th></tr></thead><tbody>
        @forelse ($peminjamans as $peminjaman)
            <tr><td class="number-column">{{ $loop->iteration + (($peminjamans->currentPage() - 1) * $peminjamans->perPage()) }}</td><td>{{ $peminjaman->user->name }}</td><td>@foreach ($peminjaman->items as $item){{ $item->alat->nama_alat }}@if (! $loop->last), @endif @endforeach</td><td>{{ $peminjaman->items->sum('jumlah') }}</td><td>{{ $peminjaman->tanggal_dipinjam?->format('d/m/Y H:i') ?? '—' }}</td><td>{{ $peminjaman->dikembalikan_pada?->format('d/m/Y H:i') ?? '—' }}</td><td>{{ ['menunggu' => 'Menunggu', 'disetujui' => 'Dipinjam', 'ditolak' => 'Ditolak', 'menunggu_pengembalian' => 'Menunggu pengembalian', 'dikembalikan' => 'Dikembalikan'][$peminjaman->status] ?? $peminjaman->status }}</td><td>{{ $peminjaman->jumlah_denda > 0 ? 'Rp '.number_format($peminjaman->jumlah_denda, 0, ',', '.') : '—' }}</td></tr>
        @empty <tr><td colspan="8" class="empty-state">Belum ada transaksi peminjaman.</td></tr>@endforelse
    </tbody></table></div>
    <div class="crud-pagination">{{ $peminjamans->links() }}</div>
</section>
@endsection

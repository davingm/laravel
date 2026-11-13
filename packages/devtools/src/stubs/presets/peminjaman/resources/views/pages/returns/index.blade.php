@extends('layouts.dashboard')

@section('title', 'Pengembalian')
@section('body-class', 'dashboard-page developer-dashboard')
@section('dashboard-content')
<section class="crud-shell">
    <header class="crud-heading"><div><p class="eyebrow">Sirkulasi alat</p><h1 class="crud-title">Pengembalian</h1><p class="crud-description">Buka baris pengajuan untuk mengonfirmasi alat yang sudah diserahkan.</p></div></header>
    @if (session('success'))<p class="flash-success" role="status">{{ session('success') }}</p>@endif
    <div class="table-wrap"><table class="barang-table"><thead><tr><th class="number-column">No.</th><th>Peminjam</th><th>Alat</th><th>Diminta kembali</th><th>Status</th></tr></thead><tbody>
        @forelse ($peminjamans as $peminjaman)
            <tr data-edit-url="{{ route('peminjaman.peminjamans.show', $peminjaman) }}" tabindex="0"><td class="number-column">{{ $loop->iteration + (($peminjamans->currentPage() - 1) * $peminjamans->perPage()) }}</td><td>{{ $peminjaman->user->name }}</td><td>@foreach ($peminjaman->items as $item){{ $item->alat->nama_alat }} × {{ $item->jumlah }}@if (! $loop->last), @endif @endforeach</td><td>{{ $peminjaman->diminta_kembali_pada?->format('d/m/Y H:i') ?? '—' }}</td><td><span class="status-badge status-{{ $peminjaman->status }}">{{ $peminjaman->status === 'menunggu_pengembalian' ? 'Menunggu konfirmasi' : 'Dikembalikan' }}</span></td></tr>
        @empty <tr><td colspan="5" class="empty-state">Belum ada pengembalian untuk dipantau.</td></tr>@endforelse
    </tbody></table></div>
    <div class="crud-pagination">{{ $peminjamans->links() }}</div>
</section>
@endsection

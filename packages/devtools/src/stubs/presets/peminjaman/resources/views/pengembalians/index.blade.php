@extends('devtools-peminjaman.layouts.dashboard')

@section('title', 'Pengembalian')
@section('body-class', 'dashboard-page')

@section('dashboard-content')
<section class="crud-shell">
    <header class="crud-heading">
        <div>
            <h1 class="crud-title">{{ auth()->user()->role === 'operator' ? 'Pantau Pengembalian' : 'Pengembalian' }}</h1>
            <p class="crud-description">Konfirmasi alat yang sudah diserahkan kembali oleh peminjam.</p>
        </div>
    </header>

    @if (session('success'))<p class="flash-success" role="status">{{ session('success') }}</p>@endif
    @if (session('error'))<p class="flash-error" role="alert">{{ session('error') }}</p>@endif

    <div class="table-wrap">
        <table class="barang-table">
            <thead><tr><th>Peminjam</th><th>Alat</th><th>Jumlah</th><th>Diminta kembali</th><th>Status</th><th class="actions-heading">Aksi</th></tr></thead>
            <tbody>
                @forelse ($peminjamans as $peminjaman)
                    <tr>
                        <td>{{ $peminjaman->user->name }}</td>
                        <td>{{ $peminjaman->alat->nama_alat }}</td>
                        <td>{{ $peminjaman->jumlah }}</td>
                        <td>{{ $peminjaman->diminta_kembali_pada?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td><span class="status-badge status-{{ $peminjaman->status }}">{{ $peminjaman->status === 'menunggu_pengembalian' ? 'Menunggu konfirmasi' : 'Dikembalikan' }}</span></td>
                        <td class="actions-cell">
                            @if ($peminjaman->status === 'menunggu_pengembalian')
                                <div class="table-actions">
                                    <form method="POST" action="{{ route('peminjaman.pengembalians.complete', $peminjaman) }}" data-confirm="Konfirmasi alat ini sudah dikembalikan?">
                                        @csrf<button class="button button-primary button-small" type="submit">Konfirmasi kembali</button>
                                    </form>
                                    @if (auth()->user()->role === 'administrator')
                                        <form method="POST" action="{{ route('peminjaman.pengembalians.cancel', $peminjaman) }}" data-confirm="Batalkan permintaan pengembalian ini?">
                                            @csrf<button class="button button-secondary button-small" type="submit">Batalkan</button>
                                        </form>
                                    @endif
                                </div>
                            @else
                                <span class="table-muted">Selesai</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-state">Belum ada pengembalian untuk dipantau.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="crud-pagination">{{ $peminjamans->links() }}</div>
</section>
@endsection

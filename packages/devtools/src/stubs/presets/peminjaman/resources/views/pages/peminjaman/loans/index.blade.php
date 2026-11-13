@extends('layouts.dashboard')

@section('title', 'Peminjaman')
@section('body-class', 'dashboard-page')

@section('dashboard-content')
<section class="crud-shell">
    <header class="crud-heading">
        <div>
            <h1 class="crud-title">Peminjaman</h1>
            <p class="crud-description">
                @if (auth()->user()->role === 'operator')
                    Periksa pengajuan dan setujui peminjaman alat.
                @elseif (auth()->user()->role === 'user')
                    Lihat status pengajuan dan pinjaman Anda.
                @else
                    Kelola semua data peminjaman.
                @endif
            </p>
        </div>
        @if (auth()->user()->role === 'administrator')
            <a href="{{ route('peminjaman.peminjamans.create') }}" class="button button-primary">Tambah data</a>
        @elseif (auth()->user()->role === 'user')
            <a href="{{ route('peminjaman.alats.index') }}" class="button button-primary">Cari alat</a>
        @endif
    </header>

    @if (session('success'))<p class="flash-success" role="status">{{ session('success') }}</p>@endif
    @if (session('error'))<p class="flash-error" role="alert">{{ session('error') }}</p>@endif
    @if ($errors->any())<div class="form-error-summary" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

    <div class="crud-summary"><span><strong class="crud-summary-count">{{ $peminjamans->total() }}</strong> data</span></div>
    <div class="table-wrap">
        <table class="barang-table">
            <thead>
                <tr>
                    @if (auth()->user()->role !== 'user')<th>Peminjam</th>@endif
                    <th>Alat</th><th>Jumlah</th><th>Tanggal kembali</th><th>Status</th><th class="actions-heading">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($peminjamans as $peminjaman)
                    <tr>
                        @if (auth()->user()->role !== 'user')<td>{{ $peminjaman->user->name }}</td>@endif
                        <td>{{ $peminjaman->alat->nama_alat }}</td>
                        <td>{{ $peminjaman->jumlah }}</td>
                        <td>{{ $peminjaman->tanggal_kembali?->format('d/m/Y') ?? '—' }}</td>
                        <td><span class="status-badge status-{{ $peminjaman->status }}">{{ ['menunggu' => 'Menunggu persetujuan', 'disetujui' => 'Dipinjam', 'ditolak' => 'Ditolak', 'menunggu_pengembalian' => 'Menunggu konfirmasi kembali', 'dikembalikan' => 'Dikembalikan'][$peminjaman->status] }}</span></td>
                        <td class="actions-cell">
                            <div class="table-actions">
                                @if (auth()->user()->role === 'operator' && $peminjaman->status === 'menunggu')
                                    <form method="POST" action="{{ route('peminjaman.peminjamans.approve', $peminjaman) }}">
                                        @csrf<button class="button button-primary button-small" type="submit">Setujui</button>
                                    </form>
                                    <form method="POST" action="{{ route('peminjaman.peminjamans.reject', $peminjaman) }}">
                                        @csrf<button class="button button-secondary button-small" type="submit">Tolak</button>
                                    </form>
                                @elseif (auth()->user()->role === 'user' && $peminjaman->status === 'disetujui')
                                    <form method="POST" action="{{ route('peminjaman.peminjamans.return', $peminjaman) }}">
                                        @csrf<button class="button button-primary button-small" type="submit">Ajukan pengembalian</button>
                                    </form>
                                @elseif (auth()->user()->role === 'administrator')
                                    @if ($peminjaman->status === 'menunggu')
                                        <a href="{{ route('peminjaman.peminjamans.edit', $peminjaman) }}" class="button button-secondary button-small">Edit</a>
                                    @endif
                                    <form method="POST" action="{{ route('peminjaman.peminjamans.destroy', $peminjaman) }}" data-confirm="Hapus data peminjaman ini?">
                                        @csrf @method('DELETE')
                                        <button class="button button-secondary button-small" type="submit">Hapus</button>
                                    </form>
                                @else
                                    <span class="table-muted">—</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ auth()->user()->role === 'user' ? 5 : 6 }}" class="empty-state">Belum ada data peminjaman.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="crud-pagination">{{ $peminjamans->links() }}</div>
</section>
@endsection

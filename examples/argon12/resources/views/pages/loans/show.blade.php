@extends('layouts.dashboard')

@section('title', 'Detail Peminjaman')
@section('body-class', 'dashboard-page developer-dashboard')
@section('dashboard-content')
<section class="crud-shell narrow-shell">
    <header class="crud-form-heading"><a href="{{ route('peminjaman.peminjamans.index') }}" class="back-link">&larr; Kembali ke peminjaman</a><p class="eyebrow">Detail pengajuan</p><h1 class="crud-title">Peminjaman #{{ $peminjaman->id }}</h1><p class="crud-description">{{ $peminjaman->user->name }} · <span class="status-badge status-{{ $peminjaman->status }}">{{ $peminjaman->status }}</span></p></header>

    @if (session('success'))<p class="flash-success" role="status">{{ session('success') }}</p>@endif
    @if (session('error'))<p class="flash-error" role="alert">{{ session('error') }}</p>@endif
    @if ($errors->any())<div class="form-error-summary" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

    <div class="table-wrap">
        <table class="barang-table loan-detail-table">
            <thead><tr><th class="number-column">No.</th><th>Alat</th><th>Kategori</th><th>Jumlah</th><th>Stok sekarang</th></tr></thead>
            <tbody>
                @foreach ($peminjaman->items as $item)
                    <tr><td class="number-column">{{ $loop->iteration }}</td><td>{{ $item->alat->nama_alat }}</td><td>{{ $item->alat->kategori->nama_kategori }}</td><td>{{ $item->jumlah }}</td><td>{{ $item->alat->stok }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <dl class="loan-dates">
        <div><dt>Diajukan</dt><dd>{{ $peminjaman->created_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
        <div><dt>Mulai dipinjam</dt><dd>{{ $peminjaman->tanggal_dipinjam?->format('d/m/Y H:i') ?? '—' }}</dd></div>
        <div><dt>Rencana kembali</dt><dd>{{ $peminjaman->tanggal_kembali?->format('d/m/Y') ?? '—' }}</dd></div>
        <div><dt>Dikembalikan</dt><dd>{{ $peminjaman->dikembalikan_pada?->format('d/m/Y H:i') ?? '—' }}</dd></div>
    </dl>
    @if ($peminjaman->jumlah_denda > 0)
        <p class="loan-note"><strong>Denda keterlambatan:</strong> {{ $peminjaman->hari_terlambat }} hari × Rp {{ number_format((int) config('peminjaman.denda_per_hari', 1000), 0, ',', '.') }} = <strong>Rp {{ number_format($peminjaman->jumlah_denda, 0, ',', '.') }}</strong> · {{ $peminjaman->denda_dibayar_pada ? 'Lunas' : 'Belum dibayar' }}</p>
    @endif
    @if ($peminjaman->catatan)<p class="loan-note"><strong>Catatan:</strong> {{ $peminjaman->catatan }}</p>@endif

    <div class="crud-form-actions">
        @if (auth()->user()->role === 'administrator' && $peminjaman->status === 'menunggu')<a href="{{ route('peminjaman.peminjamans.edit', $peminjaman) }}" class="button button-secondary">Ubah pengajuan</a>@endif
        @if (in_array(auth()->user()->role, ['administrator', 'operator'], true) && $peminjaman->status === 'menunggu')
            <form method="POST" action="{{ route('peminjaman.peminjamans.approve', $peminjaman) }}">@csrf<button class="button button-primary" type="submit">Setujui</button></form>
            <form method="POST" action="{{ route('peminjaman.peminjamans.reject', $peminjaman) }}" data-confirm="Tolak pengajuan ini?">@csrf<button class="button button-secondary" type="submit">Tolak</button></form>
        @endif
        @if (auth()->user()->role === 'user' && $peminjaman->status === 'disetujui')<form method="POST" action="{{ route('peminjaman.peminjamans.return', $peminjaman) }}">@csrf<button class="button button-primary" type="submit">Ajukan pengembalian</button></form>@endif
        @if (in_array(auth()->user()->role, ['administrator', 'operator'], true) && $peminjaman->status === 'menunggu_pengembalian')
            <form method="POST" action="{{ route('peminjaman.pengembalians.complete', $peminjaman) }}" data-confirm="Konfirmasi semua alat sudah diterima kembali?">@csrf<button class="button button-primary" type="submit">Konfirmasi pengembalian</button></form>
            <form method="POST" action="{{ route('peminjaman.pengembalians.cancel', $peminjaman) }}" data-confirm="Batalkan permintaan pengembalian ini?">@csrf<button class="button button-secondary" type="submit">Batalkan permintaan</button></form>
        @endif
        @if (in_array(auth()->user()->role, ['administrator', 'operator'], true) && $peminjaman->status === 'dikembalikan' && $peminjaman->jumlah_denda > 0 && ! $peminjaman->denda_dibayar_pada)
            <form method="POST" action="{{ route('peminjaman.pengembalians.fine-paid', $peminjaman) }}" data-confirm="Tandai denda Rp {{ number_format($peminjaman->jumlah_denda, 0, ',', '.') }} sudah dibayar?">@csrf<button class="button button-primary" type="submit">Tandai denda lunas</button></form>
        @endif
    </div>
</section>
@endsection

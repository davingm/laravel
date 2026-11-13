@extends('layouts.dashboard')

@section('title', 'Edit Peminjaman')
@section('body-class', 'dashboard-page')

@section('dashboard-content')
<section class="crud-shell narrow-shell">
    <header class="crud-form-heading">
        <a href="{{ route('peminjaman.peminjamans.index') }}" class="back-link">&larr; Kembali ke peminjaman</a>
        <h1 class="crud-title">Edit Pengajuan</h1>
        <p class="crud-description">Pengajuan yang sudah diproses tidak bisa diubah.</p>
    </header>

    <form method="POST" action="{{ route('peminjaman.peminjamans.update', $peminjaman) }}" class="barang-form">
        @csrf @method('PUT')
        @if ($errors->any())<div class="form-error-summary" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="barang-form-grid">
            <label class="crud-field">Peminjam
                <select name="user_id" required class="crud-input">
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(old('user_id', $peminjaman->user_id) == $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
            </label>
            <label class="crud-field">Alat
                <select name="alat_id" required class="crud-input">
                    @foreach ($alats as $alat)
                        <option value="{{ $alat->id }}" @selected(old('alat_id', $peminjaman->alat_id) == $alat->id)>{{ $alat->nama_alat }} — stok {{ $alat->stok }}</option>
                    @endforeach
                </select>
            </label>
            <label class="crud-field">Jumlah
                <input type="number" name="jumlah" min="1" value="{{ old('jumlah', $peminjaman->jumlah) }}" required class="crud-input">
            </label>
            <label class="crud-field">Rencana tanggal kembali
                <input type="date" name="tanggal_kembali" min="{{ now()->toDateString() }}" value="{{ old('tanggal_kembali', $peminjaman->tanggal_kembali?->format('Y-m-d')) }}" class="crud-input">
            </label>
            <label class="crud-field">Catatan
                <textarea name="catatan" rows="3" class="crud-input">{{ old('catatan', $peminjaman->catatan) }}</textarea>
            </label>
        </div>
        <div class="crud-form-actions">
            <a href="{{ route('peminjaman.peminjamans.index') }}" class="button button-secondary">Batal</a>
            <button class="button button-primary" type="submit">Simpan perubahan</button>
        </div>
    </form>
</section>
@endsection

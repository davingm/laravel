@extends('layouts.dashboard')

@section('title', 'Tambah Peminjaman')
@section('body-class', 'dashboard-page')

@section('dashboard-content')
<section class="crud-shell narrow-shell">
    <header class="crud-form-heading">
        <a href="{{ route('peminjaman.peminjamans.index') }}" class="back-link">&larr; Kembali ke peminjaman</a>
        <h1 class="crud-title">Tambah Peminjaman</h1>
        <p class="crud-description">Data baru akan menunggu persetujuan petugas.</p>
    </header>

    <form method="POST" action="{{ route('peminjaman.peminjamans.store') }}" class="barang-form">
        @csrf
        @if ($errors->any())<div class="form-error-summary" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="barang-form-grid">
            <label class="crud-field">Peminjam
                <select name="user_id" required class="crud-input">
                    <option value="">Pilih peminjam</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
                @error('user_id')<span class="form-error">{{ $message }}</span>@enderror
            </label>
            <label class="crud-field">Alat
                <select name="alat_id" required class="crud-input">
                    <option value="">Pilih alat</option>
                    @foreach ($alats as $alat)
                        <option value="{{ $alat->id }}" @selected(old('alat_id') == $alat->id)>{{ $alat->nama_alat }} — stok {{ $alat->stok }}</option>
                    @endforeach
                </select>
                @error('alat_id')<span class="form-error">{{ $message }}</span>@enderror
            </label>
            <label class="crud-field">Jumlah
                <input type="number" name="jumlah" min="1" value="{{ old('jumlah', 1) }}" required class="crud-input">
                @error('jumlah')<span class="form-error">{{ $message }}</span>@enderror
            </label>
            <label class="crud-field">Rencana tanggal kembali
                <input type="date" name="tanggal_kembali" min="{{ now()->toDateString() }}" value="{{ old('tanggal_kembali') }}" class="crud-input">
                @error('tanggal_kembali')<span class="form-error">{{ $message }}</span>@enderror
            </label>
            <label class="crud-field">Catatan
                <textarea name="catatan" rows="3" class="crud-input">{{ old('catatan') }}</textarea>
                @error('catatan')<span class="form-error">{{ $message }}</span>@enderror
            </label>
        </div>
        <div class="crud-form-actions">
            <a href="{{ route('peminjaman.peminjamans.index') }}" class="button button-secondary">Batal</a>
            <button class="button button-primary" type="submit">Simpan</button>
        </div>
    </form>
</section>
@endsection

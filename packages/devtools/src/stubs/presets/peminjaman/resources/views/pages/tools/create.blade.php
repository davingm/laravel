@extends('layouts.dashboard')

@section('title', 'Tambah Alat')
@section('body-class', 'dashboard-page developer-dashboard')
@section('dashboard-content')
<section class="crud-shell narrow-shell">
    <header class="crud-form-heading">
        <a href="{{ route('peminjaman.alats.index') }}" class="back-link">&larr; Kembali ke daftar</a>
        <p class="eyebrow">Data baru</p>
        <h1 class="crud-title">Tambah Alat</h1>
        <p class="crud-description">Isi informasi Alat.</p>
    </header>

    <form method="POST" action="{{ route('peminjaman.alats.store') }}" class="barang-form">
        @csrf
        @if ($errors->any())
            <div class="form-error-summary" role="alert">
                <strong>Periksa kembali isian berikut:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="barang-form-grid">
    <label class="crud-field">
        Nama Alat
        <input type="text" name="nama_alat" value="{{ old('nama_alat') }}" required class="crud-input">
        @error('nama_alat')<span class="form-error">{{ $message }}</span>@enderror
    </label>
    <label class="crud-field">
        Deskripsi
        <textarea name="deskripsi" rows="4" class="crud-input">{{ old('deskripsi') }}</textarea>
        @error('deskripsi')<span class="form-error">{{ $message }}</span>@enderror
    </label>
    <label class="crud-field">
        Kategori
        <select name="kategori_id" required class="crud-input">
            <option value="">Pilih kategori</option>
            @foreach($kategoris as $kategori)
                <option value="{{ $kategori->id }}" @selected(old('kategori_id') == $kategori->id)>{{ $kategori->nama_kategori }}</option>
            @endforeach
        </select>
        @error('kategori_id')<span class="form-error">{{ $message }}</span>@enderror
    </label>
    <label class="crud-field">
        Jumlah stok
        <input type="number" name="stok" value="{{ old('stok', 1) }}" min="0" required class="crud-input">
        @error('stok')<span class="form-error">{{ $message }}</span>@enderror
    </label>
        </div>
        <div class="crud-form-actions">
            <a href="{{ route('peminjaman.alats.index') }}" class="button button-secondary">Batal</a>
            <button type="submit" class="button button-primary">Simpan</button>
        </div>
    </form>
</section>
@endsection

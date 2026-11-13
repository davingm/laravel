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

    <x-ui.form method="POST" action="{{ route('peminjaman.alats.store') }}">
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
        <x-ui.input type="text" name="nama_alat" value="{{ old('nama_alat') }}" required />
        @error('nama_alat')<span class="form-error">{{ $message }}</span>@enderror
    </label>
    <label class="crud-field">
        Deskripsi
        <x-ui.textarea name="deskripsi" rows="4">{{ old('deskripsi') }}</x-ui.textarea>
        @error('deskripsi')<span class="form-error">{{ $message }}</span>@enderror
    </label>
    <label class="crud-field">
        Kategori
        <x-ui.select name="kategori_id" required>
            <option value="">Pilih kategori</option>
            @foreach($kategoris as $kategori)
                <option value="{{ $kategori->id }}" @selected(old('kategori_id') == $kategori->id)>{{ $kategori->nama_kategori }}</option>
            @endforeach
        </x-ui.select>
        @error('kategori_id')<span class="form-error">{{ $message }}</span>@enderror
    </label>
    <label class="crud-field">
        Jumlah stok
        <x-ui.input type="number" name="stok" value="{{ old('stok', 1) }}" min="0" required />
        @error('stok')<span class="form-error">{{ $message }}</span>@enderror
    </label>
        </div>
        <div class="crud-form-actions">
            <a href="{{ route('peminjaman.alats.index') }}" class="button button-secondary">Batal</a>
            <x-ui.button type="submit">Simpan</x-ui.button>
        </div>
    </x-ui.form>
</section>
@endsection

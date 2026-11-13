@extends('layouts.dashboard')

@section('title', 'Edit Kategori')
@section('body-class', 'dashboard-page')
@section('dashboard-content')
<section class="crud-shell narrow-shell">
    <header class="crud-form-heading">
        <a href="{{ route('peminjaman.kategoris.index') }}" class="back-link">&larr; Kembali ke daftar</a>
        <p class="eyebrow">Perbarui data</p>
        <h1 class="crud-title">Edit Kategori</h1>
        <p class="crud-description">Ubah informasi Kategori.</p>
    </header>

    <form method="POST" action="{{ route('peminjaman.kategoris.update', $kategori) }}" class="barang-form">
        @csrf
        @method('PUT')
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
        Nama Kategori
        <input type="text" name="nama_kategori" value="{{ old('nama_kategori', $kategori->nama_kategori) }}" required class="crud-input">
        @error('nama_kategori')<span class="form-error">{{ $message }}</span>@enderror
    </label>
    <label class="crud-field">
        Deskripsi
        <textarea name="deskripsi" rows="4" class="crud-input">{{ old('deskripsi', $kategori->deskripsi) }}</textarea>
        @error('deskripsi')<span class="form-error">{{ $message }}</span>@enderror
    </label>
        </div>
        <div class="crud-form-actions">
            <a href="{{ route('peminjaman.kategoris.index') }}" class="button button-secondary">Batal</a>
            <button type="submit" class="button button-primary">Simpan perubahan</button>
        </div>
    </form>
</section>
@endsection

@extends('layouts.app')

@section('content')
<section class="crud-shell narrow-shell">
    <header class="crud-form-heading">
        <a href="{{ route('siswas.index') }}" class="back-link">&larr; Kembali ke daftar</a>
        <p class="eyebrow">Data baru</p>
        <h1 class="crud-title">Tambah Siswa</h1>
        <p class="crud-description">Lengkapi informasi siswa di bawah ini.</p>
    </header>

    <form method="POST" action="{{ route('siswas.store') }}" class="barang-form">
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
    <label>
        Nama
        <input type="text" name="nama" value="{{ old('nama') }}" required>
        @error('nama')<span class="form-error">{{ $message }}</span>@enderror
    </label>
    <label>
        Kelas
        <input type="text" name="kelas" value="{{ old('kelas') }}" required>
        @error('kelas')<span class="form-error">{{ $message }}</span>@enderror
    </label>
    <label>
        No Absen
        <input type="number" name="no_absen" value="{{ old('no_absen') }}" required>
        @error('no_absen')<span class="form-error">{{ $message }}</span>@enderror
    </label>
        </div>
        <div class="crud-form-actions">
            <a href="{{ route('siswas.index') }}" class="button button-secondary">Batal</a>
            <button type="submit" class="button button-primary">Simpan Siswa</button>
        </div>
    </form>
</section>
@endsection
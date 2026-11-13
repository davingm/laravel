@extends('layouts.dashboard')

@section('title', 'Tambah Pengguna')
@section('body-class', 'dashboard-page')

@section('dashboard-content')
<section class="crud-shell narrow-shell">
    <header class="crud-form-heading">
        <a href="{{ route('peminjaman.users.index') }}" class="back-link">&larr; Kembali ke pengguna</a>
        <h1 class="crud-title">Tambah Pengguna</h1>
        <p class="crud-description">Buat akun admin, petugas, atau peminjam.</p>
    </header>

    <form method="POST" action="{{ route('peminjaman.users.store') }}" class="barang-form">
        @csrf
        @if ($errors->any())
            <div class="form-error-summary" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <div class="barang-form-grid">
            <label class="crud-field">Nama
                <input name="name" value="{{ old('name') }}" required class="crud-input">
                @error('name')<span class="form-error">{{ $message }}</span>@enderror
            </label>
            <label class="crud-field">Email
                <input type="email" name="email" value="{{ old('email') }}" required class="crud-input">
                @error('email')<span class="form-error">{{ $message }}</span>@enderror
            </label>
            <label class="crud-field">Peran
                <select name="role" required class="crud-input">
                    <option value="user" @selected(old('role', 'user') === 'user')>Peminjam</option>
                    <option value="operator" @selected(old('role') === 'operator')>Petugas</option>
                    <option value="administrator" @selected(old('role') === 'administrator')>Admin besar</option>
                </select>
                @error('role')<span class="form-error">{{ $message }}</span>@enderror
            </label>
            <label class="crud-field">Password
                <input type="password" name="password" required minlength="8" class="crud-input">
                @error('password')<span class="form-error">{{ $message }}</span>@enderror
            </label>
        </div>
        <div class="crud-form-actions">
            <a href="{{ route('peminjaman.users.index') }}" class="button button-secondary">Batal</a>
            <button class="button button-primary" type="submit">Simpan</button>
        </div>
    </form>
</section>
@endsection

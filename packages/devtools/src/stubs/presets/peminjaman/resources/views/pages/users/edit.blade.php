@extends('layouts.dashboard')

@section('title', 'Edit Pengguna')
@section('body-class', 'dashboard-page developer-dashboard')

@section('dashboard-content')
<section class="crud-shell narrow-shell">
    <header class="crud-form-heading">
        <a href="{{ route('peminjaman.users.index') }}" class="back-link">&larr; Kembali ke pengguna</a>
        <h1 class="crud-title">Edit Pengguna</h1>
        <p class="crud-description">Kosongkan password jika tidak ingin mengubahnya.</p>
    </header>

    <form method="POST" action="{{ route('peminjaman.users.update', $user) }}" class="barang-form">
        @csrf
        @method('PUT')
        @if ($errors->any())
            <div class="form-error-summary" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <div class="barang-form-grid">
            <label class="crud-field">Nama
                <input name="name" value="{{ old('name', $user->name) }}" required class="crud-input">
                @error('name')<span class="form-error">{{ $message }}</span>@enderror
            </label>
            <label class="crud-field">Email
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="crud-input">
                @error('email')<span class="form-error">{{ $message }}</span>@enderror
            </label>
            <label class="crud-field">Peran
                <select name="role" required class="crud-input">
                    <option value="user" @selected(old('role', $user->role) === 'user')>Peminjam</option>
                    <option value="operator" @selected(old('role', $user->role) === 'operator')>Petugas</option>
                    <option value="administrator" @selected(old('role', $user->role) === 'administrator')>Admin besar</option>
                </select>
                @error('role')<span class="form-error">{{ $message }}</span>@enderror
            </label>
            <label class="crud-field">Password baru
                <input type="password" name="password" minlength="8" class="crud-input">
                @error('password')<span class="form-error">{{ $message }}</span>@enderror
            </label>
        </div>
        <div class="crud-form-actions">
            <a href="{{ route('peminjaman.users.index') }}" class="button button-secondary">Batal</a>
            <button class="button button-primary" type="submit">Simpan perubahan</button>
        </div>
    </form>
</section>
@endsection

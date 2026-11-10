@extends('layouts.app')

@section('title', 'Register')

@section('content')
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-brand">
            <svg class="auth-brand-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/>
            </svg>
            <h1 class="auth-title">Buat akun baru</h1>
            <p class="auth-subtitle">Daftar ke {{ config('app.name') }}</p>
        </div>

        <form method="POST" action="{{ route('register.store') }}" class="auth-form">
            @csrf

            <div class="auth-field">
                <label for="name" class="auth-label">Nama</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}"
                    required autofocus autocomplete="name" class="auth-input">
                @error('name')
                    <span class="auth-field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="auth-field">
                <label for="email" class="auth-label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}"
                    required autocomplete="email" class="auth-input">
                @error('email')
                    <span class="auth-field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="auth-field">
                <label for="password" class="auth-label">Password</label>
                <input id="password" name="password" type="password" required
                    autocomplete="new-password" class="auth-input">
                @error('password')
                    <span class="auth-field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="auth-field">
                <label for="password_confirmation" class="auth-label">Konfirmasi Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password"
                    required autocomplete="new-password" class="auth-input">
            </div>

            <button type="submit" class="auth-submit">Daftar</button>
        </form>

        <p class="auth-footer">
            Sudah punya akun?
            <a href="{{ route('login') }}">Masuk</a>
        </p>
    </div>
</div>
@endsection

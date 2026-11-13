@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-brand">
            <svg class="auth-brand-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09Z"/>
            </svg>
            <h1 class="sr-only">Masuk</h1>
        </div>

        @if (session('error'))
            <p class="auth-error" role="alert">{{ session('error') }}</p>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="auth-form">
            @csrf

            <div class="auth-field">
                <label for="email" class="auth-label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}"
                    required autofocus autocomplete="username" class="auth-input">
                @error('email')
                    <span class="auth-field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="auth-field">
                <label for="password" class="auth-label">Password</label>
            <div class="auth-password-field">
                <input id="password" name="password" type="password" required
                    autocomplete="current-password" class="auth-input">
                <button type="button" class="auth-password-toggle" data-toggle-password="password" aria-controls="password" aria-label="Tampilkan password">Tampilkan</button>
            </div>
                @error('password')
                    <span class="auth-field-error">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="auth-submit">Masuk</button>
        </form>

        <p class="auth-footer">
            Belum punya akun?
            <a href="{{ route('register') }}">Daftar</a>
        </p>
    </div>
</div>
@endsection

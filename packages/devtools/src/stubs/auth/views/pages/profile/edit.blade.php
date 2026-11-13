@extends('layouts.dashboard')

@section('title', 'Profile')
@section('body-class', 'dashboard-page')

@section('dashboard-content')
            <div id="profile-main" class="profile-main">
            <section class="profile-content">
                <div class="profile-heading">
                    <p class="profile-eyebrow">Pengaturan akun</p>
                    <h1>Profile</h1>
                    <p>Perbarui informasi akun dan kata sandi Anda.</p>
                </div>

                @if (session('success'))
                    <div class="profile-alert profile-alert-success" role="status">{{ session('success') }}</div>
                @endif

                @if ($errors->any())
                    <div class="profile-alert profile-alert-error" role="alert">
                        <p>Periksa kembali data yang Anda masukkan.</p>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form class="profile-form" method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PATCH')

                    <div class="profile-section">
                        <h2>Informasi akun</h2>
                        <label class="profile-field" for="name">
                            <span>Nama</span>
                            <input id="name" name="name" type="text" value="{{ old('name', auth()->user()->name) }}" required autocomplete="name">
                        </label>
                        <label class="profile-field" for="email">
                            <span>Email</span>
                            <input id="email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required autocomplete="email">
                        </label>
                        <div class="profile-actions">
                            <button class="profile-save-button" type="submit">Simpan profil</button>
                        </div>
                    </div>
                </form>

                <form class="profile-form" method="POST" action="{{ route('profile.password') }}">
                    @csrf
                    @method('PATCH')
                    <div class="profile-section">
                        <h2>Ubah kata sandi</h2>
                        <label class="profile-field" for="current_password">
                            <span>Kata sandi saat ini</span>
                            <input id="current_password" name="current_password" type="password" autocomplete="current-password">
                        </label>
                        <label class="profile-field" for="password">
                            <span>Kata sandi baru</span>
                            <input id="password" name="password" type="password" autocomplete="new-password">
                        </label>
                        <label class="profile-field" for="password_confirmation">
                            <span>Konfirmasi kata sandi baru</span>
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
                        </label>
                        <div class="profile-actions">
                            <button class="profile-save-button" type="submit">Ubah kata sandi</button>
                        </div>
                    </div>
                </form>
            </section>
            </div>
@endsection

@extends('auto-src::layouts.app')

@section('title', 'Sign in')

@section('content')
<section class="crud-shell narrow-shell auth-shell">
    <p class="eyebrow">Your account</p>
    <h1 class="crud-title">Sign in</h1>
    <p class="lede">Enter your username and password to continue.</p>

    @if ($errors->any())
        <div class="auth-errors" role="alert">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form class="barang-form auth-form" method="POST" action="{{ route('login.store') }}">
        @csrf
        <div class="barang-form-grid">
            <label class="field-wide" for="username">Username
                <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus autocomplete="username">
            </label>
            <label class="field-wide" for="password">Password
                <input id="password" name="password" type="password" required autocomplete="current-password">
            </label>
            <label class="auth-remember" for="remember">
                <input id="remember" name="remember" type="checkbox" value="1">
                Remember me
            </label>
        </div>
        <button class="button button-primary" type="submit">Sign in <span>→</span></button>
    </form>
</section>
@endsection

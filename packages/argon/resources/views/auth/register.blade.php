@extends('layouts.app')
@section('title', 'Daftar')
@section('body-class', 'bg-default')
@section('content')
<div class="main-content"><div class="header bg-gradient-primary py-7 py-lg-8"><div class="container"><div class="header-body text-center mb-7"><h1 class="text-white">Argon</h1><p class="text-lead text-white">Buat akun peminjaman</p></div></div></div>
<div class="container mt--8 pb-5"><div class="row justify-content-center"><div class="col-lg-5 col-md-7"><div class="card bg-secondary shadow border-0"><div class="card-header bg-transparent pb-3"><div class="text-muted text-center"><h2>Daftar akun</h2></div></div><div class="card-body px-lg-5 py-lg-5">
<form method="POST" action="{{ route('register.store') }}">@csrf
<div class="form-group"><label for="name">Nama</label><input id="name" name="name" value="{{ old('name') }}" class="form-control" required autocomplete="name">@error('name')<small class="text-danger">{{ $message }}</small>@enderror</div>
<div class="form-group"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" class="form-control" required autocomplete="email">@error('email')<small class="text-danger">{{ $message }}</small>@enderror</div>
<div class="form-group"><label for="password">Password</label><input id="password" name="password" type="password" class="form-control" required autocomplete="new-password">@error('password')<small class="text-danger">{{ $message }}</small>@enderror</div>
<div class="form-group"><label for="password_confirmation">Konfirmasi password</label><input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required autocomplete="new-password"></div>
<button class="btn btn-primary btn-block" type="submit">Daftar</button></form><div class="text-center mt-3"><a href="{{ route('login') }}">Sudah punya akun? Masuk</a></div></div></div></div></div></div></div>
@endsection

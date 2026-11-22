@extends('layouts.app')
@section('title', 'Masuk')
@section('body-class', 'bg-default')
@section('content')
<div class="main-content"><div class="header bg-gradient-primary py-7 py-lg-8"><div class="container"><div class="header-body text-center mb-7"><h1 class="text-white">Argon</h1><p class="text-lead text-white">Sistem peminjaman</p></div></div></div>
<div class="container mt--8 pb-5"><div class="row justify-content-center"><div class="col-lg-5 col-md-7"><div class="card bg-secondary shadow border-0"><div class="card-header bg-transparent pb-3"><div class="text-muted text-center"><h2>Masuk ke akun</h2></div></div><div class="card-body px-lg-5 py-lg-5">
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
<form method="POST" action="{{ route('login.store') }}">@csrf
<div class="form-group"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" class="form-control" required autofocus autocomplete="username">@error('email')<small class="text-danger">{{ $message }}</small>@enderror</div>
<div class="form-group"><label for="password">Password</label><input id="password" name="password" type="password" class="form-control" required autocomplete="current-password">@error('password')<small class="text-danger">{{ $message }}</small>@enderror</div>
<button class="btn btn-primary btn-block" type="submit">Masuk</button></form><div class="text-center mt-3"><a href="{{ route('register') }}">Buat akun</a></div></div></div></div></div></div></div>
@endsection

@extends('layouts.app')
@section('body-class', 'g-sidenav-show g-sidenav-pinned bg-default')
@section('content')
<nav class="navbar navbar-vertical fixed-left navbar-expand-md navbar-light bg-white" id="sidenav-main">
 <div class="container-fluid"><button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#sidenav-collapse-main"><span class="navbar-toggler-icon"></span></button>
 <a class="navbar-brand pt-0" href="{{ route('dashboard') }}"><img src="{{ asset('assets/img/brand/blue.png') }}" class="navbar-brand-img" alt="Argon"></a>
 <div class="collapse navbar-collapse" id="sidenav-collapse-main"><div class="navbar-collapse-header d-md-none"><div class="row"><div class="col-6 collapse-brand"><a href="{{ route('dashboard') }}"><img src="{{ asset('assets/img/brand/blue.png') }}"></a></div><div class="col-6 collapse-close"><button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#sidenav-collapse-main"><span></span><span></span></button></div></div></div>
 <ul class="navbar-nav">
 <li class="nav-item"><a class="nav-link" href="{{ route('dashboard') }}"><i class="ni ni-tv-2 text-primary"></i> Ringkasan</a></li>
 <li class="nav-item"><a class="nav-link" href="{{ route('peminjaman.alats.index') }}"><i class="ni ni-basket text-orange"></i> Alat</a></li>
 @if(in_array(auth()->user()->role, ['administrator','operator'], true))<li class="nav-item"><a class="nav-link" href="{{ route('peminjaman.kategoris.index') }}"><i class="ni ni-collection text-purple"></i> Kategori</a></li>@endif
 <li class="nav-item"><a class="nav-link" href="{{ route('peminjaman.peminjamans.index') }}"><i class="ni ni-single-copy-04 text-yellow"></i> Peminjaman</a></li>
 @if(in_array(auth()->user()->role, ['administrator','operator'], true))<li class="nav-item"><a class="nav-link" href="{{ route('peminjaman.pengembalians.index') }}"><i class="ni ni-box-2 text-info"></i> Pengembalian</a></li><li class="nav-item"><a class="nav-link" href="{{ route('peminjaman.reports.index') }}"><i class="ni ni-chart-bar-32 text-success"></i> Laporan</a></li>@endif
 @if(auth()->user()->role === 'administrator')<li class="nav-item"><a class="nav-link" href="{{ route('peminjaman.users.index') }}"><i class="ni ni-circle-08 text-pink"></i> Pengguna</a></li><li class="nav-item"><a class="nav-link" href="{{ route('activity.index') }}"><i class="ni ni-bell-55 text-default"></i> Aktivitas</a></li>@endif
 </ul><hr class="my-3"><ul class="navbar-nav"><li class="nav-item"><form method="POST" action="{{ route('logout') }}">@csrf<button class="nav-link btn btn-link"><i class="ni ni-user-run text-red"></i> Keluar</button></form></li></ul>
 </div></div>
</nav>
<div class="main-content">
 <nav class="navbar navbar-top navbar-expand-md navbar-dark" id="navbar-main"><div class="container-fluid"><span class="h4 mb-0 text-white">@yield('title', 'Dashboard')</span><ul class="navbar-nav"><li class="nav-item"><span class="nav-link text-white">{{ auth()->user()->name }}</span></li></ul></div></nav>
 <div class="header bg-gradient-primary pb-8 pt-5 pt-md-8"></div>
 <div class="container-fluid mt--7"><div class="row"><div class="col">
 @yield('dashboard-content')
 </div></div><footer class="footer"><div class="row align-items-center justify-content-xl-between"><div class="col-xl-6"><div class="copyright text-center text-xl-left text-muted">Argon Dashboard · Creative Tim</div></div></div></footer></div>
</div><div class="sidenav-mask d-xl-none" data-action="sidenav-pin" data-target="#sidenav-main"></div>
@endsection

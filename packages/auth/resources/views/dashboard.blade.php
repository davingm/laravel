@extends('davingm-src::layouts.app')

@section('title', 'Dashboard')

@section('content')
<section class="crud-shell narrow-shell auth-shell">
    <p class="eyebrow">Signed in</p>
    <h1 class="crud-title">Welcome, {{ auth()->user()->username }}.</h1>
    <p class="lede">You are signed in to your account.</p>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="button button-primary" type="submit">Sign out</button>
    </form>
</section>
@endsection

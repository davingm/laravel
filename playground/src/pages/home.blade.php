@extends('layouts.app')

@php
$seo = [
    'title' => 'Home',
    'description' => 'Halaman Home.',
    'ogTitle' => 'Home',
    'ogDescription' => 'Halaman Home.',
    'ogImage' => asset('images/og-image.jpg'),
];
@endphp

@section('seo')
    <x-seo-meta :seo="$seo" />
@endsection

@section('content')
<section class="crud-shell">
    <h1 class="crud-title">Home</h1>
</section>
@endsection
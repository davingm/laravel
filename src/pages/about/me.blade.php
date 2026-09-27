@extends('layouts.app')

@php
$seo = [
    'title' => 'About Me',
    'description' => 'Halaman About Me.',
    'ogTitle' => 'About Me',
    'ogDescription' => 'Halaman About Me.',
    'ogImage' => asset('images/og-image.jpg'),
];
@endphp

@section('seo')
    <x-seo-meta :seo="$seo" />
@endsection

@section('content')
<section class="crud-shell">
    <h1 class="crud-title">About Me</h1>
</section>
@endsection
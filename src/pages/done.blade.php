@extends('layouts.app')

@php
$seo = [
    'title' => 'Done',
    'description' => 'Halaman Done.',
    'ogTitle' => 'Done',
    'ogDescription' => 'Halaman Done.',
    'ogImage' => asset('images/og-image.jpg'),
];
@endphp

@section('seo')
    <x-seo-meta :seo="$seo" />
@endsection

@section('content')
<section class="crud-shell">
    <h1 class="crud-title">Done</h1>
</section>
@endsection
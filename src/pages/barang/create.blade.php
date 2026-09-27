@extends('layouts.app')

@section('content')
<section class="crud-shell narrow-shell" data-reveal>
    <a class="back-link" href="{{ route('barang.index') }}" data-navigate="{{ route('barang.index') }}">← Kembali ke barang</a>
    <p class="eyebrow">Inventory / New</p>
    <h1 class="crud-title">Tambah barang</h1>
    <form class="barang-form" method="POST" action="{{ route('barang.store') }}">
        @include('pages.barang._form', ['submitLabel' => 'Simpan barang'])
    </form>
</section>
@endsection

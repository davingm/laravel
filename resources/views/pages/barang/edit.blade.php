@extends('layouts.app')

@section('content')
<section class="crud-shell narrow-shell" data-reveal>
    <a class="back-link" href="{{ route('barang.show', $barang) }}" data-navigate="{{ route('barang.show', $barang) }}">← Kembali ke detail</a>
    <p class="eyebrow">Inventory / Edit</p>
    <h1 class="crud-title">Edit barang</h1>
    <form class="barang-form" method="POST" action="{{ route('barang.update', $barang) }}">
        @method('PUT')
        @include('pages.barang._form', ['submitLabel' => 'Simpan perubahan'])
    </form>
</section>
@endsection

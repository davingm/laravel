@extends('layouts.app')

@section('content')
<section class="crud-shell narrow-shell" data-reveal>
    <div class="detail-topline">
        <a class="back-link" href="{{ route('barang.index') }}" data-navigate="{{ route('barang.index') }}">← Semua barang</a>
        <a class="button button-quiet" href="{{ route('barang.edit', $barang) }}" data-navigate="{{ route('barang.edit', $barang) }}">Edit</a>
    </div>
    @if (session('success')) <div class="flash-success">{{ session('success') }}</div> @endif
    <p class="eyebrow">{{ $barang->sku }}</p>
    <h1 class="crud-title">{{ $barang->nama }}</h1>
    <div class="detail-grid">
        <div><small>Harga</small><strong>Rp {{ number_format($barang->harga, 0, ',', '.') }}</strong></div>
        <div><small>Stok tersedia</small><strong>{{ $barang->stok }} unit</strong></div>
    </div>
    @if ($barang->deskripsi)
        <p class="detail-description">{{ $barang->deskripsi }}</p>
    @endif
    <form method="POST" action="{{ route('barang.destroy', $barang) }}" onsubmit="return confirm('Hapus barang ini?')">
        @csrf
        @method('DELETE')
        <button class="danger-link" type="submit">Hapus barang</button>
    </form>
</section>
@endsection

@extends('layouts.app')

@section('content')
<section class="crud-shell" data-reveal>
    <div class="crud-heading">
        <div>
            <p class="eyebrow">Inventory</p>
            <h1 class="crud-title">Barang</h1>
            <p class="lede">Kelola nama, harga, SKU, dan stok barang.</p>
        </div>
        <a class="button button-primary" href="{{ route('barang.create') }}" data-navigate="{{ route('barang.create') }}">+ Tambah barang</a>
    </div>

    @if (session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif

    <div class="table-wrap">
        <table class="barang-table">
            <thead><tr><th>Barang</th><th>SKU</th><th>Harga</th><th>Stok</th><th></th></tr></thead>
            <tbody>
                @forelse ($barangs as $barang)
                    <tr>
                        <td><a class="table-link" href="{{ route('barang.show', $barang) }}" data-navigate="{{ route('barang.show', $barang) }}">{{ $barang->nama }}</a></td>
                        <td><code>{{ $barang->sku }}</code></td>
                        <td>Rp {{ number_format($barang->harga, 0, ',', '.') }}</td>
                        <td><span class="stock-badge">{{ $barang->stok }}</span></td>
                        <td class="table-actions"><a href="{{ route('barang.edit', $barang) }}" data-navigate="{{ route('barang.edit', $barang) }}">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty-state">Belum ada barang. Tambahkan barang pertama.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $barangs->links() }}
</section>
@endsection

@extends('layouts.dashboard')

@section('title', 'Edit Pengajuan')
@section('body-class', 'dashboard-page developer-dashboard')
@section('dashboard-content')
<section class="crud-shell narrow-shell">
    <header class="crud-form-heading"><a href="{{ route('peminjaman.peminjamans.show', $peminjaman) }}" class="back-link">&larr; Kembali ke detail</a><h1 class="crud-title">Edit pengajuan</h1><p class="crud-description">Pengajuan yang menunggu persetujuan masih dapat diubah.</p></header>
    <form method="POST" action="{{ route('peminjaman.peminjamans.update', $peminjaman) }}" class="barang-form">
        @csrf @method('PUT')
        @if ($errors->any())<div class="form-error-summary" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <label class="crud-field">Peminjam<select name="user_id" required class="crud-input">@foreach ($users as $user)<option value="{{ $user->id }}" @selected(old('user_id', $peminjaman->user_id) == $user->id)>{{ $user->name }} ({{ $user->email }})</option>@endforeach</select></label>
        <fieldset class="loan-item-picker"><legend>Pilih alat dan jumlah</legend>@foreach ($alats as $alat)@php($existingItem = $peminjaman->items->firstWhere('alat_id', $alat->id))<label class="loan-item-option"><span><strong>{{ $alat->nama_alat }}</strong><small>{{ $alat->kategori->nama_kategori }} · tersedia {{ $alat->stok }}</small></span><input type="number" class="crud-input quantity-input" name="items[{{ $alat->id }}]" value="{{ old('items.'.$alat->id, $existingItem?->jumlah ?? 0) }}" min="0" max="{{ $alat->stok }}" aria-label="Jumlah {{ $alat->nama_alat }}"></label>@endforeach</fieldset>
        <div class="barang-form-grid"><label class="crud-field">Rencana tanggal kembali<input type="date" name="tanggal_kembali" min="{{ now()->toDateString() }}" value="{{ old('tanggal_kembali', $peminjaman->tanggal_kembali?->format('Y-m-d')) }}" class="crud-input" @if ((int) config('peminjaman.denda_per_hari', 1000) > 0) required @endif>
            @if ((int) config('peminjaman.denda_per_hari', 1000) > 0)<small>Wajib diisi. Denda Rp {{ number_format((int) config('peminjaman.denda_per_hari'), 0, ',', '.') }} per hari jika terlambat.</small>@else<small>Denda keterlambatan sedang dinonaktifkan.</small>@endif
        </label><label class="crud-field">Catatan<textarea name="catatan" rows="3" class="crud-input">{{ old('catatan', $peminjaman->catatan) }}</textarea></label></div>
        <div class="crud-form-actions"><a href="{{ route('peminjaman.peminjamans.show', $peminjaman) }}" class="button button-secondary">Batal</a><button class="button button-primary" type="submit">Simpan perubahan</button></div>
    </form>
</section>
@endsection

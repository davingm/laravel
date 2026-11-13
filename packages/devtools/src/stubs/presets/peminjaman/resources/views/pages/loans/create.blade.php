@extends('layouts.dashboard')

@section('title', 'Tambah Peminjaman')
@section('body-class', 'dashboard-page developer-dashboard')
@section('dashboard-content')
<section class="crud-shell narrow-shell">
    <header class="crud-form-heading"><a href="{{ route('peminjaman.peminjamans.index') }}" class="back-link">&larr; Kembali ke peminjaman</a><h1 class="crud-title">Buat pengajuan</h1><p class="crud-description">Pilih satu atau beberapa alat. Persetujuan akan memeriksa stok kembali.</p></header>
    <form method="POST" action="{{ route('peminjaman.peminjamans.store') }}" class="barang-form">
        @csrf
        @if ($errors->any())<div class="form-error-summary" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <label class="crud-field">Peminjam<select name="user_id" required class="crud-input"><option value="">Pilih peminjam</option>@foreach ($users as $user)<option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }} ({{ $user->email }})</option>@endforeach</select></label>
        <fieldset class="loan-item-picker"><legend>Pilih alat dan jumlah</legend>@foreach ($alats as $alat)<label class="loan-item-option"><span><strong>{{ $alat->nama_alat }}</strong><small>{{ $alat->kategori->nama_kategori }} · tersedia {{ $alat->stok }}</small></span><input type="number" class="crud-input quantity-input" name="items[{{ $alat->id }}]" value="{{ old('items.'.$alat->id, 0) }}" min="0" max="{{ $alat->stok }}" aria-label="Jumlah {{ $alat->nama_alat }}" @disabled($alat->stok < 1)></label>@endforeach</fieldset>
        <div class="barang-form-grid">
            <label class="crud-field">Rencana tanggal kembali<input type="date" name="tanggal_kembali" min="{{ now()->toDateString() }}" value="{{ old('tanggal_kembali') }}" class="crud-input"></label>
            <label class="crud-field">Catatan<textarea name="catatan" rows="3" class="crud-input">{{ old('catatan') }}</textarea></label>
        </div>
        <div class="crud-form-actions"><a href="{{ route('peminjaman.peminjamans.index') }}" class="button button-secondary">Batal</a><button class="button button-primary" type="submit">Simpan pengajuan</button></div>
    </form>
</section>
@endsection

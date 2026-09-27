@csrf
<div class="barang-form-grid">
    <label>
        <span>Nama barang</span>
        <input name="nama" value="{{ old('nama', $barang->nama ?? '') }}" required maxlength="255">
        @error('nama') <small class="form-error">{{ $message }}</small> @enderror
    </label>
    <label>
        <span>SKU</span>
        <input name="sku" value="{{ old('sku', $barang->sku ?? '') }}" required maxlength="100">
        @error('sku') <small class="form-error">{{ $message }}</small> @enderror
    </label>
    <label>
        <span>Harga</span>
        <input type="number" name="harga" value="{{ old('harga', $barang->harga ?? '') }}" min="0" step="0.01" required>
        @error('harga') <small class="form-error">{{ $message }}</small> @enderror
    </label>
    <label>
        <span>Stok</span>
        <input type="number" name="stok" value="{{ old('stok', $barang->stok ?? 0) }}" min="0" required>
        @error('stok') <small class="form-error">{{ $message }}</small> @enderror
    </label>
    <label class="field-wide">
        <span>Deskripsi</span>
        <textarea name="deskripsi" rows="5" maxlength="2000">{{ old('deskripsi', $barang->deskripsi ?? '') }}</textarea>
        @error('deskripsi') <small class="form-error">{{ $message }}</small> @enderror
    </label>
</div>
<button class="button button-primary" type="submit">{{ $submitLabel }}</button>
<a class="button button-quiet" href="{{ route('barang.index') }}" data-navigate="{{ route('barang.index') }}">Batal</a>

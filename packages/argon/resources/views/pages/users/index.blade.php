@extends('layouts.dashboard')

@section('title', 'Pengguna')
@section('body-class', 'dashboard-page developer-dashboard')
@section('dashboard-content')
<section class="crud-shell">
    <header class="crud-heading"><div><p class="eyebrow">Akses aplikasi</p><h1 class="crud-title">Pengguna</h1><p class="crud-description">Pilih baris untuk mengubah akun dan perannya.</p></div><a href="{{ route('peminjaman.users.create') }}" class="button button-primary">Tambah pengguna</a></header>
    @if (session('success'))<p class="flash-success" role="status">{{ session('success') }}</p>@endif
    @if (session('error'))<p class="flash-error" role="alert">{{ session('error') }}</p>@endif
    <div class="crud-summary"><span><strong class="crud-summary-count">{{ $users->total() }}</strong> pengguna</span><label class="crud-search-wrap"><span class="sr-only">Cari pengguna</span><input type="search" data-table-search placeholder="Cari pengguna…" class="crud-search"></label></div>
    <div class="table-wrap"><table class="barang-table"><thead><tr><th class="number-column">No.</th><th>Nama</th><th>Email</th><th>Peran</th><th class="actions-heading">Aksi</th></tr></thead><tbody data-table-body>
        @forelse ($users as $user)
            <tr data-table-row data-edit-url="{{ route('peminjaman.users.edit', $user) }}" tabindex="0"><td class="number-column">{{ $loop->iteration + (($users->currentPage() - 1) * $users->perPage()) }}</td><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ ['administrator' => 'Admin besar', 'operator' => 'Operator', 'user' => 'Peminjam'][$user->role] ?? $user->role }}</td><td class="actions-cell"><form method="POST" action="{{ route('peminjaman.users.destroy', $user) }}" data-confirm="Hapus akun {{ $user->name }}?">@csrf @method('DELETE')<button class="button button-delete button-small" type="submit">Hapus</button></form></td></tr>
        @empty <tr><td colspan="5" class="empty-state">Belum ada pengguna.</td></tr>@endforelse
        <tr data-table-no-results hidden><td colspan="5" class="empty-state">Tidak ada data yang cocok.</td></tr>
    </tbody></table></div>
    <div class="crud-pagination">{{ $users->links() }}</div>
</section>
@endsection

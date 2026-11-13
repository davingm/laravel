@extends('layouts.dashboard')

@section('title', 'Pengguna')
@section('body-class', 'dashboard-page')

@section('dashboard-content')
<section class="crud-shell">
    <header class="crud-heading">
        <div><h1 class="crud-title">Pengguna</h1><p class="crud-description">Kelola akun admin, operator, dan peminjam.</p></div>
        <a href="{{ route('peminjaman.users.create') }}" class="button button-primary">Tambah pengguna</a>
    </header>

    @if (session('success'))<p class="flash-success" role="status">{{ session('success') }}</p>@endif
    @if (session('error'))<p class="flash-error" role="alert">{{ session('error') }}</p>@endif

    <div class="crud-summary"><span><strong class="crud-summary-count">{{ $users->total() }}</strong> pengguna</span></div>
    <div class="table-wrap">
        <table class="barang-table">
            <thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th class="actions-heading">Aksi</th></tr></thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ ['administrator' => 'Admin besar', 'operator' => 'Petugas', 'user' => 'Peminjam'][$user->role] ?? $user->role }}</td>
                        <td class="actions-cell">
                            <div class="table-actions">
                                <a href="{{ route('peminjaman.users.edit', $user) }}" class="button button-secondary button-small">Edit</a>
                                <form method="POST" action="{{ route('peminjaman.users.destroy', $user) }}" data-confirm="Hapus akun {{ $user->name }}?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="button button-secondary button-small">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty-state">Belum ada pengguna.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="crud-pagination">{{ $users->links() }}</div>
</section>
@endsection

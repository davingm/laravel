@extends('layouts.dashboard')

@section('title', 'Users')

@section('dashboard-content')
<div class="dev-dashboard-content">
    <div class="crud-shell">
        <div class="crud-heading">
            <div>
                <p class="eyebrow">MANAGE</p>
                <h1 class="crud-title">Users</h1>
                <p class="crud-description">Daftar semua pengguna terdaftar.</p>
            </div>
        </div>

        @if (session('success'))
            <div class="flash-success">{{ session('success') }}</div>
        @endif

        <div class="crud-summary">
            <span class="crud-summary-count">{{ $users->total() }}</span>
            <span>pengguna terdaftar</span>
        </div>

        <div class="table-wrap">
            <table class="barang-table">
                <thead>
                    <tr>
                        <th>NAMA</th>
                        <th>EMAIL</th>
                        <th>ROLE</th>
                        <th>BERGABUNG</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <span style="display:inline-flex;align-items:center;gap:.5rem">
                                    <span style="display:grid;width:1.75rem;height:1.75rem;place-items:center;border-radius:50%;background:#1677ff;color:#fff;font-size:.6rem;font-weight:700;flex:0 0 1.75rem">
                                        {{ mb_strtoupper(mb_substr($user->name, 0, 2)) }}
                                    </span>
                                    {{ $user->name }}
                                </span>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span style="display:inline-flex;align-items:center;gap:.3rem;border-radius:2px;background:#f0f5ff;padding:.2rem .45rem;color:#1677ff;font-size:.6rem;font-weight:650">
                                    {{ ucfirst($user->role ?? 'user') }}
                                </span>
                            </td>
                            <td>{{ $user->created_at?->format('d M Y') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty-state">Belum ada pengguna.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="crud-pagination">{{ $users->links() }}</div>
    </div>
</div>
@endsection

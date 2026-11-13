<aside class="dashboard-sidebar">
    <a class="dashboard-brand" href="{{ route('dashboard') }}">Peminjaman Alat</a>

    <nav class="dashboard-nav" aria-label="Menu dashboard">
        @php
            $role = auth()->user()->role;
            $menu = [
                ['label' => 'Dashboard utama', 'route' => 'dashboard', 'active' => 'dashboard', 'roles' => ['administrator', 'operator', 'user']],
                ['label' => 'Ringkasan peminjaman', 'route' => 'peminjaman.dashboard', 'active' => 'peminjaman.dashboard', 'roles' => ['administrator', 'operator', 'user']],
                ['label' => 'Pengguna', 'route' => 'peminjaman.users.index', 'active' => 'peminjaman.users.*', 'roles' => ['administrator']],
                ['label' => 'Daftar alat', 'route' => 'peminjaman.alats.index', 'active' => 'peminjaman.alats.*', 'roles' => ['administrator', 'operator', 'user']],
                ['label' => 'Kategori alat', 'route' => 'peminjaman.kategoris.index', 'active' => 'peminjaman.kategoris.*', 'roles' => ['administrator']],
                ['label' => 'Peminjaman', 'route' => 'peminjaman.peminjamans.index', 'active' => 'peminjaman.peminjamans.*', 'roles' => ['administrator', 'operator', 'user']],
                ['label' => $role === 'operator' ? 'Pantau pengembalian' : 'Pengembalian', 'route' => 'peminjaman.pengembalians.index', 'active' => 'peminjaman.pengembalians.*', 'roles' => ['administrator', 'operator']],
            ];
        @endphp
        @foreach ($menu as $item)
            @if (in_array($role, $item['roles'], true))
            <a class="dashboard-nav-link {{ request()->routeIs($item['active']) ? 'is-active' : '' }}"
                href="{{ route($item['route']) }}"
                @if (request()->routeIs($item['active'])) aria-current="page" @endif>
                <svg viewBox="0 0 20 20" aria-hidden="true">
                    @switch($item['route'])
                        @case('dashboard')
                        @case('peminjaman.dashboard')
                            <path d="M2 2h6v6H2zm10 0h6v6h-6zM2 12h6v6H2zm10 0h6v6h-6z" />
                            @break
                        @case('peminjaman.users.index')
                            <path d="M10 10a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-6 7a6 6 0 0 1 12 0M15 5a3 3 0 0 1 0 6m1 2a5 5 0 0 1 2 4" />
                            @break
                        @case('peminjaman.alats.index')
                            <path d="m2 6 8-4 8 4v8l-8 4-8-4zM2 6l8 4 8-4M10 10v8" />
                            @break
                        @case('peminjaman.kategoris.index')
                            <path d="M2 3h7l9 7-8 8-8-9zM6 7h.01" />
                            @break
                        @case('peminjaman.peminjamans.index')
                        @case('peminjaman.pengembalians.index')
                            <path d="M3 5h14M3 10h14M3 15h14M6 3v4m8 1v4m-5 1v4" />
                            @break
                    @endswitch
                </svg>
                <span>{{ $item['label'] }}</span>
            </a>
            @endif
        @endforeach
    </nav>

    <div class="dashboard-collapse" aria-hidden="true">
        <svg viewBox="0 0 20 20"><path d="M3 4h14M3 10h8M3 16h14M14 8l3 2-3 2" /></svg>
    </div>
</aside>

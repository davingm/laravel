<aside class="dashboard-sidebar">
    <a class="dashboard-brand" href="{{ route('dashboard') }}">
        <span class="dashboard-brand-mark" aria-hidden="true">D</span>
        <span>{{ config('app.name', 'Laravel') }}</span>
    </a>

    <nav class="dashboard-nav" aria-label="Menu utama">
        <a class="dashboard-nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}"
            href="{{ route('dashboard') }}"
            @if (request()->routeIs('dashboard')) aria-current="page" @endif>
            <svg viewBox="0 0 20 20" aria-hidden="true">
                <path d="M2 2h6v6H2zm10 0h6v6h-6zM2 12h6v6H2zm10 0h6v6h-6z" />
            </svg>
            <span>Dashboard</span>
        </a>

        @if (\Illuminate\Support\Facades\Route::has('peminjaman.alats.index'))
            @php
                $role = auth()->user()->role;
                $peminjamanMenu = [
                    ['label' => 'Daftar alat', 'route' => 'peminjaman.alats.index', 'active' => 'peminjaman.alats.*', 'roles' => ['administrator', 'operator', 'user'], 'icon' => 'tools'],
                    ['label' => 'Kategori alat', 'route' => 'peminjaman.kategoris.index', 'active' => 'peminjaman.kategoris.*', 'roles' => ['administrator'], 'icon' => 'categories'],
                    ['label' => 'Pengguna', 'route' => 'peminjaman.users.index', 'active' => 'peminjaman.users.*', 'roles' => ['administrator'], 'icon' => 'users'],
                    ['label' => 'Peminjaman', 'route' => 'peminjaman.peminjamans.index', 'active' => 'peminjaman.peminjamans.*', 'roles' => ['administrator', 'operator', 'user'], 'icon' => 'loans'],
                    ['label' => 'Pengembalian', 'route' => 'peminjaman.pengembalians.index', 'active' => 'peminjaman.pengembalians.*', 'roles' => ['administrator', 'operator'], 'icon' => 'returns'],
                ];
            @endphp

            @foreach ($peminjamanMenu as $item)
                @if (in_array($role, $item['roles'], true))
                    <a class="dashboard-nav-link {{ request()->routeIs($item['active']) ? 'is-active' : '' }}"
                        href="{{ route($item['route']) }}"
                        @if (request()->routeIs($item['active'])) aria-current="page" @endif>
                        <svg viewBox="0 0 20 20" aria-hidden="true">
                            @switch($item['icon'])
                                @case('tools')
                                    <path d="M3 5h14v12H3zM6 2v6m8-6v6M6 11h8m-8 3h5" />
                                    @break
                                @case('categories')
                                    <path d="M2 4h6l2 2h8v11H2z" />
                                    @break
                                @case('users')
                                    <path d="M10 10a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-6 7a6 6 0 0 1 12 0M15 5a3 3 0 0 1 0 6m1 2a5 5 0 0 1 2 4" />
                                    @break
                                @default
                                    <path d="M3 5h14M3 10h14M3 15h14M6 3v4m8 1v4m-5 1v4" />
                            @endswitch
                        </svg>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endif
            @endforeach
        @endif
    </nav>
</aside>

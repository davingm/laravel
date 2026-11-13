<header class="dashboard-header">
    @php
        $roleLabel = ['administrator' => 'Admin besar', 'operator' => 'Petugas', 'user' => 'Peminjam'][auth()->user()->role] ?? auth()->user()->role;
    @endphp
    <a class="dashboard-cloud-brand" href="{{ route('peminjaman.dashboard') }}" aria-label="Dashboard peminjaman alat">
        <svg viewBox="0 0 40 32" aria-hidden="true">
            <path d="M12.2 26.5h17.4a7.1 7.1 0 0 0 .8-14.2A10.7 10.7 0 0 0 10 10a8.3 8.3 0 0 0 2.2 16.5Z" />
        </svg>
        <span>Peminjaman Alat</span>
    </a>

    <nav class="dashboard-utility-nav" aria-label="Menu akun">
        <details class="dashboard-profile-menu">
            <summary class="dashboard-avatar" aria-label="Buka menu profil">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4" /><path d="M4 21a8 8 0 0 1 16 0" /></svg>
            </summary>
            <div class="dashboard-profile-dropdown">
                <span class="dashboard-profile-name">{{ auth()->user()->name }}</span>
                <a href="{{ route('dashboard') }}">Dashboard utama</a>
                <a href="{{ route('peminjaman.dashboard') }}">Ringkasan peminjaman</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Keluar</button>
                </form>
            </div>
        </details>
        <span class="dashboard-registration">{{ $roleLabel }}</span>
    </nav>
</header>

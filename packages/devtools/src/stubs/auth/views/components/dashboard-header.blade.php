<header class="dashboard-header">
    <a class="dashboard-cloud-brand" href="{{ route('pages.dashboard') }}" aria-label="Tencent Cloud">
        <img src="{{ Vite::asset('src/assets/icons/tencent-cloud.svg') }}" alt="">
        <span>Vuxilabs Cloud</span>
    </a>

    <nav class="dashboard-top-nav" aria-label="Navigasi utama">
        <a class="is-current" href="{{ route('pages.dashboard') }}">Overview</a>
        <a href="{{ route('pages.dashboard') }}#infrastructure">Cloud Products</a>
        <a class="dashboard-add-link" href="{{ route('pages.dashboard') }}#projects" aria-label="Tambah proyek">
            <img src="{{ Vite::asset('src/assets/icons/plus.svg') }}" alt="">
        </a>
    </nav>

    <nav class="dashboard-utility-nav" aria-label="Menu bantuan dan akun">
        <a class="dashboard-header-icon" href="{{ route('pages.dashboard') }}#activity" aria-label="Pesan">
            <img src="{{ Vite::asset('src/assets/icons/envelope.svg') }}" alt="">
        </a>
        <a class="dashboard-header-icon" href="{{ route('pages.dashboard') }}#infrastructure" aria-label="Wilayah dan bahasa">
            <img src="{{ Vite::asset('src/assets/icons/globe-alt.svg') }}" alt="">
        </a>
        <a class="dashboard-utility-link" href="{{ route('pages.dashboard') }}#infrastructure">Tools</a>
        <a class="dashboard-utility-link" href="{{ route('pages.dashboard') }}#team">Tickets</a>
        <a class="dashboard-utility-link" href="{{ route('pages.dashboard') }}#analytics">Billing</a>
        <details class="dashboard-profile-menu">
            <summary class="dashboard-avatar" aria-label="Buka menu profil">
                <img src="{{ Vite::asset('src/assets/icons/user-circle.svg') }}" alt="">
            </summary>
            <div class="dashboard-profile-dropdown">
                <span class="dashboard-profile-name">{{ auth()->user()->name }}</span>
                <a href="{{ route('pages.dashboard.profile') }}">Profile settings</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Sign out</button>
                </form>
            </div>
        </details>
        <a class="dashboard-registration" href="{{ route('pages.dashboard') }}">Registration completed.</a>
    </nav>
</header>

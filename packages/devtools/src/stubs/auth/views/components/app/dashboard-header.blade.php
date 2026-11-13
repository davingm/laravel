<header class="dashboard-header">
    <a class="dashboard-cloud-brand" href="{{ route('dashboard') }}" aria-label="Tencent Cloud">
        <svg viewBox="0 0 40 32" aria-hidden="true">
            <path d="M12.2 26.5h17.4a7.1 7.1 0 0 0 .8-14.2A10.7 10.7 0 0 0 10 10a8.3 8.3 0 0 0 2.2 16.5Z" />
        </svg>
        <span>Vuxilabs Cloud</span>
    </a>

    <nav class="dashboard-top-nav" aria-label="Navigasi utama">
        <a class="is-current" href="{{ route('dashboard') }}">Overview</a>
        <a href="{{ route('dashboard') }}#infrastructure">Cloud Products</a>
        <a class="dashboard-add-link" href="{{ route('dashboard') }}#projects" aria-label="Tambah proyek">
            <img src="{{ asset('icons/plus.svg') }}" alt="">
        </a>
    </nav>

    <nav class="dashboard-utility-nav" aria-label="Menu bantuan dan akun">
        <a class="dashboard-header-icon" href="{{ route('dashboard') }}#activity" aria-label="Pesan">
            <img src="{{ asset('icons/envelope.svg') }}" alt="">
        </a>
        <a class="dashboard-header-icon" href="{{ route('dashboard') }}#infrastructure" aria-label="Wilayah dan bahasa">
            <img src="{{ asset('icons/globe-alt.svg') }}" alt="">
        </a>
        <a class="dashboard-utility-link" href="{{ route('dashboard') }}#infrastructure">Tools</a>
        <a class="dashboard-utility-link" href="{{ route('dashboard') }}#team">Tickets</a>
        <a class="dashboard-utility-link" href="{{ route('dashboard') }}#analytics">Billing</a>
        <details class="dashboard-profile-menu">
            <summary class="dashboard-avatar" aria-label="Buka menu profil">
                <img src="https://cdn.davingm.com/author.webp" alt="">
            </summary>
            <div class="dashboard-profile-dropdown">
                <span class="dashboard-profile-name">{{ auth()->user()->name }}</span>
                <a href="{{ route('profile.edit') }}">Profile settings</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Sign out</button>
                </form>
            </div>
        </details>
        <a class="dashboard-registration" href="{{ route('dashboard') }}">Registration completed.</a>
    </nav>
</header>

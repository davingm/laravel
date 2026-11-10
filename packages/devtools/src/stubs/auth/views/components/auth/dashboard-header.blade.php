<header class="dashboard-header">
    <a class="dashboard-cloud-brand" href="{{ route('dashboard') }}" aria-label="{{ config('app.name') }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/></svg>
        <span>{{ config('app.name') }}</span>
    </a>

    <nav class="dashboard-top-nav" aria-label="Navigasi utama">
        <a class="{{ request()->routeIs('dashboard') ? 'is-current' : '' }}" href="{{ route('dashboard') }}">Overview</a>
        <a href="{{ route('dashboard') }}">Aplikasi</a>
    </nav>

    <nav class="dashboard-utility-nav" aria-label="Menu bantuan dan akun">
        <a class="dashboard-utility-link" href="{{ route('dashboard') }}">Tools</a>
        <details class="dashboard-profile-menu">
            <summary class="dashboard-avatar" aria-label="Buka menu profil">
                <span>{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}</span>
                <svg viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
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
    </nav>
</header>

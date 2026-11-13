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
    </nav>
</aside>

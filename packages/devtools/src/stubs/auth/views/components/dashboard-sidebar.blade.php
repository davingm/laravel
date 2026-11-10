<aside class="dashboard-sidebar">
    <button class="workspace-switcher" type="button">
        <span class="workspace-avatar">FTs</span>
        <span class="workspace-copy"><strong>PT NLFTs ltd</strong><small>Corporate workspace</small></span>
        <img src="{{ Vite::asset('src/assets/icons/chevron-down.svg') }}" alt="">
    </button>

    <nav class="dashboard-nav" aria-label="Navigasi workspace">
        <span class="dashboard-nav-label">WORKSPACE</span>
        <a class="dashboard-nav-link {{ request()->routeIs('pages.dashboard') ? 'is-active' : '' }}" href="{{ route('pages.dashboard') }}" @if (request()->routeIs('pages.dashboard')) aria-current="page" @endif>
            <img src="{{ Vite::asset('src/assets/icons/squares-2x2.svg') }}" alt=""><span>Overview</span>
        </a>

        <span class="dashboard-nav-label">PLAN & BUILD</span>
        <a class="dashboard-nav-link" href="{{ route('pages.dashboard') }}#projects"><img src="{{ Vite::asset('src/assets/icons/folder.svg') }}" alt=""><span>Projects</span><span class="nav-count">8</span></a>
        <a class="dashboard-nav-link" href="{{ route('pages.dashboard') }}#projects"><img src="{{ Vite::asset('src/assets/icons/check-circle.svg') }}" alt=""><span>Tasks & sprints</span><span class="nav-count">12</span></a>
        <a class="dashboard-nav-link" href="{{ route('pages.dashboard') }}#projects"><img src="{{ Vite::asset('src/assets/icons/chart-bar.svg') }}" alt=""><span>Roadmap</span></a>

        <span class="dashboard-nav-label">TEAM</span>
        <a class="dashboard-nav-link" href="{{ route('pages.dashboard') }}#team"><img src="{{ Vite::asset('src/assets/icons/users.svg') }}" alt=""><span>Members</span><span class="nav-count">12</span></a>
        <a class="dashboard-nav-link" href="{{ route('pages.dashboard') }}#deployments"><img src="{{ Vite::asset('src/assets/icons/bell.svg') }}" alt=""><span>Team activity</span></a>

        <span class="dashboard-nav-label">DEVELOPMENT</span>
        <a class="dashboard-nav-link" href="{{ route('pages.dashboard') }}#repositories"><img src="{{ Vite::asset('src/assets/icons/code-bracket.svg') }}" alt=""><span>Repositories</span><span class="nav-count">6</span></a>
        <a class="dashboard-nav-link" href="{{ route('pages.dashboard') }}#repositories"><img src="{{ Vite::asset('src/assets/icons/arrows-right-left.svg') }}" alt=""><span>Pull requests</span><span class="nav-count nav-count-alert">4</span></a>
        <a class="dashboard-nav-link" href="{{ route('pages.dashboard') }}#pipelines"><img src="{{ Vite::asset('src/assets/icons/command-line.svg') }}" alt=""><span>CI / CD pipelines</span></a>
        <a class="dashboard-nav-link" href="{{ route('pages.dashboard') }}#deployments"><img src="{{ Vite::asset('src/assets/icons/rocket-launch.svg') }}" alt=""><span>Deployments</span></a>

        <span class="dashboard-nav-label">INFRASTRUCTURE</span>
        <a class="dashboard-nav-link" href="{{ route('pages.dashboard') }}#infrastructure"><img src="{{ Vite::asset('src/assets/icons/server-stack.svg') }}" alt=""><span>Servers</span></a>
        <a class="dashboard-nav-link" href="{{ route('pages.dashboard') }}#infrastructure"><img src="{{ Vite::asset('src/assets/icons/globe-alt.svg') }}" alt=""><span>Web apps & domains</span></a>
        <a class="dashboard-nav-link" href="{{ route('pages.dashboard') }}#infrastructure"><img src="{{ Vite::asset('src/assets/icons/chart-bar.svg') }}" alt=""><span>Monitoring</span></a>

        <span class="dashboard-nav-label">MANAGE</span>
        <a class="dashboard-nav-link" href="{{ route('pages.dashboard') }}#analytics"><img src="{{ Vite::asset('src/assets/icons/chart-bar.svg') }}" alt=""><span>Analytics</span></a>
        <a class="dashboard-nav-link" href="{{ route('pages.dashboard') }}#settings"><img src="{{ Vite::asset('src/assets/icons/cog-6-tooth.svg') }}" alt=""><span>Settings</span></a>
    </nav>

    <div class="sidebar-help">
        <span class="sidebar-help-icon">?</span>
        <span><strong>Need a hand?</strong><small>Visit the developer docs</small></span>
        <img src="{{ Vite::asset('src/assets/icons/chevron-down.svg') }}" alt="">
    </div>
</aside>

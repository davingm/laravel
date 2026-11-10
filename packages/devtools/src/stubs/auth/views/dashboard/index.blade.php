@extends('layouts.dashboard')

@section('title', 'Developer workspace')
@section('body-class', 'dashboard-page developer-dashboard')

@section('dashboard-content')
<div class="dev-dashboard-content">
    <section class="dashboard-welcome">
        <div>
            <h1>Selamat datang, Endministrator </h1>
            <p>Perlu kami siapkan secangkir kopi hangat untuk anda?.</p>
        </div>
        <a class="dashboard-primary-button" href="{{ route('pages.dashboard') }}#projects"><img src="{{ Vite::asset('src/assets/icons/plus.svg') }}" alt=""> Buat proyek</a>
    </section>

    <div class="dashboard-content-grid">
        <section class="dashboard-panel pipeline-panel" id="pipelines">
            <header class="dashboard-panel-heading">
                <div><h2>CI / CD PIPELINE</h2><p>Build dan deployment · 7 hari terakhir</p></div>
                <span class="pipeline-success-rate"><img src="{{ Vite::asset('src/assets/icons/check-circle.svg') }}" alt=""> 98.6% berhasil</span>
            </header>
            <div class="pipeline-chart" aria-label="Grafik build harian"><i style="height: 54%"></i><i style="height: 72%"></i><i style="height: 45%"></i><i style="height: 88%"></i><i style="height: 66%"></i><i style="height: 96%"></i><i style="height: 78%"></i><i style="height: 61%"></i><i style="height: 85%"></i><i style="height: 70%"></i><i style="height: 92%"></i><i style="height: 74%"></i><i style="height: 100%"></i><i style="height: 82%"></i><i style="height: 64%"></i><i style="height: 91%"></i><i style="height: 73%"></i><i style="height: 85%"></i><i style="height: 58%"></i><i style="height: 96%"></i><i style="height: 76%"></i><i style="height: 89%"></i><i style="height: 68%"></i><i style="height: 94%"></i></div>
            <div class="pipeline-legend"><span>2.386 build berhasil</span><span>32 gagal</span><span>Rata-rata 2m 14s</span></div>
        </section>

        <section class="dashboard-panel activity-heatmap-panel" id="activity">
            <header class="dashboard-panel-heading">
                <div><h2>Activity</h2><p>Aktivitas kontribusi workspace selama 12 bulan</p></div>
                <strong class="activity-total">186 <span>aktivitas</span></strong>
            </header>
            <div class="activity-chart-scroll">
                @php($activityMonths = [0 => 'Nov', 4 => 'Dec', 8 => 'Jan', 13 => 'Feb', 17 => 'Mar', 21 => 'Apr', 26 => 'May', 30 => 'Jun', 34 => 'Jul', 39 => 'Aug', 43 => 'Sep', 47 => 'Oct'])
                <div class="activity-months" aria-hidden="true">
                    @for ($week = 0; $week < 52; $week++)
                        <span>{{ $activityMonths[$week] ?? '' }}</span>
                    @endfor
                </div>
                <div class="activity-chart-body">
                    <div class="activity-weekdays" aria-hidden="true"><span></span><span>Mon</span><span></span><span>Wed</span><span></span><span>Fri</span><span></span></div>
                    <div class="activity-grid" role="img" aria-label="Grafik aktivitas harian selama 52 minggu">
                        @for ($week = 0; $week < 52; $week++)
                            @for ($day = 0; $day < 7; $day++)
                                @php($activitySeed = ($week * 13 + $day * 17 + ($week * $day) % 11) % 23)
                                @php($activityLevel = $activitySeed < 8 ? 0 : ($activitySeed < 13 ? 1 : ($activitySeed < 18 ? 2 : ($activitySeed < 21 ? 3 : 4))))
                                <span class="activity-cell activity-level-{{ $activityLevel }}" title="{{ $activityLevel * 2 + ($activityLevel > 0 ? $activitySeed % 3 : 0) }} aktivitas"></span>
                            @endfor
                        @endfor
                    </div>
                </div>
            </div>
            <footer class="activity-legend"><span>Aktivitas harian</span><span class="activity-legend-scale">Sedikit <i class="activity-level-0"></i><i class="activity-level-1"></i><i class="activity-level-2"></i><i class="activity-level-3"></i><i class="activity-level-4"></i> Banyak</span></footer>
        </section>

        <section class="dashboard-panel repositories-panel" id="repositories">
            <header class="dashboard-panel-heading">
                <div><h2>REPOSITORY</h2><p>Repository yang terakhir diperbarui</p></div>
                <a href="{{ route('pages.dashboard') }}#repositories" class="panel-link">Semua repository <span>→</span></a>
            </header>
            <div class="repository-list">
                <div class="repository-item"><span class="provider-icon"><img src="{{ Vite::asset('src/assets/icons/simple-github.svg') }}" alt="GitHub"></span><span class="repository-copy"><strong>sinar-nusantara/commerce</strong><small>Laravel · diperbarui 12 menit lalu</small></span><span class="repo-branch"><img src="{{ Vite::asset('src/assets/icons/arrows-right-left.svg') }}" alt=""> main</span></div>
                <div class="repository-item"><span class="provider-icon"><img src="{{ Vite::asset('src/assets/icons/simple-github.svg') }}" alt="GitHub"></span><span class="repository-copy"><strong>sinar-nusantara/core-api</strong><small>Node.js · diperbarui 1 jam lalu</small></span><span class="repo-branch"><img src="{{ Vite::asset('src/assets/icons/arrows-right-left.svg') }}" alt=""> main</span></div>
                <div class="repository-item"><span class="provider-icon"><img src="{{ Vite::asset('src/assets/icons/simple-github.svg') }}" alt="GitHub"></span><span class="repository-copy"><strong>sinar-nusantara/mobile</strong><small>Flutter · diperbarui 3 jam lalu</small></span><span class="repo-branch"><img src="{{ Vite::asset('src/assets/icons/arrows-right-left.svg') }}" alt=""> develop</span></div>
            </div>
        </section>

        <section class="developer-metrics" aria-label="Ringkasan workspace">
            <article class="developer-metric"><span>Proyek aktif</span><strong>08 <small>+2 bulan ini</small></strong></article>
            <article class="developer-metric"><span>Deployment bulan ini</span><strong>124 <small>+18.4%</small></strong></article>
            <article class="developer-metric"><span>Build berhasil</span><strong>98.6<small>%</small></strong></article>
            <article class="developer-metric"><span>Uptime server</span><strong>99.98<small>%</small></strong></article>
        </section>

        <section class="dashboard-panel projects-panel" id="projects">
            <header class="dashboard-panel-heading">
                <div><h2>Proyek</h2><p>Aktivitas terbaru dari workspace Anda</p></div>
                <a href="{{ route('pages.dashboard') }}#projects" class="panel-link">Lihat semua <span>→</span></a>
            </header>
            <div class="projects-table-wrap">
                <table class="projects-table">
                    <thead><tr><th>NAMA PROYEK</th><th>STATUS</th><th>PROGRESS</th><th>ANGGOTA</th><th>UPDATE TERAKHIR</th></tr></thead>
                    <tbody>
                        <tr><td><span class="project-avatar">S</span><span class="project-name">Sinar Commerce<small>e-commerce-platform</small></span></td><td><span class="project-status status-live"><i></i> Aktif</span></td><td><span class="project-progress"><span style="width: 76%"></span></span><small>76%</small></td><td><span class="avatar-stack"><i>AR</i><i>DN</i><i>+3</i></span></td><td class="project-updated">12 menit lalu</td></tr>
                        <tr><td><span class="project-avatar">A</span><span class="project-name">API Gateway<small>core-api-service</small></span></td><td><span class="project-status status-review"><i></i> Review</span></td><td><span class="project-progress"><span style="width: 48%"></span></span><small>48%</small></td><td><span class="avatar-stack"><i>BS</i><i>ML</i><i>+2</i></span></td><td class="project-updated">1 jam lalu</td></tr>
                        <tr><td><span class="project-avatar">M</span><span class="project-name">Mobile App<small>customer-mobile-app</small></span></td><td><span class="project-status status-live"><i></i> Aktif</span></td><td><span class="project-progress"><span style="width: 92%"></span></span><small>92%</small></td><td><span class="avatar-stack"><i>RA</i><i>FK</i><i>+4</i></span></td><td class="project-updated">3 jam lalu</td></tr>
                        <tr><td><span class="project-avatar">D</span><span class="project-name">Developer Portal<small>internal-dev-portal</small></span></td><td><span class="project-status status-paused"><i></i> Direncanakan</span></td><td><span class="project-progress"><span style="width: 24%"></span></span><small>24%</small></td><td><span class="avatar-stack"><i>DN</i><i>+2</i></span></td><td class="project-updated">Kemarin</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="project-table-footer"><span>Menampilkan 4 dari 8 proyek</span><div><button type="button" aria-label="Halaman sebelumnya">←</button><button type="button" aria-label="Halaman selanjutnya">→</button></div></div>
        </section>

        <section class="dashboard-panel activity-panel" id="deployments">
            <header class="dashboard-panel-heading"><div><h2>Deployment terbaru</h2><p>Aktivitas rilis di semua proyek</p></div><a href="{{ route('pages.dashboard') }}#deployments" class="panel-link">Semua <span>→</span></a></header>
            <div class="deployment-list">
                <article class="deployment-item"><span class="deployment-icon"><img src="{{ Vite::asset('src/assets/icons/rocket-launch.svg') }}" alt=""></span><div class="deployment-copy"><strong>Sinar Commerce <span class="environment-tag">PRODUCTION</span></strong><p><span class="deployment-commit">main</span> · fix: checkout validation</p><small>oleh Andi Rahman · 8 menit lalu</small></div><span class="deployment-check"><img src="{{ Vite::asset('src/assets/icons/check-circle.svg') }}" alt=""></span></article>
                <article class="deployment-item"><span class="deployment-icon"><img src="{{ Vite::asset('src/assets/icons/rocket-launch.svg') }}" alt=""></span><div class="deployment-copy"><strong>API Gateway <span class="environment-tag">STAGING</span></strong><p><span class="deployment-commit">feat/rate-limit</span> · add request throttling</p><small>oleh Budi Santoso · 42 menit lalu</small></div><span class="deployment-check"><img src="{{ Vite::asset('src/assets/icons/check-circle.svg') }}" alt=""></span></article>
                <article class="deployment-item"><span class="deployment-icon"><img src="{{ Vite::asset('src/assets/icons/command-line.svg') }}" alt=""></span><div class="deployment-copy"><strong>Mobile App <span class="environment-tag">PREVIEW</span></strong><p><span class="deployment-commit">feat/new-onboarding</span> · update onboarding flow</p><small>oleh Rina Ayu · 2 jam lalu</small></div><span class="deployment-running">Building</span></article>
            </div>
        </section>

            <section class="dashboard-panel team-panel" id="team">
            <header class="dashboard-panel-heading">
                <div>
                <h2>Tim Anda</h2>
                <p>12 anggota di 4 tim</p>
                </div>
                <a href="{{ route('pages.dashboard') }}#team" class="panel-link">Kelola <span>→</span></a>
            </header>

            <div class="team-members">
                <!-- Andi Rahman -->
                <div class="team-member">
                <img src="https://github.com/davingm.png" alt="Andi Rahman" class="team-avatar" />
                <span class="team-member-copy">
                    <strong>davingm</strong>
                    <small>Endministrator</small>
                </span>
                <span class="member-presence presence-online"></span>
                </div>

                <!-- Dina Nurhaliza -->
                <div class="team-member">
                <img src="https://github.com/nairha.png" alt="Dina Nurhaliza" class="team-avatar" />
                <span class="team-member-copy">
                    <strong>Nairha</strong>
                    <small>Maintainer</small>
                </span>
                <span class="member-presence presence-online"></span>
                </div>

                <!-- Budi Santoso -->
                <div class="team-member">
                <img src="https://github.com/tokitakun.png" alt="Budi Santoso" class="team-avatar" />
                <span class="team-member-copy">
                    <strong>Nafis</strong>
                    <small>Backend engineer</small>
                </span>
                <span class="member-presence presence-away"></span>
                </div>
            </div>

            <a class="team-invite-link" href="{{ route('pages.dashboard') }}#team">
                <img src="{{ Vite::asset('src/assets/icons/plus.svg') }}" alt=""> Undang anggota tim
            </a>
            </section>


        <section class="dashboard-panel infrastructure-panel" id="infrastructure">
            <header class="dashboard-panel-heading"><div><h2>Infrastruktur</h2><p>Status layanan dan server</p></div><a href="{{ route('pages.dashboard') }}#infrastructure" class="panel-link">Detail <span>→</span></a></header>
            <div class="infrastructure-status"><span class="infrastructure-status-icon"><img src="{{ Vite::asset('src/assets/icons/server-stack.svg') }}" alt=""></span><div><strong>Production cluster</strong><small>Jakarta · 3 nodes</small></div><span class="infra-operational"><i></i> Normal</span></div>
            <div class="infrastructure-status"><span class="infrastructure-status-icon"><img src="{{ Vite::asset('src/assets/icons/globe-alt.svg') }}" alt=""></span><div><strong>sinar-nusantara.id</strong><small>Web app · SSL aktif</small></div><span class="infra-operational"><i></i> Online</span></div>
            <div class="server-load"><span>Penggunaan CPU <strong>34%</strong></span><div><i style="width: 34%"></i></div></div>
        </section>
    </div>
    <span id="analytics"></span><span id="settings"></span><span id="help"></span>
</div>
@endsection

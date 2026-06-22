@php
    $headerUser = auth()->user();
    $initials = collect(explode(' ', $headerUser?->name ?? 'PPMU'))
        ->take(2)
        ->map(fn ($word) => strtoupper(substr($word, 0, 1)))
        ->join('');
@endphp

<header class="ppmf-header">
    <button class="header-toggle" id="sidebarToggle" type="button" title="Expand or collapse sidebar" aria-label="Expand or collapse sidebar">
        <i class="bi bi-list"></i>
    </button>

    <a href="{{ route('dashboard') }}" class="header-brand" title="PPMU Dashboard">
        <img src="{{ asset('images/pmru-logo.png') }}" alt="PPMU Logo" class="portal-logo header-logo">
        <div class="brand-text">
            <div class="brand-title">Punjab Performance Management Unit</div>
            <div class="brand-sub">Government of the Punjab</div>
        </div>
    </a>

    <div class="header-spacer"></div>

    <div class="header-search">
        <i class="bi bi-search search-icon"></i>
        <input type="text" placeholder="Search KPIs, locations, reports..." aria-label="Search">
    </div>

    <div class="header-actions">
        <button class="header-btn" type="button" title="Notifications"><i class="bi bi-bell"></i></button>
        <button class="header-btn" type="button" title="Refresh data" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i></button>
    </div>

    <div class="dropdown-ppmf">
        <a class="header-user" href="#" data-dropdown="userMenu">
            <div class="header-user-avatar">{{ $initials }}</div>
            <div>
                <div class="header-user-name">{{ $headerUser?->name }}</div>
                <div class="header-user-role">{{ $headerUser?->role?->name }}</div>
            </div>
            <i class="bi bi-chevron-down" style="font-size:11px;color:var(--text-muted);margin-left:2px"></i>
        </a>

        <div class="dropdown-menu-ppmf" id="userMenu">
            <a href="{{ route('settings.change-password') }}"><i class="bi bi-key"></i> Change Password</a>
            <div class="divider"></div>
            <a href="{{ route('logout') }}" style="color:var(--danger)"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>
</header>

@php $roleSlug = auth()->user()?->role?->slug; $isAdmin = $roleSlug === 'super_admin'; $mainNavLabel = request()->routeIs('kpi.dashboard', 'kpi.dashboard.data') ? 'Dashboard' : 'Home'; @endphp
<aside class="ppmf-sidebar" id="ppmfSidebar">
  <a href="{{ route('dashboard') }}" class="sidebar-brand">
    <div class="sidebar-brand-icon"><img src="{{ asset('images/pmru-logo.png') }}" alt="PPMU Logo" class="portal-logo sidebar-logo"></div>
    <div class="sidebar-brand-text">
      <div class="sidebar-brand-title">PPMU</div>
      <div class="sidebar-brand-sub">Punjab Performance Management Unit</div>
    </div>
  </a>
  <div class="sidebar-nav-wrap">
    <div class="nav-group-label">Main</div>
    <div class="nav-item">
      <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard','dashboard.data','kpi.dashboard','kpi.dashboard.data') ? 'active' : '' }}" title="{{ $mainNavLabel }}">
        <i class="bi bi-house-door-fill"></i><span class="nav-text">{{ $mainNavLabel }}</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="{{ route('inspections.index') }}" class="nav-link {{ request()->routeIs('inspections.*', 'kpi.inspections.*') ? 'active' : '' }}" title="Inspections">
        <i class="bi bi-clipboard2-check-fill"></i><span class="nav-text">Inspections</span>
      </a>
    </div>
    @if($isAdmin)
    <div class="nav-group-label">Administration</div>
    <div class="nav-item">
      <a href="{{ route('manage-kpis.index') }}" class="nav-link {{ request()->routeIs('manage-kpis.*') ? 'active' : '' }}" title="Manage KPIs">
        <i class="bi bi-sliders"></i><span class="nav-text">Manage KPIs</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" title="Users">
        <i class="bi bi-people"></i><span class="nav-text">Users</span>
      </a>
    </div>
    @endif
  </div>
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="sidebar-user-avatar"><i class="bi bi-person-fill"></i></div>
      <div class="sidebar-user-meta">
        <div class="sidebar-user-name">{{ auth()->user()?->name }}</div>
        <div class="sidebar-user-role">{{ auth()->user()?->role?->name }}</div>
      </div>
      <a href="{{ route('logout') }}" class="sidebar-user-logout" title="Logout"><i class="bi bi-box-arrow-right"></i></a>
    </div>
  </div>
</aside>

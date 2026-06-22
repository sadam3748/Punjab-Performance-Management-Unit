@php $roleSlug = auth()->user()?->role?->slug; $isAdmin = $roleSlug === 'super_admin'; $canReview = !in_array($roleSlug, ['ac','field_user']); @endphp
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
      <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard','kpi.dashboard') ? 'active' : '' }}" title="Dashboard">
        <i class="bi bi-grid-1x2-fill"></i><span class="nav-text">Dashboard</span>
      </a>
    </div>
    @if($canReview)
    <div class="nav-item">
      <a href="{{ route('kpi-submissions.index') }}" class="nav-link {{ request()->routeIs('kpi-submissions.*','submissions.*') ? 'active' : '' }}" title="KPI Submissions">
        <i class="bi bi-clipboard2-check"></i><span class="nav-text">KPI Submissions</span>
      </a>
    </div>
    @endif
    <div class="nav-item">
      <a href="{{ route('reports') }}" class="nav-link {{ request()->routeIs('reports') ? 'active' : '' }}" title="Reports">
        <i class="bi bi-file-earmark-bar-graph"></i><span class="nav-text">Reports</span>
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
    <div class="nav-item">
      <a href="{{ route('settings.change-password') }}" class="nav-link {{ request()->routeIs('settings.change-password*') ? 'active' : '' }}" title="Change Password">
        <i class="bi bi-key"></i><span class="nav-text">Change Password</span>
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

<aside class="ppmf-sidebar" id="ppmfSidebar">

  {{-- Brand --}}
  <a href="{{ route('dashboard') }}" class="sidebar-brand">
    <div class="sidebar-brand-icon">
      <i class="bi bi-activity"></i>
    </div>
    <div class="sidebar-brand-text">
      <div class="sidebar-brand-title">PPMF Portal</div>
      <div class="sidebar-brand-sub">Punjab Govt. · 2024–25</div>
    </div>
  </a>

  {{-- Navigation --}}
  <div class="sidebar-nav-wrap">

    {{-- Overview --}}
    <div class="nav-group-label">Overview</div>
    <div class="nav-item">
      <a href="{{ route('dashboard') }}" class="nav-link {{ Request::is('dashboard*') ? 'active' : '' }}">
        <i class="bi bi-speedometer2"></i> Dashboard
      </a>
    </div>
    <div class="nav-item">
      <a href="{{ route('scorecard.index') }}" class="nav-link {{ Request::is('scorecard*') ? 'active' : '' }}">
        <i class="bi bi-award"></i> CM Governance Scorecard
      </a>
    </div>

    {{-- KPI Management --}}
    <div class="nav-group-label">KPI Management</div>
    <div class="nav-item">
      <a href="{{ route('kpi.index') }}" class="nav-link {{ Request::is('kpi') ? 'active' : '' }}">
        <i class="bi bi-graph-up-arrow"></i> KPI Management
      </a>
    </div>
    <div class="nav-item">
      <a href="{{ route('kpi.create') }}" class="nav-link {{ Request::is('kpi/create') ? 'active' : '' }}">
        <i class="bi bi-pencil-square"></i> KPI Data Entry
      </a>
    </div>

    {{-- Performance --}}
    <div class="nav-group-label">Performance</div>
    <div class="nav-item">
      <a href="{{ route('divisions.performance') }}" class="nav-link {{ Request::is('divisions*') ? 'active' : '' }}">
        <i class="bi bi-diagram-3"></i> Division Performance
      </a>
    </div>
    <div class="nav-item">
      <a href="{{ route('districts.performance') }}" class="nav-link {{ Request::is('districts*') ? 'active' : '' }}">
        <i class="bi bi-pin-map"></i> District Performance
      </a>
    </div>
    <div class="nav-item">
      <a href="{{ route('scorecard.tier') }}" class="nav-link {{ Request::is('scorecard/tier*') ? 'active' : '' }}">
        <i class="bi bi-layers"></i> Tier Wise Ranking
        <span class="badge-pill">NEW</span>
      </a>
    </div>

    {{-- Reports --}}
    <div class="nav-group-label">Reports</div>
    <div class="nav-item">
      <a href="{{ route('departments.index') }}" class="nav-link {{ Request::is('departments*') ? 'active' : '' }}">
        <i class="bi bi-building"></i> Department Reports
      </a>
    </div>
    <div class="nav-item">
      <a href="{{ route('map.index') }}" class="nav-link {{ Request::is('map*') ? 'active' : '' }}">
        <i class="bi bi-map"></i> Punjab Map View
      </a>
    </div>
    <div class="nav-item">
      <a href="{{ route('reports.index') }}" class="nav-link {{ Request::is('reports*') ? 'active' : '' }}">
        <i class="bi bi-file-earmark-bar-graph"></i> Reports & Analytics
      </a>
    </div>

    {{-- Admin --}}
    <div class="nav-group-label">Administration</div>
    <div class="nav-item">
      <a href="{{ route('users.index') }}" class="nav-link {{ Request::is('users*') ? 'active' : '' }}">
        <i class="bi bi-people"></i> User Management
      </a>
    </div>
    <div class="nav-item">
      <a href="{{ route('settings.index') }}" class="nav-link {{ Request::is('settings*') ? 'active' : '' }}">
        <i class="bi bi-gear"></i> Settings
      </a>
    </div>

  </div>{{-- /nav-wrap --}}

  {{-- Sidebar footer --}}
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="sidebar-user-avatar"><i class="bi bi-person-fill"></i></div>
      <div>
        <div class="sidebar-user-name">Chief Secretary</div>
        <div class="sidebar-user-role">cs.pmru · Admin</div>
      </div>
      <a href="{{ route('logout') }}" class="sidebar-user-logout" title="Logout">
        <i class="bi bi-box-arrow-right"></i>
      </a>
    </div>
  </div>

</aside>

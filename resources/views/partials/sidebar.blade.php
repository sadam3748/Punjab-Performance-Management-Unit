<aside class="ppmf-sidebar" id="ppmfSidebar">

  {{-- Brand --}}
  <a href="{{ route('dashboard') }}" class="sidebar-brand">
    <div class="sidebar-brand-icon">
      <i class="bi bi-activity"></i>
    </div>
    <div class="sidebar-brand-text">
      <div class="sidebar-brand-title">PPMF Portal</div>
      <div class="sidebar-brand-sub">Punjab Govt. · 2025–26</div>
    </div>
  </a>

  <div class="sidebar-nav-wrap">

    {{-- Overview --}}
    <div class="nav-group-label">Overview</div>

    <div class="nav-item">
      <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <i class="bi bi-speedometer2"></i>
        Dashboard
      </a>
    </div>

    <div class="nav-item">
      <a href="{{ route('scorecard.index') }}" class="nav-link {{ request()->routeIs('scorecard.index') ? 'active' : '' }}">
        <i class="bi bi-award"></i>
        CM Governance Scorecard
      </a>
    </div>

    <div class="nav-item">
      <a href="{{ route('petrol.dashboard') }}" class="nav-link {{ request()->routeIs('petrol.dashboard') ? 'active' : '' }}">
        <i class="bi bi-fuel-pump"></i>
        Petrol Pump Monitoring
      </a>
    </div>

    {{-- Inspections --}}
    <div class="nav-item has-submenu">
      <a href="#inspectionSubmenu"
         class="nav-link {{ request()->routeIs('inspections.*') ? 'active' : '' }}"
         data-bs-toggle="collapse"
         role="button"
         aria-expanded="{{ request()->routeIs('inspections.*') ? 'true' : 'false' }}">
        <i class="bi bi-search"></i>
        Inspections
        <i class="bi bi-chevron-down ms-auto"></i>
      </a>

      <div class="collapse submenu {{ request()->routeIs('inspections.*') ? 'show' : '' }}" id="inspectionSubmenu">
        <a href="{{ route('inspections.map') }}" class="submenu-link {{ request()->routeIs('inspections.map') ? 'active' : '' }}">
          Map View
        </a>

        <a href="{{ route('inspections.list') }}" class="submenu-link {{ request()->routeIs('inspections.list') ? 'active' : '' }}">
          List View
        </a>
      </div>
    </div>

    {{-- Reports --}}
    <div class="nav-item has-submenu">
      <a href="#reportsSubmenu"
         class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}"
         data-bs-toggle="collapse"
         role="button"
         aria-expanded="{{ request()->routeIs('reports.*') ? 'true' : 'false' }}">
        <i class="bi bi-file-earmark-bar-graph"></i>
        Reports
        <i class="bi bi-chevron-down ms-auto"></i>
      </a>

      <div class="collapse submenu {{ request()->routeIs('reports.*') ? 'show' : '' }}" id="reportsSubmenu">

        <a href="{{ route('reports.category-wise-district-score') }}" class="submenu-link {{ request()->routeIs('reports.category-wise-district-score') ? 'active' : '' }}">
          <i class="bi bi-grid-3x3-gap"></i>
          Category wise District Score Report
        </a>

        <a href="{{ route('reports.district-sfn-victim-tier') }}" class="submenu-link {{ request()->routeIs('reports.district-sfn-victim-tier') ? 'active' : '' }}">
          <i class="bi bi-layers"></i>
          District Sixty Forty, Negative & Victim Tier Wise
        </a>

        <a href="{{ route('reports.district-sfn-comparison') }}" class="submenu-link {{ request()->routeIs('reports.district-sfn-comparison') ? 'active' : '' }}">
          <i class="bi bi-arrow-left-right"></i>
          District Sixty Forty & Negative Ratio Comparison
        </a>

        <a href="{{ route('reports.division-score') }}" class="submenu-link {{ request()->routeIs('reports.division-score') ? 'active' : '' }}">
          <i class="bi bi-diagram-3"></i>
          Division Score Report
        </a>

        <a href="{{ route('reports.district-comparison') }}" class="submenu-link {{ request()->routeIs('reports.district-comparison') ? 'active' : '' }}">
          <i class="bi bi-columns-gap"></i>
          District Comparison Report
        </a>

        <a href="{{ route('reports.district-accumulative') }}" class="submenu-link {{ request()->routeIs('reports.district-accumulative') ? 'active' : '' }}">
          <i class="bi bi-plus-square"></i>
          District Accumulative Report
        </a>

        <a href="{{ route('reports.division-kpi-ranking') }}" class="submenu-link {{ request()->routeIs('reports.division-kpi-ranking') ? 'active' : '' }}">
          <i class="bi bi-trophy"></i>
          Division KPI Ranking
        </a>

        <a href="{{ route('reports.district-weekly-kpi-inspection') }}" class="submenu-link {{ request()->routeIs('reports.district-weekly-kpi-inspection') ? 'active' : '' }}">
          <i class="bi bi-calendar-week"></i>
          District Weekly KPI Inspection
        </a>

        <a href="{{ route('reports.district-week-rank-changelog') }}" class="submenu-link {{ request()->routeIs('reports.district-week-rank-changelog') ? 'active' : '' }}">
          <i class="bi bi-clock-history"></i>
          District Week Rank Changelog
        </a>

        <a href="{{ route('reports.district-wise-kpi-score') }}" class="submenu-link {{ request()->routeIs('reports.district-wise-kpi-score') ? 'active' : '' }}">
          <i class="bi bi-speedometer2"></i>
          District KPI Score Report
        </a>

      </div>
    </div>

    {{-- KPI / Data Reports --}}
    <div class="nav-item">
      <a href="{{ route('kpi.provincial-data') }}" class="nav-link {{ request()->routeIs('kpi.provincial-data') ? 'active' : '' }}">
        <i class="bi bi-database"></i>
        Provincial KPI Wise Data
      </a>
    </div>

    <div class="nav-item">
      <a href="{{ route('kpi.reporting-status') }}" class="nav-link {{ request()->routeIs('kpi.reporting-status') ? 'active' : '' }}">
        <i class="bi bi-bar-chart-line"></i>
        KPI Reporting Status
      </a>
    </div>

    <div class="nav-item">
      <a href="{{ route('reports.district-baseline') }}" class="nav-link {{ request()->routeIs('reports.district-baseline') ? 'active' : '' }}">
        <i class="bi bi-list-ol"></i>
        District Baseline Data
      </a>
    </div>

<div class="nav-item">
  <a href="{{ route('kpi.graphical-report') }}"
     class="nav-link {{ request()->routeIs('kpi.graphical-report') ? 'active' : '' }}">
    <i class="bi bi-pie-chart"></i>
    KPI Graphical Report
  </a>
</div>

    {{-- Geo Taggings --}}
    <div class="nav-item has-submenu">
      <a href="#geoTaggingSubmenu"
         class="nav-link {{ request()->routeIs('geo-taggings.*') ? 'active' : '' }}"
         data-bs-toggle="collapse"
         role="button"
         aria-expanded="{{ request()->routeIs('geo-taggings.*') ? 'true' : 'false' }}">
        <i class="bi bi-geo-alt"></i>
        Geo Taggings
        <i class="bi bi-chevron-down ms-auto"></i>
      </a>

      <div class="collapse submenu {{ request()->routeIs('geo-taggings.*') ? 'show' : '' }}" id="geoTaggingSubmenu">
        <a href="{{ route('geo-taggings.map') }}" class="submenu-link {{ request()->routeIs('geo-taggings.map') ? 'active' : '' }}">
          Map View
        </a>

        <a href="{{ route('geo-taggings.list') }}" class="submenu-link {{ request()->routeIs('geo-taggings.list') ? 'active' : '' }}">
          List View
        </a>
      </div>
    </div>

  </div>

  {{-- Sidebar Footer --}}
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="sidebar-user-avatar">
        <i class="bi bi-person-fill"></i>
      </div>

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

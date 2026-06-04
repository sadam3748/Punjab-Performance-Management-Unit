<aside class="ppmf-sidebar" id="ppmfSidebar">

  {{-- Brand --}}
  <a href="{{ route('dashboard') }}" class="sidebar-brand">
    <div class="sidebar-brand-icon">
      <img src="{{ asset('images/pmru-logo.png') }}" alt="PMRU Wing Logo" class="portal-logo sidebar-logo">
    </div>
    <div class="sidebar-brand-text">
      <div class="sidebar-brand-title">PPMF Portal</div>
      <div class="sidebar-brand-sub">Punjab Govt. · 2025–26</div>
    </div>
  </a>

  <div class="sidebar-nav-wrap">

    {{-- Overview --}}
    <div class="nav-group-label">Overview</div>

    <div class="nav-item" style="display:none;">
      <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
        <i class="bi bi-speedometer2"></i>
        <span class="nav-text">Dashboard</span>
      </a>
    </div>

 <div class="nav-item">
    <a href="{{ route('scorecard.index') }}"
       class="nav-link {{ request()->routeIs('scorecard.index') ? 'active' : '' }}"
       title="District Wise Scorecard">
        <i class="bi bi-award"></i>
        <span class="nav-text">District Wise Scorecard</span>
    </a>
</div>

<div class="nav-item">
    <a href="{{ route('scorecard.tier') }}"
       class="nav-link {{ request()->routeIs('scorecard.tier') ? 'active' : '' }}"
       title="Tier Wise Scorecard">
        <i class="bi bi-layers"></i>
        <span class="nav-text">Tier Wise Scorecard</span>
    </a>
</div>

    <div class="nav-item">
      <a href="{{ route('petrol.dashboard') }}" class="nav-link {{ request()->routeIs('petrol.dashboard') ? 'active' : '' }}" title="Petrol Pump Monitoring">
        <i class="bi bi-fuel-pump"></i>
        <span class="nav-text">Petrol Pump Monitoring</span>
      </a>
    </div>

    {{-- Inspections --}}
    <div class="nav-item has-submenu">
      <a href="#inspectionSubmenu"
         class="nav-link {{ request()->routeIs('inspections.*') ? 'active' : '' }}"
         title="Inspections"
         data-bs-toggle="collapse"
         role="button"
         aria-expanded="{{ request()->routeIs('inspections.*') ? 'true' : 'false' }}">
        <i class="bi bi-search"></i>
        <span class="nav-text">Inspections</span>
        <i class="bi bi-chevron-down ms-auto"></i>
      </a>

      <div class="collapse submenu {{ request()->routeIs('inspections.*') ? 'show' : '' }}" id="inspectionSubmenu">
        <a href="{{ route('inspections.map') }}" class="submenu-link {{ request()->routeIs('inspections.map') ? 'active' : '' }}" title="Map View">
          <span class="submenu-text">Map View</span>
        </a>

        <a href="{{ route('inspections.list') }}" class="submenu-link {{ request()->routeIs('inspections.list') ? 'active' : '' }}" title="List View">
          <span class="submenu-text">List View</span>
        </a>
      </div>
    </div>

    {{-- Reports --}}
    <div class="nav-item has-submenu">
      <a href="#reportsSubmenu"
         class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}"
         title="Reports"
         data-bs-toggle="collapse"
         role="button"
         aria-expanded="{{ request()->routeIs('reports.*') ? 'true' : 'false' }}">
        <i class="bi bi-file-earmark-bar-graph"></i>
        <span class="nav-text">Reports</span>
        <i class="bi bi-chevron-down ms-auto"></i>
      </a>

      <div class="collapse submenu {{ request()->routeIs('reports.*') ? 'show' : '' }}" id="reportsSubmenu">

        <a href="{{ route('reports.category-wise-district-score') }}" class="submenu-link {{ request()->routeIs('reports.category-wise-district-score') ? 'active' : '' }}" title="Category wise District Score Report">
          <i class="bi bi-grid-3x3-gap"></i>
          <span class="submenu-text">Category wise District Score Report</span>
        </a>

        <a href="{{ route('reports.district-sfn-victim-tier') }}" class="submenu-link {{ request()->routeIs('reports.district-sfn-victim-tier') ? 'active' : '' }}" title="District Sixty Forty, Negative & Victim Tier Wise">
          <i class="bi bi-layers"></i>
          <span class="submenu-text">District Sixty Forty, Negative & Victim Tier Wise</span>
        </a>

        <a href="{{ route('reports.district-sfn-comparison') }}" class="submenu-link {{ request()->routeIs('reports.district-sfn-comparison') ? 'active' : '' }}" title="District Sixty Forty & Negative Ratio Comparison">
          <i class="bi bi-arrow-left-right"></i>
          <span class="submenu-text">District Sixty Forty & Negative Ratio Comparison</span>
        </a>

        <a href="{{ route('reports.division-score') }}" class="submenu-link {{ request()->routeIs('reports.division-score') ? 'active' : '' }}" title="Division Score Report">
          <i class="bi bi-diagram-3"></i>
          <span class="submenu-text">Division Score Report</span>
        </a>

        <a href="{{ route('reports.district-comparison') }}" class="submenu-link {{ request()->routeIs('reports.district-comparison') ? 'active' : '' }}" title="District Comparison Report">
          <i class="bi bi-columns-gap"></i>
          <span class="submenu-text">District Comparison Report</span>
        </a>

        <a href="{{ route('reports.district-accumulative') }}" class="submenu-link {{ request()->routeIs('reports.district-accumulative') ? 'active' : '' }}" title="District Accumulative Report">
          <i class="bi bi-plus-square"></i>
          <span class="submenu-text">District Accumulative Report</span>
        </a>

        <a href="{{ route('reports.division-kpi-ranking') }}" class="submenu-link {{ request()->routeIs('reports.division-kpi-ranking') ? 'active' : '' }}" title="Division KPI Ranking">
          <i class="bi bi-trophy"></i>
          <span class="submenu-text">Division KPI Ranking</span>
        </a>

        <a href="{{ route('reports.district-weekly-kpi-inspection') }}" class="submenu-link {{ request()->routeIs('reports.district-weekly-kpi-inspection') ? 'active' : '' }}" title="District Weekly KPI Inspection">
          <i class="bi bi-calendar-week"></i>
          <span class="submenu-text">District Weekly KPI Inspection</span>
        </a>

        <a href="{{ route('reports.district-week-rank-changelog') }}" class="submenu-link {{ request()->routeIs('reports.district-week-rank-changelog') ? 'active' : '' }}" title="District Week Rank Changelog">
          <i class="bi bi-clock-history"></i>
          <span class="submenu-text">District Week Rank Changelog</span>
        </a>

        <a href="{{ route('reports.district-wise-kpi-score') }}" class="submenu-link {{ request()->routeIs('reports.district-wise-kpi-score') ? 'active' : '' }}" title="District KPI Score Report">
          <i class="bi bi-speedometer2"></i>
          <span class="submenu-text">District KPI Score Report</span>
        </a>

      </div>
    </div>

    {{-- KPI / Data Reports --}}
    <div class="nav-item">
      <a href="{{ route('kpi.district-wise-kpi-score') }}" class="nav-link {{ request()->routeIs('kpi.district-wise-kpi-score') ? 'active' : '' }}" title="District Wise KPI Score Report">
        <i class="bi bi-speedometer2"></i>
        <span class="nav-text">District Wise KPI Score</span>
      </a>
    </div>

    <div class="nav-item">
      <a href="{{ route('kpi.provincial-data') }}" class="nav-link {{ request()->routeIs('kpi.provincial-data') ? 'active' : '' }}" title="Provincial KPI Wise Data">
        <i class="bi bi-database"></i>
        <span class="nav-text">Provincial KPI Wise Data</span>
      </a>
    </div>

    <div class="nav-item">
      <a href="{{ route('kpi.reporting-status') }}" class="nav-link {{ request()->routeIs('kpi.reporting-status') ? 'active' : '' }}" title="KPI Reporting Status">
        <i class="bi bi-bar-chart-line"></i>
        <span class="nav-text">KPI Reporting Status</span>
      </a>
    </div>

<div class="nav-item">
  <a href="{{ route('baseline.district-baseline') }}"
     class="nav-link {{ request()->routeIs('baseline.district-baseline') ? 'active' : '' }}"
     title="District Baseline Data">
    <i class="bi bi-list-ol"></i>
    <span class="nav-text">District Baseline Data</span>
  </a>
</div>

<div class="nav-item">
  <a href="{{ route('kpi.graphical-report') }}"
     class="nav-link {{ request()->routeIs('kpi.graphical-report') ? 'active' : '' }}"
     title="KPI Graphical Report">
    <i class="bi bi-pie-chart"></i>
    <span class="nav-text">KPI Graphical Report</span>
  </a>
</div>

    {{-- Geo Taggings --}}
    <div class="nav-item has-submenu">
      <a href="#geoTaggingSubmenu"
         class="nav-link {{ request()->routeIs('geo-taggings.*') ? 'active' : '' }}"
         title="Geo Taggings"
         data-bs-toggle="collapse"
         role="button"
         aria-expanded="{{ request()->routeIs('geo-taggings.*') ? 'true' : 'false' }}">
        <i class="bi bi-geo-alt"></i>
        <span class="nav-text">Geo Taggings</span>
        <i class="bi bi-chevron-down ms-auto"></i>
      </a>

      <div class="collapse submenu {{ request()->routeIs('geo-taggings.*') ? 'show' : '' }}" id="geoTaggingSubmenu">
        <a href="{{ route('geo-taggings.map') }}" class="submenu-link {{ request()->routeIs('geo-taggings.map') ? 'active' : '' }}" title="Map View">
          <span class="submenu-text">Map View</span>
        </a>

        <a href="{{ route('geo-taggings.list') }}" class="submenu-link {{ request()->routeIs('geo-taggings.list') ? 'active' : '' }}" title="List View">
          <span class="submenu-text">List View</span>
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

      <div class="sidebar-user-meta">
        <div class="sidebar-user-name">Chief Secretary</div>
        <div class="sidebar-user-role">cs.pmru · Admin</div>
      </div>

      <a href="{{ route('logout') }}" class="sidebar-user-logout" title="Logout">
        <i class="bi bi-box-arrow-right"></i>
      </a>
    </div>
  </div>

</aside>

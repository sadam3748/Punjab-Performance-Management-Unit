<header class="ppmf-header">

  {{-- Sidebar toggle --}}
  <button class="header-toggle" id="sidebarToggle" title="Toggle Sidebar">
    <i class="bi bi-list"></i>
  </button>

  {{-- Breadcrumb --}}
  <nav class="header-breadcrumb">
    <i class="bi bi-house-door" style="font-size:13px;"></i>
    @yield('breadcrumb_parent', '')
    @hasSection('breadcrumb_parent')
      <i class="bi bi-chevron-right" style="font-size:10px;"></i>
    @endif
    <span class="bc-current">@yield('page_title', 'Dashboard')</span>
  </nav>

  <div class="header-spacer"></div>

  {{-- Search --}}
  <div class="header-search">
    <i class="bi bi-search search-icon"></i>
    <input type="text" placeholder="Search KPIs, Districts, Reports…">
  </div>

  {{-- Actions --}}
  <div class="header-actions">

    {{-- Notifications --}}
    <div class="dropdown-ppmf">
      <button class="header-btn" data-dropdown="notifMenu" title="Notifications">
        <i class="bi bi-bell"></i>
        <span class="header-notif-badge"></span>
      </button>
      <div class="dropdown-menu-ppmf" id="notifMenu">
        <div style="padding:12px 16px;border-bottom:1px solid var(--border);">
          <span style="font-size:13px;font-weight:700;color:var(--text-primary);">Notifications</span>
        </div>
        <a href="#"><i class="bi bi-exclamation-circle text-danger"></i>
          <span>3 districts below target threshold</span></a>
        <a href="#"><i class="bi bi-check-circle text-green"></i>
          <span>Monthly KPI report generated</span></a>
        <a href="#"><i class="bi bi-info-circle" style="color:var(--info)"></i>
          <span>New data entry deadline reminder</span></a>
        <div class="divider"></div>
        <a href="#" style="justify-content:center;font-weight:600;color:var(--gov-green);">
          View all notifications</a>
      </div>
    </div>

    {{-- Export --}}
    <button class="header-btn" title="Export current report">
      <i class="bi bi-download"></i>
    </button>

    {{-- Refresh --}}
    <button class="header-btn" title="Refresh data" onclick="location.reload()">
      <i class="bi bi-arrow-clockwise"></i>
    </button>

  </div>

  {{-- User profile --}}
  <div class="dropdown-ppmf">
    <a class="header-user" href="#" data-dropdown="userMenu">
      <div class="header-user-avatar">CS</div>
      <div>
        <div class="header-user-name">Chief Secretary</div>
        <div class="header-user-role">Administrator</div>
      </div>
      <i class="bi bi-chevron-down" style="font-size:11px;color:var(--text-muted);margin-left:2px;"></i>
    </a>
    <div class="dropdown-menu-ppmf" id="userMenu">
      <a href="#"><i class="bi bi-person"></i> My Profile</a>
      <a href="{{ route('settings.index') }}"><i class="bi bi-gear"></i> Settings</a>
      <div class="divider"></div>
      <a href="{{ route('logout') }}" style="color:var(--danger);">
        <i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
  </div>

</header>

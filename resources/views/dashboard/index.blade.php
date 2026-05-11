@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')

{{-- Page title --}}
<div class="page-title-bar animate-in">
  <div>
    <div class="page-title">Performance Overview</div>
    <div class="page-subtitle">
      <i class="bi bi-calendar3" style="margin-right:5px;"></i>
      Fiscal Year 2024–25 &nbsp;·&nbsp; Week: 30 Apr – 06 May 2025 &nbsp;·&nbsp;
      <span style="color:var(--gov-green);font-weight:600;">Live Data</span>
    </div>
  </div>
  <div class="page-title-actions">
    <button class="btn-gov btn-gov-ghost btn-gov-sm"><i class="bi bi-download"></i> Export</button>
    <button class="btn-gov btn-gov-outline btn-gov-sm"><i class="bi bi-printer"></i> Print</button>
    <button class="btn-gov btn-gov-primary btn-gov-sm"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
  </div>
</div>

{{-- Filter Panel --}}
<div class="filter-panel animate-in delay-1">
  <div class="filter-panel-title"><i class="bi bi-funnel"></i> Filters</div>
  <div class="filter-row">
    <div class="filter-group">
      <label>Division</label>
      <select><option>All Divisions</option><option>Lahore</option><option>Multan</option><option>Rawalpindi</option><option>Faisalabad</option><option>Gujranwala</option><option>Sargodha</option><option>Bahawalpur</option><option>Sahiwal</option><option>DG Khan</option></select>
    </div>
    <div class="filter-group">
      <label>District</label>
      <select><option>All Districts</option><option>Lahore</option><option>Faisalabad</option><option>Rawalpindi</option><option>Multan</option><option>Gujranwala</option></select>
    </div>
    <div class="filter-group">
      <label>Department</label>
      <select><option>All Departments</option><option>Health</option><option>Education</option><option>Agriculture</option><option>Finance</option><option>Works</option><option>Police</option></select>
    </div>
    <div class="filter-group">
      <label>Frequency</label>
      <select><option>Weekly</option><option>Monthly</option><option>Quarterly</option><option>Annual</option></select>
    </div>
    <div class="filter-group">
      <label>Month</label>
      <select><option>May</option><option>April</option><option>March</option><option>February</option></select>
    </div>
    <div class="filter-group">
      <label>Year</label>
      <select><option>2025</option><option>2024</option><option>2023</option></select>
    </div>
    <div class="filter-actions">
      <button class="btn-gov btn-gov-primary"><i class="bi bi-search"></i> Apply</button>
      <button class="btn-gov btn-gov-ghost" data-reset-filters><i class="bi bi-x-circle"></i> Reset</button>
    </div>
  </div>
</div>

{{-- KPI Stat Cards --}}
<div class="row g-3 mb-4 animate-in delay-2">
  <div class="col-xl col-md-4 col-sm-6">
    <div class="stat-card green">
      <div class="stat-header">
        <div class="stat-icon green"><i class="bi bi-bar-chart-line"></i></div>
        <div class="stat-trend up"><i class="bi bi-arrow-up"></i>+12</div>
      </div>
      <div class="stat-value" data-count="234">0</div>
      <div class="stat-label">Total KPIs</div>
      <div class="stat-sub"><i class="bi bi-info-circle text-green"></i> Across all departments</div>
    </div>
  </div>
  <div class="col-xl col-md-4 col-sm-6">
    <div class="stat-card teal">
      <div class="stat-header">
        <div class="stat-icon teal"><i class="bi bi-check2-circle"></i></div>
        <div class="stat-trend up"><i class="bi bi-arrow-up"></i>+8</div>
      </div>
      <div class="stat-value" data-count="148">0</div>
      <div class="stat-label">Achieved KPIs</div>
      <div class="stat-sub"><i class="bi bi-graph-up text-teal"></i> 63.2% achievement rate</div>
    </div>
  </div>
  <div class="col-xl col-md-4 col-sm-6">
    <div class="stat-card gold">
      <div class="stat-header">
        <div class="stat-icon gold"><i class="bi bi-clock-history"></i></div>
        <div class="stat-trend flat"><i class="bi bi-dash"></i> Same</div>
      </div>
      <div class="stat-value" data-count="62">0</div>
      <div class="stat-label">Pending KPIs</div>
      <div class="stat-sub"><i class="bi bi-exclamation-circle text-gold"></i> Require immediate attention</div>
    </div>
  </div>
  <div class="col-xl col-md-4 col-sm-6">
    <div class="stat-card danger">
      <div class="stat-header">
        <div class="stat-icon danger"><i class="bi bi-exclamation-triangle"></i></div>
        <div class="stat-trend down"><i class="bi bi-arrow-down"></i>-3</div>
      </div>
      <div class="stat-value" data-count="8">0</div>
      <div class="stat-label">Critical Districts</div>
      <div class="stat-sub"><i class="bi bi-geo-alt text-danger"></i> Below 50% performance</div>
    </div>
  </div>
  <div class="col-xl col-md-4 col-sm-6">
    <div class="stat-card info">
      <div class="stat-header">
        <div class="stat-icon info"><i class="bi bi-trophy"></i></div>
        <div class="stat-trend up"><i class="bi bi-arrow-up"></i>+4.2%</div>
      </div>
      <div class="stat-value">76<span style="font-size:16px;font-weight:600;color:var(--text-muted);">%</span></div>
      <div class="stat-label">Overall Score</div>
      <div class="stat-sub"><i class="bi bi-star text-gold"></i> Punjab average score</div>
    </div>
  </div>
</div>

{{-- Row: Charts --}}
<div class="row g-3 mb-4 animate-in delay-3">

  {{-- Monthly Performance --}}
  <div class="col-lg-8">
    <div class="card-ppmf h-100">
      <div class="card-ppmf-header">
        <div class="card-ppmf-title"><i class="bi bi-graph-up"></i> Monthly Performance Trend</div>
        <div class="card-ppmf-actions">
          <button class="btn-icon" title="Download chart"><i class="bi bi-download"></i></button>
          <button class="btn-icon" title="Expand"><i class="bi bi-arrows-fullscreen"></i></button>
        </div>
      </div>
      <div class="card-ppmf-body">
        <div class="chart-wrap"><canvas id="monthlyChart"></canvas></div>
      </div>
    </div>
  </div>

  {{-- KPI Donut --}}
  <div class="col-lg-4">
    <div class="card-ppmf h-100">
      <div class="card-ppmf-header">
        <div class="card-ppmf-title"><i class="bi bi-pie-chart"></i> KPI Status</div>
      </div>
      <div class="card-ppmf-body">
        <div class="chart-wrap" style="height:200px;"><canvas id="kpiDonut"></canvas></div>
        <div class="divider"></div>
        <div style="display:flex;flex-direction:column;gap:8px;margin-top:4px;">
          <div style="display:flex;align-items:center;justify-content:space-between;">
            <span style="display:flex;align-items:center;gap:7px;font-size:12.5px;color:var(--text-secondary);">
              <span style="width:10px;height:10px;background:var(--gov-green);border-radius:50%;flex-shrink:0;"></span>Achieved
            </span>
            <span style="font-size:13px;font-weight:700;color:var(--gov-green);">148 <small style="font-weight:400;color:var(--text-muted);">(63.2%)</small></span>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;">
            <span style="display:flex;align-items:center;gap:7px;font-size:12.5px;color:var(--text-secondary);">
              <span style="width:10px;height:10px;background:var(--gold);border-radius:50%;flex-shrink:0;"></span>In Progress
            </span>
            <span style="font-size:13px;font-weight:700;color:var(--gold);">62 <small style="font-weight:400;color:var(--text-muted);">(26.5%)</small></span>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;">
            <span style="display:flex;align-items:center;gap:7px;font-size:12.5px;color:var(--text-secondary);">
              <span style="width:10px;height:10px;background:var(--danger);border-radius:50%;flex-shrink:0;"></span>Critical
            </span>
            <span style="font-size:13px;font-weight:700;color:var(--danger);">24 <small style="font-weight:400;color:var(--text-muted);">(10.3%)</small></span>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

{{-- Row: Map + Tier Rankings --}}
<div class="row g-3 mb-4 animate-in delay-3">

  {{-- Punjab Map --}}
  <div class="col-lg-5">
    <div class="map-card h-100">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
        <div>
          <div style="font-size:15px;font-weight:700;">Punjab District Map</div>
          <div style="font-size:11.5px;opacity:.6;margin-top:2px;">Performance heat map view</div>
        </div>
        <a href="{{ route('map.index') }}" style="color:rgba(255,255,255,.7);font-size:12px;text-decoration:none;background:rgba(255,255,255,.1);padding:5px 12px;border-radius:20px;border:1px solid rgba(255,255,255,.2);">
          Full View <i class="bi bi-arrow-right"></i>
        </a>
      </div>
      <div class="map-placeholder">
        <i class="bi bi-map"></i>
        <p>Interactive Punjab district map<br>with performance color coding<br>coming in next version</p>
        <a href="{{ route('map.index') }}" class="btn-gov btn-gov-outline btn-gov-sm" style="border-color:rgba(255,255,255,.4);color:#fff;background:rgba(255,255,255,.1);">
          <i class="bi bi-geo-alt"></i> View Map
        </a>
      </div>
    </div>
  </div>

  {{-- Tier Rankings --}}
  <div class="col-lg-7">
    <div class="card-ppmf h-100">
      <div class="card-ppmf-header">
        <div class="card-ppmf-title"><i class="bi bi-layers"></i> CM Governance Scorecard — Tier Ranking</div>
        <a href="{{ route('scorecard.index') }}" class="btn-gov btn-gov-ghost btn-gov-sm">
          Full Scorecard <i class="bi bi-arrow-right"></i>
        </a>
      </div>
      <div class="card-ppmf-body" style="padding:16px;">

        {{-- Tier 1 --}}
        <div style="margin-bottom:16px;">
          <div class="tier-header"><i class="bi bi-1-circle-fill"></i> Tier 1 — Metropolitan Districts</div>
          <div class="tier-table-wrap">
            <table class="table-ppmf" style="font-size:12.5px;">
              <thead>
                <tr><th>#</th><th>District</th><th>Score</th><th>Trend</th><th>Progress</th></tr>
              </thead>
              <tbody>
                <tr>
                  <td><div class="rank-badge rank-1">1</div></td>
                  <td><strong>Faisalabad</strong></td>
                  <td><span class="badge-ppmf achieved">88%</span></td>
                  <td><i class="bi bi-arrow-up text-green"></i></td>
                  <td style="min-width:100px;"><div class="progress-ppmf"><div class="bar green" data-progress="88"></div></div></td>
                </tr>
                <tr>
                  <td><div class="rank-badge rank-2">2</div></td>
                  <td><strong>Lahore</strong></td>
                  <td><span class="badge-ppmf achieved">85%</span></td>
                  <td><i class="bi bi-arrow-up text-green"></i></td>
                  <td><div class="progress-ppmf"><div class="bar green" data-progress="85"></div></div></td>
                </tr>
                <tr>
                  <td><div class="rank-badge rank-3">3</div></td>
                  <td><strong>Rawalpindi</strong></td>
                  <td><span class="badge-ppmf achieved">81%</span></td>
                  <td><i class="bi bi-dash text-muted"></i></td>
                  <td><div class="progress-ppmf"><div class="bar teal" data-progress="81"></div></div></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        {{-- Tier 2 --}}
        <div>
          <div class="tier-header" style="background:var(--teal);"><i class="bi bi-2-circle-fill"></i> Tier 2 — Large Districts</div>
          <div class="tier-table-wrap" style="border-color:var(--teal);">
            <table class="table-ppmf" style="font-size:12.5px;">
              <thead>
                <tr><th>#</th><th>District</th><th>Score</th><th>Trend</th><th>Progress</th></tr>
              </thead>
              <tbody>
                <tr>
                  <td><div class="rank-badge rank-1">1</div></td>
                  <td><strong>Gujranwala</strong></td>
                  <td><span class="badge-ppmf achieved">79%</span></td>
                  <td><i class="bi bi-arrow-up text-green"></i></td>
                  <td><div class="progress-ppmf"><div class="bar teal" data-progress="79"></div></div></td>
                </tr>
                <tr>
                  <td><div class="rank-badge rank-2">2</div></td>
                  <td><strong>Multan</strong></td>
                  <td><span class="badge-ppmf pending">74%</span></td>
                  <td><i class="bi bi-arrow-down text-danger"></i></td>
                  <td><div class="progress-ppmf"><div class="bar gold" data-progress="74"></div></div></td>
                </tr>
                <tr>
                  <td><div class="rank-badge rank-3">3</div></td>
                  <td><strong>Sialkot</strong></td>
                  <td><span class="badge-ppmf pending">71%</span></td>
                  <td><i class="bi bi-dash text-muted"></i></td>
                  <td><div class="progress-ppmf"><div class="bar gold" data-progress="71"></div></div></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>

</div>

{{-- Row: Dept chart + Division chart --}}
<div class="row g-3 mb-4 animate-in delay-4">
  <div class="col-lg-6">
    <div class="card-ppmf h-100">
      <div class="card-ppmf-header">
        <div class="card-ppmf-title"><i class="bi bi-building"></i> Department-wise Performance</div>
        <button class="btn-icon" title="Download"><i class="bi bi-download"></i></button>
      </div>
      <div class="card-ppmf-body">
        <div class="chart-wrap" style="height:220px;"><canvas id="deptChart"></canvas></div>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card-ppmf h-100">
      <div class="card-ppmf-header">
        <div class="card-ppmf-title"><i class="bi bi-diagram-3"></i> Division Performance</div>
        <a href="{{ route('divisions.performance') }}" class="btn-gov btn-gov-ghost btn-gov-sm">Details</a>
      </div>
      <div class="card-ppmf-body">
        <div class="chart-wrap" style="height:220px;"><canvas id="divisionChart"></canvas></div>
      </div>
    </div>
  </div>
</div>

{{-- District Performance Table + Recent Activity --}}
<div class="row g-3 animate-in delay-5">

  {{-- District Table --}}
  <div class="col-lg-8">
    <div class="card-ppmf">
      <div class="card-ppmf-header">
        <div class="card-ppmf-title"><i class="bi bi-pin-map"></i> District Performance Summary</div>
        <div class="card-ppmf-actions">
          <input type="text" placeholder="Search district…" style="height:30px;border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:0 10px;font-size:12px;outline:none;font-family:var(--font);">
          <a href="{{ route('districts.performance') }}" class="btn-gov btn-gov-ghost btn-gov-sm">View All</a>
        </div>
      </div>
      <div style="overflow-x:auto;">
        <table class="table-ppmf">
          <thead>
            <tr>
              <th>#</th><th>District</th><th>Division</th><th>KPIs</th>
              <th>Achieved</th><th>Score</th><th>Trend</th><th>Status</th>
            </tr>
          </thead>
          <tbody>
            @php
            $districts = [
              [1,'Lahore','Lahore',28,25,89,'up','achieved'],
              [2,'Faisalabad','Faisalabad',24,21,87,'up','achieved'],
              [3,'Rawalpindi','Rawalpindi',22,18,81,'flat','achieved'],
              [4,'Gujranwala','Gujranwala',20,16,79,'up','achieved'],
              [5,'Multan','Multan',22,16,74,'down','pending'],
              [6,'Sialkot','Gujranwala',18,13,71,'flat','pending'],
              [7,'Sargodha','Sargodha',20,13,65,'down','pending'],
              [8,'Bahawalpur','Bahawalpur',18,10,56,'down','critical'],
            ];
            $trendIcons = ['up'=>'bi-arrow-up text-green','flat'=>'bi-dash text-muted','down'=>'bi-arrow-down text-danger'];
            @endphp
            @foreach($districts as [$rank,$district,$division,$kpis,$achieved,$score,$trend,$status])
            <tr>
              <td>
                <div class="rank-badge {{ $rank<=3 ? 'rank-'.$rank : 'rank-n' }}">{{ $rank }}</div>
              </td>
              <td><strong>{{ $district }}</strong></td>
              <td style="color:var(--text-muted);font-size:12px;">{{ $division }}</td>
              <td style="font-variant-numeric:tabular-nums;">{{ $kpis }}</td>
              <td>{{ $achieved }}</td>
              <td>
                <div style="display:flex;align-items:center;gap:8px;">
                  <div class="progress-ppmf" style="width:60px;"><div class="bar {{ $status === 'achieved' ? 'green' : ($status === 'pending' ? 'gold' : 'danger') }}" data-progress="{{ $score }}"></div></div>
                  <span style="font-size:12px;font-weight:700;">{{ $score }}%</span>
                </div>
              </td>
              <td><i class="bi {{ $trendIcons[$trend] }}" style="font-size:15px;"></i></td>
              <td>
                <span class="badge-ppmf {{ $status }}">
                  {{ ucfirst($status) }}
                </span>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Recent Activity --}}
  <div class="col-lg-4">
    <div class="card-ppmf h-100">
      <div class="card-ppmf-header">
        <div class="card-ppmf-title"><i class="bi bi-activity"></i> Recent Activity</div>
        <button class="btn-icon"><i class="bi bi-three-dots"></i></button>
      </div>
      <div class="card-ppmf-body" style="padding:0;">
        @php
        $activities = [
          ['color'=>'green','icon'=>'bi-check-circle-fill','title'=>'KPI Updated','desc'=>'Health KPI #H-024 marked achieved','time'=>'2 min ago'],
          ['color'=>'info','icon'=>'bi-upload','title'=>'Data Submitted','desc'=>'Lahore district submitted monthly report','time'=>'18 min ago'],
          ['color'=>'danger','icon'=>'bi-exclamation-circle-fill','title'=>'Alert Triggered','desc'=>'DG Khan below 50% threshold','time'=>'1 hr ago'],
          ['color'=>'gold','icon'=>'bi-clock-history','title'=>'Pending Reminder','desc'=>'5 KPIs awaiting verification','time'=>'2 hrs ago'],
          ['color'=>'teal','icon'=>'bi-file-earmark-check','title'=>'Report Generated','desc'=>'April 2025 scorecard published','time'=>'4 hrs ago'],
          ['color'=>'info','icon'=>'bi-person-plus','title'=>'User Added','desc'=>'New officer added: DO Sahiwal','time'=>'Yesterday'],
        ];
        @endphp
        @foreach($activities as $act)
        <div style="display:flex;gap:12px;padding:12px 18px;border-bottom:1px solid var(--border-light);">
          <div style="width:34px;height:34px;border-radius:50%;background:var(--{{ $act['color'] }}-light,var(--bg));display:grid;place-items:center;font-size:15px;color:var(--{{ $act['color'] }},var(--text-muted));flex-shrink:0;">
            <i class="bi {{ $act['icon'] }}"></i>
          </div>
          <div style="flex:1;min-width:0;">
            <div style="font-size:12.5px;font-weight:600;color:var(--text-primary);">{{ $act['title'] }}</div>
            <div style="font-size:11.5px;color:var(--text-muted);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $act['desc'] }}</div>
          </div>
          <div style="font-size:10.5px;color:var(--text-muted);white-space:nowrap;flex-shrink:0;">{{ $act['time'] }}</div>
        </div>
        @endforeach
        <div style="padding:12px 18px;">
          <a href="#" style="font-size:12.5px;color:var(--gov-green);text-decoration:none;font-weight:600;display:flex;align-items:center;gap:6px;">
            View all activity <i class="bi bi-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>
  </div>

</div>

@endsection

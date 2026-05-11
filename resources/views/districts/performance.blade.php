@extends('layouts.app')
@section('title','District Performance')
@section('page_title','District Performance')
@section('breadcrumb_parent','Performance')

@section('content')

<div class="page-title-bar animate-in">
  <div>
    <div class="page-title">District Performance</div>
    <div class="page-subtitle">Detailed KPI performance across all 36 districts of Punjab</div>
  </div>
  <div class="page-title-actions">
    <button class="btn-gov btn-gov-ghost btn-gov-sm"><i class="bi bi-file-earmark-excel"></i> Export</button>
    <button class="btn-gov btn-gov-primary btn-gov-sm"><i class="bi bi-printer"></i> Print Report</button>
  </div>
</div>

<div class="filter-panel animate-in delay-1">
  <div class="filter-panel-title"><i class="bi bi-funnel"></i> Filters</div>
  <div class="filter-row">
    <div class="filter-group"><label>Division</label>
      <select><option>All Divisions</option><option>Lahore</option><option>Multan</option><option>Rawalpindi</option><option>Faisalabad</option><option>Gujranwala</option><option>Sargodha</option><option>Bahawalpur</option><option>Sahiwal</option><option>DG Khan</option></select>
    </div>
    <div class="filter-group"><label>Tier</label>
      <select><option>All Tiers</option><option>Tier 1</option><option>Tier 2</option><option>Tier 3</option></select>
    </div>
    <div class="filter-group"><label>Period</label>
      <select><option>May 2025</option><option>April 2025</option><option>March 2025</option><option>Q3 FY2024-25</option></select>
    </div>
    <div class="filter-group"><label>Status</label>
      <select><option>All</option><option>On Track</option><option>Moderate</option><option>Critical</option></select>
    </div>
    <div class="filter-actions">
      <button class="btn-gov btn-gov-primary"><i class="bi bi-search"></i> Apply</button>
      <button class="btn-gov btn-gov-ghost" data-reset-filters><i class="bi bi-x-circle"></i> Reset</button>
    </div>
  </div>
</div>

{{-- Summary --}}
<div class="row g-3 mb-4 animate-in delay-2">
  <div class="col-md-3"><div class="stat-card green">
    <div class="stat-header"><div class="stat-icon green"><i class="bi bi-buildings"></i></div><div class="stat-trend up"><i class="bi bi-arrow-up"></i>+2</div></div>
    <div class="stat-value" data-count="18">0</div><div class="stat-label">On Track Districts</div>
  </div></div>
  <div class="col-md-3"><div class="stat-card gold">
    <div class="stat-header"><div class="stat-icon gold"><i class="bi bi-exclamation-circle"></i></div></div>
    <div class="stat-value" data-count="10">0</div><div class="stat-label">Moderate Districts</div>
  </div></div>
  <div class="col-md-3"><div class="stat-card danger">
    <div class="stat-header"><div class="stat-icon danger"><i class="bi bi-x-circle"></i></div><div class="stat-trend down"><i class="bi bi-arrow-down"></i>-3</div></div>
    <div class="stat-value" data-count="8">0</div><div class="stat-label">Critical Districts</div>
  </div></div>
  <div class="col-md-3"><div class="stat-card info">
    <div class="stat-header"><div class="stat-icon info"><i class="bi bi-bar-chart"></i></div></div>
    <div class="stat-value">76<span style="font-size:14px;font-weight:500;color:var(--text-muted);">%</span></div>
    <div class="stat-label">Punjab Average Score</div>
  </div></div>
</div>

{{-- Table + Mini chart --}}
<div class="row g-3 animate-in delay-3">
  <div class="col-lg-8">
    <div class="card-ppmf">
      <div class="card-ppmf-header">
        <div class="card-ppmf-title"><i class="bi bi-pin-map"></i> All Districts</div>
        <div style="display:flex;align-items:center;gap:8px;">
          <input type="text" placeholder="Search…" style="height:30px;border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:0 10px;font-size:12px;font-family:var(--font);outline:none;">
          <button class="btn-gov btn-gov-ghost btn-gov-sm">Sort by Score <i class="bi bi-arrow-down-up"></i></button>
        </div>
      </div>
      <div style="overflow-x:auto;">
        <table class="table-ppmf">
          <thead><tr><th>Rank</th><th>District</th><th>Division</th><th>Tier</th><th>Total KPIs</th><th>Achieved</th><th>Score</th><th>Status</th><th></th></tr></thead>
          <tbody>
            @php
            $districts = [
              [1,'Faisalabad','Faisalabad',1,28,25,89,'achieved'],
              [2,'Lahore','Lahore',1,28,24,85,'achieved'],
              [3,'Rawalpindi','Rawalpindi',1,22,18,81,'achieved'],
              [4,'Gujranwala','Gujranwala',1,20,16,79,'achieved'],
              [5,'Multan','Multan',1,22,16,74,'pending'],
              [6,'Sialkot','Gujranwala',2,18,13,71,'pending'],
              [7,'Sargodha','Sargodha',2,20,13,65,'pending'],
              [8,'Jhang','Faisalabad',2,18,11,62,'pending'],
              [9,'Bahawalpur','Bahawalpur',2,18,10,56,'critical'],
              [10,'DG Khan','DG Khan',3,16,8,48,'critical'],
            ];
            @endphp
            @foreach($districts as [$rank,$dist,$div,$tier,$kpis,$ach,$score,$status])
            <tr>
              <td><div class="rank-badge {{ $rank<=3?'rank-'.$rank:'rank-n' }}">{{ $rank }}</div></td>
              <td><strong>{{ $dist }}</strong></td>
              <td style="font-size:12px;color:var(--text-muted);">{{ $div }}</td>
              <td><span class="badge-ppmf {{ $tier===1?'achieved':($tier===2?'pending':'critical') }}">T{{ $tier }}</span></td>
              <td>{{ $kpis }}</td>
              <td>{{ $ach }}</td>
              <td>
                <div style="display:flex;align-items:center;gap:6px;">
                  <div class="progress-ppmf" style="width:55px;"><div class="bar {{ $status==='achieved'?'green':($status==='pending'?'gold':'danger') }}" data-progress="{{ $score }}"></div></div>
                  <span style="font-size:12px;font-weight:700;">{{ $score }}%</span>
                </div>
              </td>
              <td><span class="badge-ppmf {{ $status }}">{{ $status==='achieved'?'On Track':($status==='pending'?'Moderate':'Critical') }}</span></td>
              <td><button class="btn-icon" title="View detail"><i class="bi bi-eye"></i></button></td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card-ppmf mb-3">
      <div class="card-ppmf-header"><div class="card-ppmf-title"><i class="bi bi-bar-chart-horizontal"></i> Division Scores</div></div>
      <div class="card-ppmf-body"><div class="chart-wrap" style="height:260px;"><canvas id="divisionChart"></canvas></div></div>
    </div>

    {{-- Top & Bottom --}}
    <div class="card-ppmf">
      <div class="card-ppmf-header"><div class="card-ppmf-title"><i class="bi bi-trophy"></i> Top Performers</div></div>
      <div class="card-ppmf-body" style="padding:12px 16px;">
        @foreach([['Faisalabad',89,'green'],['Lahore',85,'green'],['Rawalpindi',81,'teal']] as [$d,$s,$c])
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
          <span style="font-size:13px;font-weight:600;">{{ $d }}</span>
          <div style="display:flex;align-items:center;gap:8px;">
            <div class="progress-ppmf" style="width:80px;"><div class="bar {{ $c }}" data-progress="{{ $s }}"></div></div>
            <span style="font-size:12px;font-weight:700;width:36px;text-align:right;">{{ $s }}%</span>
          </div>
        </div>
        @endforeach
        <div class="divider"></div>
        <div style="font-size:11px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--text-muted);margin-bottom:10px;">Needs Attention</div>
        @foreach([['DG Khan',48,'danger'],['Bahawalpur',56,'danger'],['Jhang',62,'warning']] as [$d,$s,$c])
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
          <span style="font-size:13px;font-weight:600;">{{ $d }}</span>
          <div style="display:flex;align-items:center;gap:8px;">
            <div class="progress-ppmf" style="width:80px;"><div class="bar {{ $c }}" data-progress="{{ $s }}"></div></div>
            <span style="font-size:12px;font-weight:700;width:36px;text-align:right;color:var(--danger);">{{ $s }}%</span>
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </div>
</div>

@endsection

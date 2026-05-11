@extends('layouts.app')

@section('title', 'CM Governance Scorecard')
@section('page_title', 'CM Governance Scorecard')
@section('breadcrumb_parent', 'Performance')

@section('content')

<div class="page-title-bar animate-in">
  <div>
    <div class="page-title">CM Governance Scorecard — Tier Wise</div>
    <div class="page-subtitle">
      <i class="bi bi-calendar-week" style="margin-right:5px;"></i>
      Week: 30 Apr – 06 May 2025 &nbsp;·&nbsp; Showing district rankings by performance tier
    </div>
  </div>
  <div class="page-title-actions">
    <button class="btn-gov btn-gov-ghost btn-gov-sm"><i class="bi bi-file-earmark-pdf"></i> Export PDF</button>
    <button class="btn-gov btn-gov-ghost btn-gov-sm"><i class="bi bi-file-earmark-excel"></i> Export Excel</button>
    <button class="btn-gov btn-gov-primary btn-gov-sm"><i class="bi bi-printer"></i> Print</button>
  </div>
</div>

{{-- Filters --}}
<div class="filter-panel animate-in delay-1">
  <div class="filter-panel-title"><i class="bi bi-funnel"></i> Scorecard Filters</div>
  <div class="filter-row">
    <div class="filter-group">
      <label>Division</label>
      <select><option>All Divisions</option><option>Lahore</option><option>Multan</option><option>Rawalpindi</option><option>Faisalabad</option><option>Gujranwala</option></select>
    </div>
    <div class="filter-group">
      <label>Frequency</label>
      <select><option>Weekly</option><option>Monthly</option><option>Quarterly</option></select>
    </div>
    <div class="filter-group">
      <label>Period</label>
      <select><option>30 Apr – 06 May</option><option>23 Apr – 29 Apr</option><option>16 Apr – 22 Apr</option></select>
    </div>
    <div class="filter-group">
      <label>Month</label>
      <select><option>May</option><option>April</option><option>March</option></select>
    </div>
    <div class="filter-group">
      <label>Year</label>
      <select><option>2025</option><option>2024</option></select>
    </div>
    <div class="filter-group">
      <label>Indicator</label>
      <select><option>All Indicators</option><option>Victims Negative Marking</option><option>Health Score</option><option>Education Score</option></select>
    </div>
    <div class="filter-actions">
      <button class="btn-gov btn-gov-primary"><i class="bi bi-search"></i> Apply</button>
      <button class="btn-gov btn-gov-ghost" data-reset-filters><i class="bi bi-x-circle"></i> Reset</button>
    </div>
  </div>
</div>

{{-- Summary stat cards --}}
<div class="row g-3 mb-4 animate-in delay-2">
  <div class="col-md-3">
    <div class="stat-card green">
      <div class="stat-header"><div class="stat-icon green"><i class="bi bi-buildings"></i></div></div>
      <div class="stat-value" data-count="36">0</div>
      <div class="stat-label">Total Districts</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card teal">
      <div class="stat-header"><div class="stat-icon teal"><i class="bi bi-1-circle"></i></div></div>
      <div class="stat-value" data-count="6">0</div>
      <div class="stat-label">Tier 1 Districts</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card gold">
      <div class="stat-header"><div class="stat-icon gold"><i class="bi bi-2-circle"></i></div></div>
      <div class="stat-value" data-count="14">0</div>
      <div class="stat-label">Tier 2 Districts</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card danger">
      <div class="stat-header"><div class="stat-icon danger"><i class="bi bi-3-circle"></i></div></div>
      <div class="stat-value" data-count="16">0</div>
      <div class="stat-label">Tier 3 Districts</div>
    </div>
  </div>
</div>

{{-- Radar + Tier tables --}}
<div class="row g-3 animate-in delay-3">

  {{-- Radar chart --}}
  <div class="col-lg-4">
    <div class="card-ppmf h-100">
      <div class="card-ppmf-header">
        <div class="card-ppmf-title"><i class="bi bi-diagram-2"></i> Tier Comparison</div>
      </div>
      <div class="card-ppmf-body">
        <div class="chart-wrap" style="height:260px;"><canvas id="tierRadar"></canvas></div>
      </div>
    </div>
  </div>

  {{-- Tier tables --}}
  <div class="col-lg-8">
    <div class="card-ppmf">
      <div class="card-ppmf-header">
        <div class="card-ppmf-title"><i class="bi bi-layers"></i> District Rankings by Tier</div>
        <div class="card-ppmf-actions">
          <input type="text" placeholder="Search district…" style="height:30px;border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:0 10px;font-size:12px;font-family:var(--font);outline:none;">
        </div>
      </div>
      <div class="card-ppmf-body" style="padding:16px;">

        {{-- Tab buttons --}}
        <div class="tabs-ppmf" data-tabs>
          <button class="tab-btn active" data-tab-target="tier1Tab">
            <i class="bi bi-1-circle" style="margin-right:5px;"></i>Tier 1
          </button>
          <button class="tab-btn" data-tab-target="tier2Tab">
            <i class="bi bi-2-circle" style="margin-right:5px;"></i>Tier 2
          </button>
          <button class="tab-btn" data-tab-target="tier3Tab">
            <i class="bi bi-3-circle" style="margin-right:5px;"></i>Tier 3
          </button>
        </div>

        {{-- Tier 1 --}}
        <div id="tier1Tab" data-tab-pane>
          <table class="table-ppmf">
            <thead>
              <tr><th>Rank</th><th>District</th><th>Division</th><th>Score</th><th>Trend</th><th>Progress</th><th>Status</th></tr>
            </thead>
            <tbody>
              @php
              $tier1 = [
                [1,'Faisalabad','Faisalabad',88,'up'],
                [2,'Lahore','Lahore',85,'up'],
                [3,'Rawalpindi','Rawalpindi',81,'flat'],
                [4,'Gujranwala','Gujranwala',79,'up'],
                [5,'Multan','Multan',76,'down'],
                [6,'Sialkot','Gujranwala',74,'flat'],
              ];
              @endphp
              @foreach($tier1 as [$r,$d,$div,$s,$t])
              <tr>
                <td><div class="rank-badge {{ $r<=3?'rank-'.$r:'rank-n' }}">{{ $r }}</div></td>
                <td><strong>{{ $d }}</strong></td>
                <td style="color:var(--text-muted);font-size:12px;">{{ $div }}</td>
                <td><span style="font-size:13px;font-weight:700;color:var(--gov-green);">{{ $s }}%</span></td>
                <td><i class="bi {{ $t==='up'?'bi-arrow-up text-green':($t==='down'?'bi-arrow-down text-danger':'bi-dash text-muted') }}" style="font-size:15px;"></i></td>
                <td style="min-width:120px;"><div class="progress-ppmf"><div class="bar {{ $s>=80?'green':($s>=70?'teal':'gold') }}" data-progress="{{ $s }}"></div></div></td>
                <td><span class="badge-ppmf {{ $s>=80?'achieved':($s>=70?'pending':'critical') }}">{{ $s>=80?'On Track':($s>=70?'Moderate':'At Risk') }}</span></td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        {{-- Tier 2 --}}
        <div id="tier2Tab" data-tab-pane class="d-none">
          <table class="table-ppmf">
            <thead>
              <tr><th>Rank</th><th>District</th><th>Division</th><th>Score</th><th>Trend</th><th>Progress</th><th>Status</th></tr>
            </thead>
            <tbody>
              @php
              $tier2 = [
                [1,'Sargodha','Sargodha',72,'up'],[2,'Jhang','Faisalabad',69,'flat'],
                [3,'Bahawalnagar','Bahawalpur',68,'up'],[4,'Rahim Yar Khan','Bahawalpur',65,'down'],
                [5,'Jhelum','Rawalpindi',63,'flat'],[6,'Chakwal','Rawalpindi',61,'down'],
              ];
              @endphp
              @foreach($tier2 as [$r,$d,$div,$s,$t])
              <tr>
                <td><div class="rank-badge {{ $r<=3?'rank-'.$r:'rank-n' }}">{{ $r }}</div></td>
                <td><strong>{{ $d }}</strong></td>
                <td style="color:var(--text-muted);font-size:12px;">{{ $div }}</td>
                <td><span style="font-size:13px;font-weight:700;color:var(--gold);">{{ $s }}%</span></td>
                <td><i class="bi {{ $t==='up'?'bi-arrow-up text-green':($t==='down'?'bi-arrow-down text-danger':'bi-dash text-muted') }}" style="font-size:15px;"></i></td>
                <td style="min-width:120px;"><div class="progress-ppmf"><div class="bar {{ $s>=70?'gold':'warning' }}" data-progress="{{ $s }}"></div></div></td>
                <td><span class="badge-ppmf pending">Moderate</span></td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        {{-- Tier 3 --}}
        <div id="tier3Tab" data-tab-pane class="d-none">
          <table class="table-ppmf">
            <thead>
              <tr><th>Rank</th><th>District</th><th>Division</th><th>Score</th><th>Trend</th><th>Progress</th><th>Status</th></tr>
            </thead>
            <tbody>
              @php
              $tier3 = [
                [1,'Muzaffargarh','DG Khan',55,'flat'],[2,'Layyah','DG Khan',52,'down'],
                [3,'DG Khan','DG Khan',48,'down'],[4,'Rajanpur','DG Khan',44,'down'],
                [5,'Dera Ghazi','DG Khan',42,'down'],[6,'Mianwali','Sargodha',40,'flat'],
              ];
              @endphp
              @foreach($tier3 as [$r,$d,$div,$s,$t])
              <tr>
                <td><div class="rank-badge {{ $r<=3?'rank-'.$r:'rank-n' }}">{{ $r }}</div></td>
                <td><strong>{{ $d }}</strong></td>
                <td style="color:var(--text-muted);font-size:12px;">{{ $div }}</td>
                <td><span style="font-size:13px;font-weight:700;color:var(--danger);">{{ $s }}%</span></td>
                <td><i class="bi {{ $t==='up'?'bi-arrow-up text-green':($t==='down'?'bi-arrow-down text-danger':'bi-dash text-muted') }}" style="font-size:15px;"></i></td>
                <td style="min-width:120px;"><div class="progress-ppmf"><div class="bar danger" data-progress="{{ $s }}"></div></div></td>
                <td><span class="badge-ppmf critical">Critical</span></td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>
</div>

@endsection

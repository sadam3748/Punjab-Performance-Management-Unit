@extends('layouts.app')
@section('title','KPI Management')
@section('page_title','KPI Management')
@section('breadcrumb_parent','KPI')

@section('content')

<div class="page-title-bar animate-in">
  <div>
    <div class="page-title">KPI Management</div>
    <div class="page-subtitle">Define, manage and track all performance indicators</div>
  </div>
  <div class="page-title-actions">
    <button class="btn-gov btn-gov-ghost btn-gov-sm"><i class="bi bi-download"></i> Export</button>
    <a href="{{ route('kpi.create') }}" class="btn-gov btn-gov-primary btn-gov-sm">
      <i class="bi bi-plus-lg"></i> Add New KPI
    </a>
  </div>
</div>

{{-- Filter --}}
<div class="filter-panel animate-in delay-1">
  <div class="filter-panel-title"><i class="bi bi-funnel"></i> Filters</div>
  <div class="filter-row">
    <div class="filter-group">
      <label>Department</label>
      <select><option>All Departments</option><option>Health</option><option>Education</option><option>Agriculture</option><option>Finance</option><option>Works & Services</option></select>
    </div>
    <div class="filter-group">
      <label>Category</label>
      <select><option>All Categories</option><option>Service Delivery</option><option>Governance</option><option>Development</option><option>Revenue</option></select>
    </div>
    <div class="filter-group">
      <label>Frequency</label>
      <select><option>All</option><option>Weekly</option><option>Monthly</option><option>Quarterly</option></select>
    </div>
    <div class="filter-group">
      <label>Status</label>
      <select><option>All Status</option><option>Achieved</option><option>In Progress</option><option>Critical</option></select>
    </div>
    <div class="filter-group" style="max-width:200px;">
      <label>Search</label>
      <input type="text" placeholder="Search KPI name…">
    </div>
    <div class="filter-actions">
      <button class="btn-gov btn-gov-primary"><i class="bi bi-search"></i> Search</button>
      <button class="btn-gov btn-gov-ghost" data-reset-filters><i class="bi bi-x-circle"></i> Reset</button>
    </div>
  </div>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4 animate-in delay-2">
  <div class="col-md-3"><div class="stat-card green">
    <div class="stat-header"><div class="stat-icon green"><i class="bi bi-list-check"></i></div></div>
    <div class="stat-value" data-count="234">0</div><div class="stat-label">Total KPIs</div>
  </div></div>
  <div class="col-md-3"><div class="stat-card teal">
    <div class="stat-header"><div class="stat-icon teal"><i class="bi bi-check2-all"></i></div></div>
    <div class="stat-value" data-count="148">0</div><div class="stat-label">Achieved</div>
  </div></div>
  <div class="col-md-3"><div class="stat-card gold">
    <div class="stat-header"><div class="stat-icon gold"><i class="bi bi-hourglass-split"></i></div></div>
    <div class="stat-value" data-count="62">0</div><div class="stat-label">In Progress</div>
  </div></div>
  <div class="col-md-3"><div class="stat-card danger">
    <div class="stat-header"><div class="stat-icon danger"><i class="bi bi-x-circle"></i></div></div>
    <div class="stat-value" data-count="24">0</div><div class="stat-label">Critical</div>
  </div></div>
</div>

{{-- KPI Table --}}
<div class="card-ppmf animate-in delay-3">
  <div class="card-ppmf-header">
    <div class="card-ppmf-title"><i class="bi bi-table"></i> KPI List</div>
    <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:var(--text-muted);">
      Showing 10 of 234 records
    </div>
  </div>
  <div style="overflow-x:auto;">
    <table class="table-ppmf">
      <thead>
        <tr>
          <th>#</th><th>KPI Code</th><th>KPI Name</th><th>Department</th>
          <th>Target</th><th>Achieved</th><th>Score</th><th>Freq.</th>
          <th>Status</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @php
        $kpis = [
          ['H-001','Immunization Coverage (%)','Health',95,91,96,'W','achieved'],
          ['H-002','Lady Health Workers Active','Health',4500,4312,96,'M','achieved'],
          ['E-001','Primary Enrolment Rate (%)','Education',90,84,93,'M','achieved'],
          ['E-002','Teacher Attendance (%)','Education',90,86,96,'W','achieved'],
          ['A-001','Wheat Procurement (MT)','Agriculture',1200000,980000,82,'M','pending'],
          ['F-001','Revenue Collection (PKR Cr)','Finance',8500,7200,85,'M','achieved'],
          ['P-001','Police Response Time (min)','Police',15,22,73,'W','pending'],
          ['W-001','Road Repair Completion (%)','Works',80,55,69,'M','pending'],
          ['R-001','Land Record Digitization (%)','Revenue',70,41,59,'M','critical'],
          ['V-001','Voter Registration Updates','Others',100,87,87,'Q','achieved'],
        ];
        @endphp
        @foreach($kpis as $i=>[$code,$name,$dept,$target,$actual,$score,$freq,$status])
        <tr>
          <td style="color:var(--text-muted);font-size:12px;">{{ $i+1 }}</td>
          <td><code style="background:var(--bg);padding:2px 7px;border-radius:5px;font-size:11.5px;font-weight:600;color:var(--gov-green);">{{ $code }}</code></td>
          <td><strong style="font-size:13px;">{{ $name }}</strong></td>
          <td style="font-size:12.5px;">{{ $dept }}</td>
          <td style="font-variant-numeric:tabular-nums;">{{ number_format($target) }}</td>
          <td style="font-variant-numeric:tabular-nums;">{{ number_format($actual) }}</td>
          <td>
            <div style="display:flex;align-items:center;gap:6px;">
              <div class="progress-ppmf" style="width:55px;">
                <div class="bar {{ $status==='achieved'?'green':($status==='pending'?'gold':'danger') }}" data-progress="{{ $score }}"></div>
              </div>
              <span style="font-size:12px;font-weight:700;">{{ $score }}%</span>
            </div>
          </td>
          <td><span class="badge-ppmf neutral">{{ $freq==='W'?'Weekly':($freq==='M'?'Monthly':'Quarterly') }}</span></td>
          <td><span class="badge-ppmf {{ $status }}">{{ ucfirst($status) }}</span></td>
          <td>
            <div style="display:flex;gap:4px;">
              <button class="btn-icon" title="View"><i class="bi bi-eye"></i></button>
              <button class="btn-icon" title="Edit"><i class="bi bi-pencil"></i></button>
              <button class="btn-icon danger" title="Delete"><i class="bi bi-trash"></i></button>
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  {{-- Pagination --}}
  <div style="padding:14px 20px;display:flex;align-items:center;justify-content:between;border-top:1px solid var(--border-light);">
    <span style="font-size:12.5px;color:var(--text-muted);flex:1;">Showing 1–10 of 234 KPIs</span>
    <div style="display:flex;gap:4px;">
      <button class="btn-gov btn-gov-ghost btn-gov-sm" disabled><i class="bi bi-chevron-left"></i></button>
      <button class="btn-gov btn-gov-primary btn-gov-sm">1</button>
      <button class="btn-gov btn-gov-ghost btn-gov-sm">2</button>
      <button class="btn-gov btn-gov-ghost btn-gov-sm">3</button>
      <span style="padding:0 6px;line-height:30px;font-size:12px;color:var(--text-muted);">…</span>
      <button class="btn-gov btn-gov-ghost btn-gov-sm">24</button>
      <button class="btn-gov btn-gov-ghost btn-gov-sm"><i class="bi bi-chevron-right"></i></button>
    </div>
  </div>
</div>

@endsection

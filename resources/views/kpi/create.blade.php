@extends('layouts.app')
@section('title','KPI Data Entry')
@section('page_title','KPI Data Entry')
@section('breadcrumb_parent','KPI Management')

@section('content')

<div class="page-title-bar animate-in">
  <div>
    <div class="page-title">KPI Data Entry</div>
    <div class="page-subtitle">Add or update KPI records for performance monitoring</div>
  </div>
  <a href="{{ route('kpi.index') }}" class="btn-gov btn-gov-ghost btn-gov-sm">
    <i class="bi bi-arrow-left"></i> Back to KPI List
  </a>
</div>

<div class="row g-3 animate-in delay-1">

  {{-- Main Form --}}
  <div class="col-lg-8">
    <div class="card-ppmf">
      <div class="card-ppmf-header">
        <div class="card-ppmf-title"><i class="bi bi-pencil-square"></i> KPI Information</div>
      </div>
      <div class="card-ppmf-body">
        <form action="{{ route('kpi.store') }}" method="POST">
          @csrf

          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <div class="form-group-ppmf">
                <label>KPI Code <span style="color:var(--danger);">*</span></label>
                <input type="text" class="form-input-ppmf" placeholder="e.g. H-001" required>
              </div>
            </div>
            <div class="col-md-8">
              <div class="form-group-ppmf">
                <label>KPI Name <span style="color:var(--danger);">*</span></label>
                <input type="text" class="form-input-ppmf" placeholder="Enter full KPI name" required>
              </div>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <div class="form-group-ppmf">
                <label>Department <span style="color:var(--danger);">*</span></label>
                <select class="form-input-ppmf" style="background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238896a5' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E\");background-repeat:no-repeat;background-position:right 12px center;appearance:none;padding-right:32px;">
                  <option value="">-- Select Department --</option>
                  <option>Health</option><option>Education</option><option>Agriculture</option>
                  <option>Finance</option><option>Works & Services</option><option>Police</option>
                  <option>Revenue</option><option>Forest</option><option>Sports</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group-ppmf">
                <label>Category</label>
                <select class="form-input-ppmf" style="background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238896a5' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E\");background-repeat:no-repeat;background-position:right 12px center;appearance:none;padding-right:32px;">
                  <option>Service Delivery</option><option>Governance</option>
                  <option>Development</option><option>Revenue</option><option>Law & Order</option>
                </select>
              </div>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <div class="form-group-ppmf">
                <label>Annual Target <span style="color:var(--danger);">*</span></label>
                <input type="number" class="form-input-ppmf" placeholder="0" required>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group-ppmf">
                <label>Current Achievement</label>
                <input type="number" class="form-input-ppmf" placeholder="0">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group-ppmf">
                <label>Unit of Measurement</label>
                <select class="form-input-ppmf" style="background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238896a5' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E\");background-repeat:no-repeat;background-position:right 12px center;appearance:none;padding-right:32px;">
                  <option>Percentage (%)</option><option>Number (#)</option><option>PKR (Crore)</option>
                  <option>Metric Ton (MT)</option><option>Days</option><option>Minutes</option>
                </select>
              </div>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <div class="form-group-ppmf">
                <label>Reporting Frequency</label>
                <select class="form-input-ppmf" style="background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238896a5' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E\");background-repeat:no-repeat;background-position:right 12px center;appearance:none;padding-right:32px;">
                  <option>Weekly</option><option>Monthly</option><option>Quarterly</option><option>Annual</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group-ppmf">
                <label>Applicable Tier</label>
                <select class="form-input-ppmf" style="background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238896a5' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E\");background-repeat:no-repeat;background-position:right 12px center;appearance:none;padding-right:32px;">
                  <option>All Tiers</option><option>Tier 1 Only</option><option>Tier 2 Only</option><option>Tier 3 Only</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group-ppmf">
                <label>Weight (%)</label>
                <input type="number" class="form-input-ppmf" placeholder="e.g. 5" min="1" max="100">
              </div>
            </div>
          </div>

          <div class="form-group-ppmf mb-3">
            <label>Description / Methodology</label>
            <textarea class="form-input-ppmf" rows="3" style="height:auto;padding:10px 14px;resize:vertical;" placeholder="Describe how this KPI is measured and reported…"></textarea>
          </div>

          <div class="divider"></div>

          <div style="display:flex;gap:10px;justify-content:flex-end;">
            <a href="{{ route('kpi.index') }}" class="btn-gov btn-gov-ghost">Cancel</a>
            <button type="submit" class="btn-gov btn-gov-primary">
              <i class="bi bi-check-lg"></i> Save KPI
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>

  {{-- Side info --}}
  <div class="col-lg-4">

    <div class="card-ppmf mb-3">
      <div class="card-ppmf-header">
        <div class="card-ppmf-title"><i class="bi bi-info-circle"></i> Instructions</div>
      </div>
      <div class="card-ppmf-body" style="font-size:13px;color:var(--text-secondary);line-height:1.8;">
        <p style="margin-bottom:10px;"><i class="bi bi-dot" style="color:var(--gov-green);font-size:18px;"></i> Fill all required fields marked with <span style="color:var(--danger);">*</span></p>
        <p style="margin-bottom:10px;"><i class="bi bi-dot" style="color:var(--gov-green);font-size:18px;"></i> KPI Code must be unique</p>
        <p style="margin-bottom:10px;"><i class="bi bi-dot" style="color:var(--gov-green);font-size:18px;"></i> Achievement value will calculate score automatically</p>
        <p style="margin-bottom:10px;"><i class="bi bi-dot" style="color:var(--gov-green);font-size:18px;"></i> Select correct frequency for proper reporting</p>
        <p><i class="bi bi-dot" style="color:var(--gov-green);font-size:18px;"></i> Weight total across all KPIs should equal 100%</p>
      </div>
    </div>

    <div class="card-ppmf">
      <div class="card-ppmf-header">
        <div class="card-ppmf-title"><i class="bi bi-clock-history"></i> Recent Entries</div>
      </div>
      <div class="card-ppmf-body" style="padding:0;">
        @foreach([['H-001','Health','2 min ago'],['E-002','Education','1 hr ago'],['A-001','Agriculture','3 hrs ago']] as [$code,$dept,$time])
        <div style="display:flex;align-items:center;gap:12px;padding:10px 16px;border-bottom:1px solid var(--border-light);">
          <code style="background:var(--gov-green-light);color:var(--gov-green);padding:3px 8px;border-radius:5px;font-size:11px;font-weight:700;">{{ $code }}</code>
          <span style="flex:1;font-size:12.5px;">{{ $dept }}</span>
          <span style="font-size:11px;color:var(--text-muted);">{{ $time }}</span>
        </div>
        @endforeach
      </div>
    </div>

  </div>
</div>

@endsection

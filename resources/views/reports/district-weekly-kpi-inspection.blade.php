@extends('layouts.app')

@section('title', 'District Wise Weekly KPI Inspection Report | PPMF Portal')
@section('page_title', 'District Wise Weekly KPI Inspection Report')

@section('content')

<div class="page-actions-ppmf">
  <div>
    <h2>District Wise Weekly KPI Inspection Report</h2>
    <p>Weekly KPI inspection performance by district, indicator, period, month, and year.</p>
  </div>

  <div class="d-flex gap-2">
    <button class="btn btn-outline-success">
      <i class="bi bi-file-earmark-excel"></i> Export Excel
    </button>
    <button class="btn btn-outline-danger">
      <i class="bi bi-file-earmark-pdf"></i> Export PDF
    </button>
    <button class="btn btn-success">
      <i class="bi bi-printer"></i> Print
    </button>
  </div>
</div>

{{-- Filters --}}
<div class="filter-card-ppmf mb-4">
  <div class="filter-title">
    <i class="bi bi-funnel"></i> Report Filters
  </div>

  <div class="row g-3 align-items-end">
    <div class="col-md-3">
      <label class="form-label">KPI / Indicator</label>
      <select class="form-select">
        <option selected>Price of Roti</option>
        <option>Cleanliness</option>
        <option>Encroachment</option>
        <option>Stray Dogs</option>
        <option>Manhole Covers</option>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label">Frequency</label>
      <select class="form-select">
        <option selected>Weekly</option>
        <option>Monthly</option>
        <option>Quarterly</option>
        <option>Yearly</option>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label">Period</label>
      <select class="form-select">
        <option selected>30 Apr - 06 May</option>
        <option>23 Apr - 29 Apr</option>
        <option>16 Apr - 22 Apr</option>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label">Month</label>
      <select class="form-select">
        <option selected>May</option>
        <option>April</option>
        <option>March</option>
      </select>
    </div>

    <div class="col-md-1">
      <label class="form-label">Year</label>
      <select class="form-select">
        <option selected>2026</option>
        <option>2025</option>
        <option>2024</option>
      </select>
    </div>

    <div class="col-md-2 d-flex gap-2">
      <button class="btn btn-success flex-fill">
        <i class="bi bi-search"></i> Apply
      </button>
      <button class="btn btn-outline-secondary">
        <i class="bi bi-x-circle"></i>
      </button>
    </div>
  </div>
</div>

{{-- KPI Summary Cards --}}
<div class="row g-3 mb-4">
  <div class="col-xl-4 col-md-6">
    <div class="kpi-inspection-card">
      <div class="kpi-inspection-value text-success">77</div>
      <div class="kpi-inspection-title">
        DCs twice Weekly Review with all PCMs, Food Department and special branch about enforcement of Rate of Roti.
      </div>
    </div>
  </div>

  <div class="col-xl-4 col-md-6">
    <div class="kpi-inspection-card">
      <div class="kpi-inspection-value text-warning">0</div>
      <div class="kpi-inspection-title">
        Inspections of Tandoors to be conducted by ACs/PCMs daily as per tier wise targets.
      </div>
    </div>
  </div>

  <div class="col-xl-4 col-md-6">
    <div class="kpi-inspection-card">
      <div class="kpi-inspection-value text-primary">0</div>
      <div class="kpi-inspection-title">
        Special Coverage and Mobility Index for ACs/PCMs.
      </div>
    </div>
  </div>

  <div class="col-xl-4 col-md-6">
    <div class="kpi-inspection-card">
      <div class="kpi-inspection-value text-danger">0</div>
      <div class="kpi-inspection-title">
        Imposition of Fine on violations (Over Price, Weight, Non availability of Roti) at least 15% of visits.
      </div>
    </div>
  </div>

  <div class="col-xl-4 col-md-6">
    <div class="kpi-inspection-card">
      <div class="kpi-inspection-value text-info">0</div>
      <div class="kpi-inspection-title">
        Action taken on the complaints by the citizen.
      </div>
    </div>
  </div>
</div>

{{-- Chart --}}
<div class="card-ppmf mb-4">
  <div class="card-ppmf-header">
    <div>
      <div class="card-ppmf-title">
        <i class="bi bi-bar-chart-line"></i> Weekly KPI Inspection Overview
      </div>
      <div class="card-ppmf-subtitle">
        Comparison of selected KPI indicators for the current reporting period.
      </div>
    </div>
  </div>

  <div class="card-ppmf-body">
    <canvas id="weeklyKpiInspectionChart" height="95"></canvas>
  </div>
</div>

{{-- Report Table --}}
<div class="card-ppmf">
  <div class="card-ppmf-header">
    <div>
      <div class="card-ppmf-title">
        <i class="bi bi-table"></i> District Wise KPI Inspection Data
      </div>
      <div class="card-ppmf-subtitle">
        District-wise status of weekly KPI inspection indicators.
      </div>
    </div>

    <div class="d-flex align-items-center gap-2">
      <select class="form-select form-select-sm" style="width: 90px;">
        <option>10</option>
        <option selected>25</option>
        <option>50</option>
      </select>

      <div class="position-relative">
        <i class="bi bi-search position-absolute" style="left: 11px; top: 8px; color: var(--text-muted);"></i>
        <input type="text" class="form-control form-control-sm" style="padding-left: 32px; width: 240px;" placeholder="Search district...">
      </div>
    </div>
  </div>

  <div class="card-ppmf-body p-0">
    <div class="table-responsive">
      <table class="table table-ppmf align-middle mb-0">
        <thead>
          <tr>
            <th>District</th>
            <th>DCs Weekly Review</th>
            <th>Tandoor Inspections</th>
            <th>Mobility Index</th>
            <th>Fine on Violations</th>
            <th>Citizen Complaints Action</th>
            <th>Score</th>
            <th>Status</th>
          </tr>
        </thead>

        <tbody>
          <tr>
            <td><strong>Faisalabad</strong></td>
            <td><span class="metric-pill success">18</span></td>
            <td><span class="metric-pill warning">04</span></td>
            <td><span class="metric-pill success">12</span></td>
            <td><span class="metric-pill warning">03</span></td>
            <td><span class="metric-pill success">08</span></td>
            <td><strong class="text-success">88%</strong></td>
            <td><span class="badge bg-success-subtle text-success">On Track</span></td>
          </tr>

          <tr>
            <td><strong>Lahore</strong></td>
            <td><span class="metric-pill success">16</span></td>
            <td><span class="metric-pill warning">03</span></td>
            <td><span class="metric-pill success">10</span></td>
            <td><span class="metric-pill danger">01</span></td>
            <td><span class="metric-pill success">07</span></td>
            <td><strong class="text-success">82%</strong></td>
            <td><span class="badge bg-success-subtle text-success">Good</span></td>
          </tr>

          <tr>
            <td><strong>Multan</strong></td>
            <td><span class="metric-pill warning">11</span></td>
            <td><span class="metric-pill danger">00</span></td>
            <td><span class="metric-pill warning">06</span></td>
            <td><span class="metric-pill danger">00</span></td>
            <td><span class="metric-pill warning">04</span></td>
            <td><strong class="text-warning">64%</strong></td>
            <td><span class="badge bg-warning-subtle text-warning">Average</span></td>
          </tr>

          <tr>
            <td><strong>Rawalpindi</strong></td>
            <td><span class="metric-pill success">14</span></td>
            <td><span class="metric-pill warning">02</span></td>
            <td><span class="metric-pill success">09</span></td>
            <td><span class="metric-pill warning">02</span></td>
            <td><span class="metric-pill success">06</span></td>
            <td><strong class="text-success">78%</strong></td>
            <td><span class="badge bg-success-subtle text-success">Good</span></td>
          </tr>

          <tr>
            <td><strong>D. G. Khan</strong></td>
            <td><span class="metric-pill warning">08</span></td>
            <td><span class="metric-pill danger">00</span></td>
            <td><span class="metric-pill warning">04</span></td>
            <td><span class="metric-pill danger">00</span></td>
            <td><span class="metric-pill warning">03</span></td>
            <td><strong class="text-danger">45%</strong></td>
            <td><span class="badge bg-danger-subtle text-danger">Needs Action</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card-ppmf-footer d-flex justify-content-between align-items-center p-3 border-top">
    <small class="text-muted">Showing district wise weekly KPI inspection report for Price of Roti.</small>

    <nav>
      <ul class="pagination pagination-sm mb-0">
        <li class="page-item disabled"><a class="page-link">Previous</a></li>
        <li class="page-item active"><a class="page-link" href="#">1</a></li>
        <li class="page-item"><a class="page-link" href="#">2</a></li>
        <li class="page-item"><a class="page-link" href="#">Next</a></li>
      </ul>
    </nav>
  </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const chartEl = document.getElementById('weeklyKpiInspectionChart');

  if (chartEl && window.Chart) {
    new Chart(chartEl, {
      type: 'bar',
      data: {
        labels: [
          'DC Review',
          'Tandoor Inspections',
          'Mobility Index',
          'Fine Violations',
          'Citizen Complaints'
        ],
        datasets: [{
          label: 'Current Period Value',
          data: [77, 0, 0, 0, 0],
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });
  }
});
</script>
@endpush

@extends('layouts.app')

@section('title', 'Petrol Pump Monitoring')
@section('page_title', 'Petrol Pump Monitoring')

@section('content')

<div class="page-actions-ppmf">
  <div>
    <h2>Petrol Pump Monitoring</h2>
    <p>Punjab-wide monitoring dashboard for registered petrol pumps, inspections, violations, actions requested, and fuel sale reports.</p>
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

{{-- Hero + KPI Cards --}}
<div class="row g-4 mb-4">
  <div class="col-xl-6">
    <div class="petrol-hero-card">
      <div class="petrol-hero-content">
        <span class="petrol-module-badge">
          <i class="bi bi-fuel-pump"></i> Government of the Punjab
        </span>

        <h3>Welcome back, Admin Petrol Pump!</h3>

        <p>
          A quick snapshot of registered petrol pumps, inspections, action requested,
          actions taken, violations, and district-wise monitoring activity.
        </p>

        <div class="petrol-hero-actions">
          <button class="btn btn-success">
            <i class="bi bi-search"></i> View Inspections
          </button>

          <button class="btn btn-outline-success">
            <i class="bi bi-graph-up-arrow"></i> View Reports
          </button>
        </div>
      </div>

      <div class="petrol-hero-visual">
        <div class="fuel-drop drop-1"></div>
        <div class="fuel-drop drop-2"></div>
        <div class="fuel-drop drop-3"></div>
        <i class="bi bi-fuel-pump-fill"></i>
      </div>
    </div>
  </div>

  <div class="col-xl-6">
    <div class="row g-3">
      <div class="col-md-6">
        <div class="petrol-stat-card">
          <div class="petrol-stat-icon">
            <i class="bi bi-fuel-pump"></i>
          </div>

          <div>
            <span>Petrol Pumps</span>
            <strong>8,336</strong>
            <small>
              <b class="text-success">Active 7,652</b>
              <b class="text-warning ms-2">Inactive 543</b>
              <b class="text-danger ms-2">Closed 141</b>
            </small>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="petrol-stat-card">
          <div class="petrol-stat-icon info">
            <i class="bi bi-clipboard-check"></i>
          </div>

          <div>
            <span>Inspections</span>
            <strong>38,439</strong>
            <small>Today: <b>205</b> · Most inspections: <b>Pakpattan 49</b></small>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="petrol-stat-card">
          <div class="petrol-stat-icon success">
            <i class="bi bi-check2-square"></i>
          </div>

          <div>
            <span>Petrol Pumps Inspected</span>
            <strong>5,057</strong>
            <small>Unique pumps inspected</small>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="petrol-stat-card">
          <div class="petrol-stat-icon danger">
            <i class="bi bi-bullseye"></i>
          </div>

          <div>
            <span>Actions Requested</span>
            <strong>1,424</strong>
            <small>Violations flagged</small>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="petrol-stat-card">
          <div class="petrol-stat-icon warning">
            <i class="bi bi-lightning-charge"></i>
          </div>

          <div>
            <span>Actions Taken</span>
            <strong>338</strong>
            <small>Resolved cases</small>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="petrol-stat-card">
          <div class="petrol-stat-icon muted">
            <i class="bi bi-hourglass-split"></i>
          </div>

          <div>
            <span>Actions Pending</span>
            <strong>1,086</strong>
            <small>Awaiting resolution</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Violation Cards --}}
<div class="row g-3 mb-4">
  <div class="col-xl col-md-4">
    <div class="petrol-alert-card fuel">
      <div class="petrol-alert-icon">
        <i class="bi bi-droplet-half"></i>
      </div>
      <span>Fuel Shortage</span>
      <strong>197</strong>
      <small>Reported cases</small>
    </div>
  </div>

  <div class="col-xl col-md-4">
    <div class="petrol-alert-card stock">
      <div class="petrol-alert-icon">
        <i class="bi bi-box-seam"></i>
      </div>
      <span>Out of Stock</span>
      <strong>230</strong>
      <small>Inspection findings</small>
    </div>
  </div>

  <div class="col-xl col-md-4">
    <div class="petrol-alert-card queue">
      <div class="petrol-alert-icon">
        <i class="bi bi-collection"></i>
      </div>
      <span>Pumps with Queues</span>
      <strong>541</strong>
      <small>Queue observations</small>
    </div>
  </div>

  <div class="col-xl col-md-4">
    <div class="petrol-alert-card pricing">
      <div class="petrol-alert-icon">
        <i class="bi bi-tag"></i>
      </div>
      <span>Over Pricing</span>
      <strong>82</strong>
      <small>Price violation cases</small>
    </div>
  </div>

  <div class="col-xl col-md-4">
    <div class="petrol-alert-card hoarding">
      <div class="petrol-alert-icon">
        <i class="bi bi-truck"></i>
      </div>
      <span>Fuel Hoarding</span>
      <strong>308</strong>
      <small>Suspected cases</small>
    </div>
  </div>
</div>

{{-- Charts --}}
<div class="row g-4 mb-4">
  <div class="col-xl-8">
    <div class="card-ppmf h-100">
      <div class="card-ppmf-header">
        <div>
          <div class="card-ppmf-title">
            <i class="bi bi-graph-up"></i> Inspection Trend
          </div>
          <div class="card-ppmf-subtitle">
            Petrol pump inspections, action requested and actions taken trend.
          </div>
        </div>

        <select class="form-select form-select-sm" style="width: 140px;">
          <option selected>Last 7 Days</option>
          <option>Last 30 Days</option>
          <option>Current Month</option>
        </select>
      </div>

      <div class="card-ppmf-body">
        <canvas id="petrolInspectionTrendChart" height="105"></canvas>
      </div>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="card-ppmf h-100">
      <div class="card-ppmf-header">
        <div>
          <div class="card-ppmf-title">
            <i class="bi bi-pie-chart"></i> Pump Status
          </div>
          <div class="card-ppmf-subtitle">
            Registered petrol pump status distribution.
          </div>
        </div>
      </div>

      <div class="card-ppmf-body">
        <canvas id="petrolPumpStatusChart" height="180"></canvas>
      </div>
    </div>
  </div>
</div>

{{-- Reports + District Table --}}
<div class="row g-4">
  <div class="col-xl-4">
    <div class="card-ppmf h-100">
      <div class="card-ppmf-header">
        <div>
          <div class="card-ppmf-title">
            <i class="bi bi-menu-button-wide"></i> Quick Reports
          </div>
          <div class="card-ppmf-subtitle">
            Access petrol pump monitoring reports.
          </div>
        </div>
      </div>

      <div class="card-ppmf-body">
        <div class="petrol-report-list">
          <a href="#">
            <i class="bi bi-person-lines-fill"></i>
            <div>
              <strong>User Wise Inspections</strong>
              <span>Inspection activity by user and role</span>
            </div>
            <i class="bi bi-chevron-right"></i>
          </a>

          <a href="#">
            <i class="bi bi-fuel-pump-fill"></i>
            <div>
              <strong>Petrol Pump Inspections</strong>
              <span>Pump-wise inspection report</span>
            </div>
            <i class="bi bi-chevron-right"></i>
          </a>

          <a href="#">
            <i class="bi bi-bar-chart"></i>
            <div>
              <strong>Petrol & Diesel Sale</strong>
              <span>Fuel sale and supply report</span>
            </div>
            <i class="bi bi-chevron-right"></i>
          </a>

          <a href="#">
            <i class="bi bi-building"></i>
            <div>
              <strong>Tehsil Wise Summary</strong>
              <span>Tehsil level performance summary</span>
            </div>
            <i class="bi bi-chevron-right"></i>
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-8">
    <div class="card-ppmf">
      <div class="card-ppmf-header">
        <div>
          <div class="card-ppmf-title">
            <i class="bi bi-table"></i> District Wise Petrol Pump Monitoring
          </div>
          <div class="card-ppmf-subtitle">
            District-level pump registration, inspection and action status.
          </div>
        </div>

        <div class="position-relative">
          <i class="bi bi-search position-absolute" style="left: 11px; top: 8px; color: var(--text-muted);"></i>
          <input type="text" class="form-control form-control-sm" style="padding-left: 32px; width: 240px;" placeholder="Search district...">
        </div>
      </div>

      <div class="card-ppmf-body p-0">
        <div class="table-responsive">
          <table class="table table-ppmf align-middle mb-0">
            <thead>
              <tr>
                <th>District</th>
                <th>Registered Pumps</th>
                <th>Inspections</th>
                <th>Actions Requested</th>
                <th>Actions Taken</th>
                <th>Pending</th>
                <th>Status</th>
              </tr>
            </thead>

            <tbody>
              <tr>
                <td><strong>Lahore</strong></td>
                <td>842</td>
                <td>4,280</td>
                <td>181</td>
                <td>62</td>
                <td>119</td>
                <td><span class="badge bg-warning-subtle text-warning">Needs Follow-up</span></td>
              </tr>

              <tr>
                <td><strong>Faisalabad</strong></td>
                <td>615</td>
                <td>3,910</td>
                <td>150</td>
                <td>51</td>
                <td>99</td>
                <td><span class="badge bg-warning-subtle text-warning">Needs Follow-up</span></td>
              </tr>

              <tr>
                <td><strong>Rawalpindi</strong></td>
                <td>520</td>
                <td>3,225</td>
                <td>115</td>
                <td>42</td>
                <td>73</td>
                <td><span class="badge bg-success-subtle text-success">Stable</span></td>
              </tr>

              <tr>
                <td><strong>Multan</strong></td>
                <td>488</td>
                <td>2,970</td>
                <td>108</td>
                <td>29</td>
                <td>79</td>
                <td><span class="badge bg-danger-subtle text-danger">High Pending</span></td>
              </tr>

              <tr>
                <td><strong>Gujranwala</strong></td>
                <td>452</td>
                <td>2,610</td>
                <td>93</td>
                <td>31</td>
                <td>62</td>
                <td><span class="badge bg-success-subtle text-success">Stable</span></td>
              </tr>

              <tr>
                <td><strong>Pakpattan</strong></td>
                <td>201</td>
                <td>1,340</td>
                <td>74</td>
                <td>18</td>
                <td>56</td>
                <td><span class="badge bg-warning-subtle text-warning">Today High Activity</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-ppmf-footer d-flex justify-content-between align-items-center p-3 border-top">
        <small class="text-muted">Showing district-wise petrol pump monitoring summary.</small>

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
  </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const trendChart = document.getElementById('petrolInspectionTrendChart');

  if (trendChart && window.Chart) {
    new Chart(trendChart, {
      type: 'line',
      data: {
        labels: ['05 May', '06 May', '07 May', '08 May', '09 May', '10 May', '11 May'],
        datasets: [
          {
            label: 'Inspections',
            data: [420, 510, 480, 610, 690, 740, 805],
            borderWidth: 3,
            tension: 0.35
          },
          {
            label: 'Actions Requested',
            data: [120, 140, 110, 150, 180, 205, 230],
            borderWidth: 3,
            tension: 0.35
          },
          {
            label: 'Actions Taken',
            data: [45, 52, 50, 67, 72, 80, 90],
            borderWidth: 3,
            tension: 0.35
          }
        ]
      },
      options: {
        responsive: true,
        interaction: {
          mode: 'index',
          intersect: false
        },
        plugins: {
          legend: {
            position: 'bottom'
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

  const statusChart = document.getElementById('petrolPumpStatusChart');

  if (statusChart && window.Chart) {
    new Chart(statusChart, {
      type: 'doughnut',
      data: {
        labels: ['Active', 'Inactive', 'Closed'],
        datasets: [{
          data: [7652, 543, 141],
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        cutout: '68%',
        plugins: {
          legend: {
            position: 'bottom'
          }
        }
      }
    });
  }
});
</script>
@endpush

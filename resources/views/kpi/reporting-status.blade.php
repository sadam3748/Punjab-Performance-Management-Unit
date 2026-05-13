@extends('layouts.app')

@section('title', 'KPI Reporting Status | PPMF Portal')
@section('page_title', 'KPI Reporting Status')

@section('content')

<div class="page-actions-ppmf">
  <div>
    <h2>KPI Reporting Status</h2>
    <p>District-wise KPI submission status for the selected reporting period.</p>
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
    <i class="bi bi-funnel"></i> Reporting Status Filters
  </div>

  <div class="row g-3 align-items-end">
    <div class="col-md-3">
      <label class="form-label">KPI Category</label>
      <select class="form-select">
        <option selected>All Categories</option>
        <option>Price Control</option>
        <option>Municipal Services</option>
        <option>Education</option>
        <option>Health</option>
        <option>Law & Order</option>
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

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="stat-card-ppmf border-success">
      <span>Total Districts</span>
      <strong>36</strong>
      <small>Punjab districts monitored</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-primary">
      <span>Submitted</span>
      <strong>36</strong>
      <small>Districts submitted report</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-warning">
      <span>Partially Submitted</span>
      <strong>12</strong>
      <small>Require verification</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-danger">
      <span>Pending</span>
      <strong>0</strong>
      <small>Districts not submitted</small>
    </div>
  </div>
</div>

{{-- Status Chart + Summary --}}
<div class="row g-4 mb-4">
  <div class="col-xl-8">
    <div class="card-ppmf h-100">
      <div class="card-ppmf-header">
        <div>
          <div class="card-ppmf-title">
            <i class="bi bi-bar-chart-line"></i> KPI Submission Overview
          </div>
          <div class="card-ppmf-subtitle">
            Submitted KPI count by district for selected period.
          </div>
        </div>
      </div>

      <div class="card-ppmf-body">
        <canvas id="kpiReportingStatusChart" height="105"></canvas>
      </div>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="card-ppmf h-100">
      <div class="card-ppmf-header">
        <div>
          <div class="card-ppmf-title">
            <i class="bi bi-clipboard-data"></i> Reporting Summary
          </div>
          <div class="card-ppmf-subtitle">
            Current week status snapshot.
          </div>
        </div>
      </div>

      <div class="card-ppmf-body">
        <div class="summary-stack">
          <div class="summary-item">
            <span>Reporting Period</span>
            <strong>30 Apr - 06 May</strong>
          </div>

          <div class="summary-item">
            <span>Expected Submissions</span>
            <strong>252</strong>
          </div>

          <div class="summary-item">
            <span>Received Submissions</span>
            <strong>252</strong>
          </div>

          <div class="summary-item">
            <span>Completion Rate</span>
            <strong>100%</strong>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Table --}}
<div class="card-ppmf">
  <div class="card-ppmf-header">
    <div>
      <div class="card-ppmf-title">
        <i class="bi bi-table"></i> District KPI Reporting Status
      </div>
      <div class="card-ppmf-subtitle">
        District-wise submitted count and current reporting status.
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
            <th style="width: 80px;">Sr. No.</th>
            <th>District</th>
            <th>Status</th>
            <th>Submitted</th>
            <th>Expected</th>
            <th>Completion</th>
            <th>Last Updated</th>
            <th>Remarks</th>
          </tr>
        </thead>

        <tbody>
          @php
            $districts = [
              ['Attock', 7, 7, 'Submitted'],
              ['Bahawalnagar', 7, 7, 'Submitted'],
              ['Bahawalpur', 7, 7, 'Submitted'],
              ['Bhakkar', 7, 7, 'Submitted'],
              ['Chakwal', 7, 7, 'Submitted'],
              ['Chiniot', 7, 7, 'Submitted'],
              ['D. G. Khan', 7, 7, 'Submitted'],
              ['Faisalabad', 7, 7, 'Submitted'],
              ['Gujranwala', 7, 7, 'Submitted'],
              ['Gujrat', 7, 7, 'Submitted'],
              ['Hafizabad', 7, 7, 'Submitted'],
              ['Jhang', 7, 7, 'Submitted'],
            ];
          @endphp

          @foreach($districts as $index => $district)
            @php
              $completion = round(($district[1] / $district[2]) * 100);
            @endphp

            <tr>
              <td>{{ $index + 1 }}</td>
              <td><strong>{{ $district[0] }}</strong></td>

              <td>
                <span class="status-chip-ppmf status-submitted">
                  <i class="bi bi-check-circle"></i> {{ $district[3] }}
                </span>
              </td>

              <td><strong>{{ $district[1] }}</strong></td>
              <td>{{ $district[2] }}</td>

              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="progress progress-sm flex-grow-1" style="min-width: 120px;">
                    <div class="progress-bar bg-success" style="width: {{ $completion }}%"></div>
                  </div>
                  <strong class="text-success">{{ $completion }}%</strong>
                </div>
              </td>

              <td>06 May, 2026</td>

              <td>
                <span class="badge bg-success-subtle text-success">Completed</span>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <div class="card-ppmf-footer d-flex justify-content-between align-items-center p-3 border-top">
    <small class="text-muted">Showing 1 to 12 of 36 districts</small>

    <nav>
      <ul class="pagination pagination-sm mb-0">
        <li class="page-item disabled"><a class="page-link">Previous</a></li>
        <li class="page-item active"><a class="page-link" href="#">1</a></li>
        <li class="page-item"><a class="page-link" href="#">2</a></li>
        <li class="page-item"><a class="page-link" href="#">3</a></li>
        <li class="page-item"><a class="page-link" href="#">Next</a></li>
      </ul>
    </nav>
  </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const chartEl = document.getElementById('kpiReportingStatusChart');

  if (chartEl && window.Chart) {
    new Chart(chartEl, {
      type: 'bar',
      data: {
        labels: [
          'Attock',
          'Bahawalnagar',
          'Bahawalpur',
          'Bhakkar',
          'Chakwal',
          'Chiniot',
          'D.G Khan',
          'Faisalabad',
          'Gujranwala',
          'Gujrat',
          'Hafizabad',
          'Jhang'
        ],
        datasets: [{
          label: 'Submitted KPIs',
          data: [7, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7],
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
            beginAtZero: true,
            max: 10
          }
        }
      }
    });
  }
});
</script>
@endpush

@extends('layouts.app')

@section('title', 'District Accumulative Report | PPMF Portal')
@section('page_title', 'District Accumulative Report')

@section('content')

<div class="page-actions-ppmf">
  <div>
    <h2>District Accumulative Report</h2>
    <p>Accumulative district score and ranking report across selected week range and indicator.</p>
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
    <i class="bi bi-funnel"></i> Accumulative Report Filters
  </div>

  <div class="row g-3 align-items-end">
    <div class="col-md-3">
      <label class="form-label">KPI / Indicator</label>
      <select class="form-select">
        <option selected>All</option>
        <option>Price of Roti</option>
        <option>Cleanliness</option>
        <option>Encroachment</option>
        <option>Manhole Covers</option>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label">Report Type</label>
      <select class="form-select">
        <option selected>Accumulative</option>
        <option>Weekly</option>
        <option>Monthly</option>
        <option>Quarterly</option>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label">Week Range</label>
      <select class="form-select">
        <option selected>1st to 109 Week</option>
        <option>1st to 100 Week</option>
        <option>50th to 109 Week</option>
        <option>Current Quarter</option>
      </select>
    </div>

    <div class="col-md-2">
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
      <span>Districts Covered</span>
      <strong>36</strong>
      <small>All Punjab districts</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-primary">
      <span>Top District</span>
      <strong style="font-size: 22px;">Murree</strong>
      <small>Highest accumulative score</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-warning">
      <span>Average Score</span>
      <strong>45.8</strong>
      <small>Across selected period</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-danger">
      <span>Low Score Districts</span>
      <strong>07</strong>
      <small>Need performance review</small>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  {{-- Chart --}}
  <div class="col-xl-8">
    <div class="card-ppmf h-100">
      <div class="card-ppmf-header">
        <div>
          <div class="card-ppmf-title">
            <i class="bi bi-bar-chart-line"></i> Top Districts by Accumulative Score
          </div>
          <div class="card-ppmf-subtitle">
            Score comparison for selected accumulative week range.
          </div>
        </div>
      </div>

      <div class="card-ppmf-body">
        <canvas id="districtAccumulativeChart" height="110"></canvas>
      </div>
    </div>
  </div>

  {{-- Performance Summary --}}
  <div class="col-xl-4">
    <div class="card-ppmf h-100">
      <div class="card-ppmf-header">
        <div>
          <div class="card-ppmf-title">
            <i class="bi bi-clipboard-data"></i> Performance Summary
          </div>
          <div class="card-ppmf-subtitle">
            Current accumulative report overview.
          </div>
        </div>
      </div>

      <div class="card-ppmf-body">
        <div class="summary-stack">
          <div class="summary-item">
            <span>Highest Score</span>
            <strong>48.1</strong>
          </div>

          <div class="summary-item">
            <span>Lowest Score</span>
            <strong>39.5</strong>
          </div>

          <div class="summary-item">
            <span>Score Range</span>
            <strong>8.6</strong>
          </div>

          <div class="summary-item">
            <span>Report Weeks</span>
            <strong>109</strong>
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
        <i class="bi bi-table"></i> District Accumulative Ranking
      </div>
      <div class="card-ppmf-subtitle">
        District-wise accumulative score and rank.
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
            <th style="width: 80px;">Rank</th>
            <th>District</th>
            <th>Division</th>
            <th>Score</th>
            <th>Performance</th>
            <th>Trend</th>
            <th>Status</th>
          </tr>
        </thead>

        <tbody>
          <tr>
            <td><span class="rank-badge rank-1">1</span></td>
            <td><strong>Murree</strong></td>
            <td>Rawalpindi</td>
            <td><strong class="text-success">48.1</strong></td>
            <td>
              <div class="progress progress-sm">
                <div class="progress-bar bg-success" style="width: 96%"></div>
              </div>
            </td>
            <td><span class="text-success"><i class="bi bi-arrow-up"></i> +2</span></td>
            <td><span class="badge bg-success-subtle text-success">Leading</span></td>
          </tr>

          <tr>
            <td><span class="rank-badge rank-2">2</span></td>
            <td><strong>Bahawalnagar</strong></td>
            <td>Bahawalpur</td>
            <td><strong class="text-success">48.0</strong></td>
            <td>
              <div class="progress progress-sm">
                <div class="progress-bar bg-success" style="width: 95%"></div>
              </div>
            </td>
            <td><span class="text-success"><i class="bi bi-arrow-up"></i> +1</span></td>
            <td><span class="badge bg-success-subtle text-success">Strong</span></td>
          </tr>

          <tr>
            <td><span class="rank-badge rank-3">3</span></td>
            <td><strong>Pakpattan</strong></td>
            <td>Sahiwal</td>
            <td><strong class="text-success">47.9</strong></td>
            <td>
              <div class="progress progress-sm">
                <div class="progress-bar bg-success" style="width: 94%"></div>
              </div>
            </td>
            <td><span class="text-muted"><i class="bi bi-dash"></i> 0</span></td>
            <td><span class="badge bg-success-subtle text-success">Good</span></td>
          </tr>

          <tr>
            <td><span class="rank-badge">4</span></td>
            <td><strong>M. B. Din</strong></td>
            <td>Gujranwala</td>
            <td><strong class="text-success">47.9</strong></td>
            <td>
              <div class="progress progress-sm">
                <div class="progress-bar bg-success" style="width: 94%"></div>
              </div>
            </td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i> -1</span></td>
            <td><span class="badge bg-success-subtle text-success">Good</span></td>
          </tr>

          <tr>
            <td><span class="rank-badge">5</span></td>
            <td><strong>Hafizabad</strong></td>
            <td>Gujranwala</td>
            <td><strong class="text-warning">47.8</strong></td>
            <td>
              <div class="progress progress-sm">
                <div class="progress-bar bg-warning" style="width: 92%"></div>
              </div>
            </td>
            <td><span class="text-success"><i class="bi bi-arrow-up"></i> +3</span></td>
            <td><span class="badge bg-warning-subtle text-warning">Stable</span></td>
          </tr>

          <tr>
            <td><span class="rank-badge">6</span></td>
            <td><strong>Nankana Sahib</strong></td>
            <td>Lahore</td>
            <td><strong class="text-warning">47.8</strong></td>
            <td>
              <div class="progress progress-sm">
                <div class="progress-bar bg-warning" style="width: 92%"></div>
              </div>
            </td>
            <td><span class="text-muted"><i class="bi bi-dash"></i> 0</span></td>
            <td><span class="badge bg-warning-subtle text-warning">Stable</span></td>
          </tr>

          <tr>
            <td><span class="rank-badge">7</span></td>
            <td><strong>Lodhran</strong></td>
            <td>Multan</td>
            <td><strong class="text-warning">47.6</strong></td>
            <td>
              <div class="progress progress-sm">
                <div class="progress-bar bg-warning" style="width: 90%"></div>
              </div>
            </td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i> -2</span></td>
            <td><span class="badge bg-warning-subtle text-warning">Watch</span></td>
          </tr>

          <tr>
            <td><span class="rank-badge">8</span></td>
            <td><strong>Rawalpindi</strong></td>
            <td>Rawalpindi</td>
            <td><strong class="text-warning">47.5</strong></td>
            <td>
              <div class="progress progress-sm">
                <div class="progress-bar bg-warning" style="width: 88%"></div>
              </div>
            </td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i> -4</span></td>
            <td><span class="badge bg-warning-subtle text-warning">Watch</span></td>
          </tr>

          <tr>
            <td><span class="rank-badge">9</span></td>
            <td><strong>Sahiwal</strong></td>
            <td>Sahiwal</td>
            <td><strong class="text-danger">47.4</strong></td>
            <td>
              <div class="progress progress-sm">
                <div class="progress-bar bg-danger" style="width: 84%"></div>
              </div>
            </td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i> -3</span></td>
            <td><span class="badge bg-danger-subtle text-danger">Needs Review</span></td>
          </tr>

          <tr>
            <td><span class="rank-badge">10</span></td>
            <td><strong>Vehari</strong></td>
            <td>Multan</td>
            <td><strong class="text-danger">47.4</strong></td>
            <td>
              <div class="progress progress-sm">
                <div class="progress-bar bg-danger" style="width: 84%"></div>
              </div>
            </td>
            <td><span class="text-muted"><i class="bi bi-dash"></i> 0</span></td>
            <td><span class="badge bg-danger-subtle text-danger">Needs Review</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card-ppmf-footer d-flex justify-content-between align-items-center p-3 border-top">
    <small class="text-muted">Showing accumulative ranking for selected week range.</small>

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
  const chartEl = document.getElementById('districtAccumulativeChart');

  if (chartEl && window.Chart) {
    new Chart(chartEl, {
      type: 'bar',
      data: {
        labels: ['Murree', 'Bahawalnagar', 'Pakpattan', 'M.B. Din', 'Hafizabad', 'Nankana Sahib', 'Lodhran', 'Rawalpindi'],
        datasets: [{
          label: 'Accumulative Score',
          data: [48.1, 48.0, 47.9, 47.9, 47.8, 47.8, 47.6, 47.5],
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
            beginAtZero: false,
            min: 45,
            max: 50
          }
        }
      }
    });
  }
});
</script>
@endpush

@extends('layouts.app')

@section('title', 'Division KPI Ranking Report | PPMF Portal')
@section('page_title', 'Division KPI Ranking Report')

@section('content')

<div class="page-actions-ppmf">
  <div>
    <h2>Division KPI Ranking Report</h2>
    <p>Division-wise KPI ranking based on selected indicator, period, month, and year.</p>
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
    <i class="bi bi-funnel"></i> Ranking Filters
  </div>

  <div class="row g-3 align-items-end">
    <div class="col-md-3">
      <label class="form-label">KPI / Indicator</label>
      <select class="form-select">
        <option selected>Price of Roti</option>
        <option>Cleanliness</option>
        <option>Encroachment</option>
        <option>Manhole Covers</option>
        <option>Stray Dogs</option>
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
      <span>Total Divisions</span>
      <strong>09</strong>
      <small>Included in current report</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-primary">
      <span>Top Division</span>
      <strong style="font-size: 22px;">Lahore</strong>
      <small>Highest KPI performance</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-warning">
      <span>Average Score</span>
      <strong>71%</strong>
      <small>Across all divisions</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-danger">
      <span>Needs Attention</span>
      <strong>02</strong>
      <small>Low performing divisions</small>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  {{-- Chart --}}
  <div class="col-xl-5">
    <div class="card-ppmf h-100">
      <div class="card-ppmf-header">
        <div>
          <div class="card-ppmf-title">
            <i class="bi bi-bar-chart"></i> Division Score Overview
          </div>
          <div class="card-ppmf-subtitle">
            KPI score comparison by division.
          </div>
        </div>
      </div>

      <div class="card-ppmf-body">
        <canvas id="divisionKpiRankingChart" height="230"></canvas>
      </div>
    </div>
  </div>

  {{-- Top 3 --}}
  <div class="col-xl-7">
    <div class="card-ppmf h-100">
      <div class="card-ppmf-header">
        <div>
          <div class="card-ppmf-title">
            <i class="bi bi-trophy"></i> Top Performing Divisions
          </div>
          <div class="card-ppmf-subtitle">
            Current period ranking leaders.
          </div>
        </div>
      </div>

      <div class="card-ppmf-body">
        <div class="ranking-highlight-list">
          <div class="ranking-highlight-item first">
            <div class="ranking-position">1</div>
            <div>
              <h6>Lahore</h6>
              <p>Strong compliance and reporting performance.</p>
            </div>
            <strong>88%</strong>
          </div>

          <div class="ranking-highlight-item second">
            <div class="ranking-position">2</div>
            <div>
              <h6>Faisalabad</h6>
              <p>Consistent KPI submission and field progress.</p>
            </div>
            <strong>84%</strong>
          </div>

          <div class="ranking-highlight-item third">
            <div class="ranking-position">3</div>
            <div>
              <h6>Rawalpindi</h6>
              <p>Improved weekly performance trend.</p>
            </div>
            <strong>79%</strong>
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
        <i class="bi bi-table"></i> Division KPI Ranking
      </div>
      <div class="card-ppmf-subtitle">
        Ranking table based on selected KPI and reporting period.
      </div>
    </div>

    <div class="d-flex align-items-center gap-2">
      <select class="form-select form-select-sm" style="width: 90px;">
        <option selected>10</option>
        <option>25</option>
        <option>50</option>
      </select>

      <div class="position-relative">
        <i class="bi bi-search position-absolute" style="left: 11px; top: 8px; color: var(--text-muted);"></i>
        <input type="text" class="form-control form-control-sm" style="padding-left: 32px; width: 240px;" placeholder="Search division...">
      </div>
    </div>
  </div>

  <div class="card-ppmf-body p-0">
    <div class="table-responsive">
      <table class="table table-ppmf align-middle mb-0">
        <thead>
          <tr>
            <th style="width: 80px;">Rank</th>
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
            <td><strong>Lahore</strong></td>
            <td><strong class="text-success">88%</strong></td>
            <td>
              <div class="progress progress-sm">
                <div class="progress-bar bg-success" style="width: 88%"></div>
              </div>
            </td>
            <td><span class="text-success"><i class="bi bi-arrow-up"></i> +4</span></td>
            <td><span class="badge bg-success-subtle text-success">On Track</span></td>
          </tr>

          <tr>
            <td><span class="rank-badge rank-2">2</span></td>
            <td><strong>Faisalabad</strong></td>
            <td><strong class="text-success">84%</strong></td>
            <td>
              <div class="progress progress-sm">
                <div class="progress-bar bg-success" style="width: 84%"></div>
              </div>
            </td>
            <td><span class="text-success"><i class="bi bi-arrow-up"></i> +2</span></td>
            <td><span class="badge bg-success-subtle text-success">Good</span></td>
          </tr>

          <tr>
            <td><span class="rank-badge rank-3">3</span></td>
            <td><strong>Rawalpindi</strong></td>
            <td><strong class="text-success">79%</strong></td>
            <td>
              <div class="progress progress-sm">
                <div class="progress-bar bg-success" style="width: 79%"></div>
              </div>
            </td>
            <td><span class="text-success"><i class="bi bi-arrow-up"></i> +1</span></td>
            <td><span class="badge bg-success-subtle text-success">Good</span></td>
          </tr>

          <tr>
            <td><span class="rank-badge">4</span></td>
            <td><strong>Multan</strong></td>
            <td><strong class="text-warning">72%</strong></td>
            <td>
              <div class="progress progress-sm">
                <div class="progress-bar bg-warning" style="width: 72%"></div>
              </div>
            </td>
            <td><span class="text-muted"><i class="bi bi-dash"></i> 0</span></td>
            <td><span class="badge bg-warning-subtle text-warning">Average</span></td>
          </tr>

          <tr>
            <td><span class="rank-badge">5</span></td>
            <td><strong>Gujranwala</strong></td>
            <td><strong class="text-warning">69%</strong></td>
            <td>
              <div class="progress progress-sm">
                <div class="progress-bar bg-warning" style="width: 69%"></div>
              </div>
            </td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i> -2</span></td>
            <td><span class="badge bg-warning-subtle text-warning">Watch</span></td>
          </tr>

          <tr>
            <td><span class="rank-badge">6</span></td>
            <td><strong>Sahiwal</strong></td>
            <td><strong class="text-warning">65%</strong></td>
            <td>
              <div class="progress progress-sm">
                <div class="progress-bar bg-warning" style="width: 65%"></div>
              </div>
            </td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i> -1</span></td>
            <td><span class="badge bg-warning-subtle text-warning">Average</span></td>
          </tr>

          <tr>
            <td><span class="rank-badge">7</span></td>
            <td><strong>Bahawalpur</strong></td>
            <td><strong class="text-danger">54%</strong></td>
            <td>
              <div class="progress progress-sm">
                <div class="progress-bar bg-danger" style="width: 54%"></div>
              </div>
            </td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i> -3</span></td>
            <td><span class="badge bg-danger-subtle text-danger">Needs Action</span></td>
          </tr>

          <tr>
            <td><span class="rank-badge">8</span></td>
            <td><strong>D. G. Khan</strong></td>
            <td><strong class="text-danger">48%</strong></td>
            <td>
              <div class="progress progress-sm">
                <div class="progress-bar bg-danger" style="width: 48%"></div>
              </div>
            </td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i> -4</span></td>
            <td><span class="badge bg-danger-subtle text-danger">Critical</span></td>
          </tr>

          <tr>
            <td><span class="rank-badge">9</span></td>
            <td><strong>Sargodha</strong></td>
            <td><strong class="text-danger">44%</strong></td>
            <td>
              <div class="progress progress-sm">
                <div class="progress-bar bg-danger" style="width: 44%"></div>
              </div>
            </td>
            <td><span class="text-muted"><i class="bi bi-dash"></i> 0</span></td>
            <td><span class="badge bg-danger-subtle text-danger">Low</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card-ppmf-footer d-flex justify-content-between align-items-center p-3 border-top">
    <small class="text-muted">Showing 1 to 9 of 9 divisions</small>

    <nav>
      <ul class="pagination pagination-sm mb-0">
        <li class="page-item disabled"><a class="page-link">Previous</a></li>
        <li class="page-item active"><a class="page-link" href="#">1</a></li>
        <li class="page-item disabled"><a class="page-link">Next</a></li>
      </ul>
    </nav>
  </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const chartEl = document.getElementById('divisionKpiRankingChart');

  if (chartEl && window.Chart) {
    new Chart(chartEl, {
      type: 'bar',
      data: {
        labels: ['Lahore', 'Faisalabad', 'Rawalpindi', 'Multan', 'Gujranwala', 'Sahiwal', 'Bahawalpur', 'D.G Khan', 'Sargodha'],
        datasets: [{
          label: 'Score',
          data: [88, 84, 79, 72, 69, 65, 54, 48, 44],
          borderWidth: 1
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          x: {
            beginAtZero: true,
            max: 100
          }
        }
      }
    });
  }
});
</script>
@endpush

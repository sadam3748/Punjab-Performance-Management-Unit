@extends('layouts.app')

@section('title', 'Category Wise District Score Report | PPMF Portal')
@section('page_title', 'Category Wise District Score Report')

@section('content')

<div class="page-actions-ppmf">
  <div>
    <h2>Category Wise District Score Report</h2>
    <p>District-wise KPI/category score report with ranking, score percentage, and performance status.</p>
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
    <i class="bi bi-funnel"></i> Category Score Filters
  </div>

  <div class="row g-3 align-items-end">
    <div class="col-md-3">
      <label class="form-label">KPI Category</label>
      <select class="form-select">
        <option selected>All Categories</option>
        <option>Price Control</option>
        <option>Municipal Services</option>
        <option>Cleanliness</option>
        <option>Encroachment</option>
        <option>Public Complaints</option>
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
      <small>Included in this report</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-primary">
      <span>Top District</span>
      <strong style="font-size: 22px;">Lahore</strong>
      <small>Highest category score</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-warning">
      <span>Average Score</span>
      <strong>74%</strong>
      <small>Across all districts</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-danger">
      <span>Low Performers</span>
      <strong>06</strong>
      <small>Below required benchmark</small>
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
            <i class="bi bi-bar-chart-line"></i> Category Wise District Score Overview
          </div>
          <div class="card-ppmf-subtitle">
            Top district scores for selected KPI category and period.
          </div>
        </div>
      </div>

      <div class="card-ppmf-body">
        <canvas id="categoryDistrictScoreChart" height="105"></canvas>
      </div>
    </div>
  </div>

  {{-- Category Summary --}}
  <div class="col-xl-4">
    <div class="card-ppmf h-100">
      <div class="card-ppmf-header">
        <div>
          <div class="card-ppmf-title">
            <i class="bi bi-grid-3x3-gap"></i> Category Summary
          </div>
          <div class="card-ppmf-subtitle">
            Selected category performance overview.
          </div>
        </div>
      </div>

      <div class="card-ppmf-body">
        <div class="summary-stack">
          <div class="summary-item">
            <span>Selected Category</span>
            <strong>All</strong>
          </div>

          <div class="summary-item">
            <span>Highest Score</span>
            <strong>92%</strong>
          </div>

          <div class="summary-item">
            <span>Lowest Score</span>
            <strong>45%</strong>
          </div>

          <div class="summary-item">
            <span>Score Gap</span>
            <strong>47%</strong>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

{{-- Report Table --}}
<div class="card-ppmf">
  <div class="card-ppmf-header">
    <div>
      <div class="card-ppmf-title">
        <i class="bi bi-table"></i> District Category Score Ranking
      </div>
      <div class="card-ppmf-subtitle">
        District ranking based on selected KPI category score.
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
            <th>Category</th>
            <th>Obtained Marks</th>
            <th>Total Marks</th>
            <th>Score</th>
            <th>Performance</th>
            <th>Status</th>
          </tr>
        </thead>

        <tbody>
          <tr>
            <td><span class="rank-badge rank-1">1</span></td>
            <td><strong>Lahore</strong></td>
            <td>Lahore</td>
            <td>Price Control</td>
            <td>46</td>
            <td>50</td>
            <td><strong class="text-success">92%</strong></td>
            <td>
              <div class="progress progress-sm">
                <div class="progress-bar bg-success" style="width: 92%"></div>
              </div>
            </td>
            <td><span class="badge bg-success-subtle text-success">Excellent</span></td>
          </tr>

          <tr>
            <td><span class="rank-badge rank-2">2</span></td>
            <td><strong>Faisalabad</strong></td>
            <td>Faisalabad</td>
            <td>Price Control</td>
            <td>44</td>
            <td>50</td>
            <td><strong class="text-success">88%</strong></td>
            <td>
              <div class="progress progress-sm">
                <div class="progress-bar bg-success" style="width: 88%"></div>
              </div>
            </td>
            <td><span class="badge bg-success-subtle text-success">On Track</span></td>
          </tr>

          <tr>
            <td><span class="rank-badge rank-3">3</span></td>
            <td><strong>Rawalpindi</strong></td>
            <td>Rawalpindi</td>
            <td>Price Control</td>
            <td>41</td>
            <td>50</td>
            <td><strong class="text-success">82%</strong></td>
            <td>
              <div class="progress progress-sm">
                <div class="progress-bar bg-success" style="width: 82%"></div>
              </div>
            </td>
            <td><span class="badge bg-success-subtle text-success">Good</span></td>
          </tr>

          <tr>
            <td><span class="rank-badge">4</span></td>
            <td><strong>Multan</strong></td>
            <td>Multan</td>
            <td>Municipal Services</td>
            <td>38</td>
            <td>50</td>
            <td><strong class="text-warning">76%</strong></td>
            <td>
              <div class="progress progress-sm">
                <div class="progress-bar bg-warning" style="width: 76%"></div>
              </div>
            </td>
            <td><span class="badge bg-warning-subtle text-warning">Average</span></td>
          </tr>

          <tr>
            <td><span class="rank-badge">5</span></td>
            <td><strong>Gujranwala</strong></td>
            <td>Gujranwala</td>
            <td>Cleanliness</td>
            <td>35</td>
            <td>50</td>
            <td><strong class="text-warning">70%</strong></td>
            <td>
              <div class="progress progress-sm">
                <div class="progress-bar bg-warning" style="width: 70%"></div>
              </div>
            </td>
            <td><span class="badge bg-warning-subtle text-warning">Watch</span></td>
          </tr>

          <tr>
            <td><span class="rank-badge">6</span></td>
            <td><strong>Bahawalpur</strong></td>
            <td>Bahawalpur</td>
            <td>Encroachment</td>
            <td>31</td>
            <td>50</td>
            <td><strong class="text-warning">62%</strong></td>
            <td>
              <div class="progress progress-sm">
                <div class="progress-bar bg-warning" style="width: 62%"></div>
              </div>
            </td>
            <td><span class="badge bg-warning-subtle text-warning">Average</span></td>
          </tr>

          <tr>
            <td><span class="rank-badge">7</span></td>
            <td><strong>D. G. Khan</strong></td>
            <td>D. G. Khan</td>
            <td>Public Complaints</td>
            <td>27</td>
            <td>50</td>
            <td><strong class="text-danger">54%</strong></td>
            <td>
              <div class="progress progress-sm">
                <div class="progress-bar bg-danger" style="width: 54%"></div>
              </div>
            </td>
            <td><span class="badge bg-danger-subtle text-danger">Needs Action</span></td>
          </tr>

          <tr>
            <td><span class="rank-badge">8</span></td>
            <td><strong>Sargodha</strong></td>
            <td>Sargodha</td>
            <td>Cleanliness</td>
            <td>23</td>
            <td>50</td>
            <td><strong class="text-danger">46%</strong></td>
            <td>
              <div class="progress progress-sm">
                <div class="progress-bar bg-danger" style="width: 46%"></div>
              </div>
            </td>
            <td><span class="badge bg-danger-subtle text-danger">Critical</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card-ppmf-footer d-flex justify-content-between align-items-center p-3 border-top">
    <small class="text-muted">Showing category wise district score report for selected period.</small>

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
  const chartEl = document.getElementById('categoryDistrictScoreChart');

  if (chartEl && window.Chart) {
    new Chart(chartEl, {
      type: 'bar',
      data: {
        labels: ['Lahore', 'Faisalabad', 'Rawalpindi', 'Multan', 'Gujranwala', 'Bahawalpur', 'D.G Khan', 'Sargodha'],
        datasets: [{
          label: 'Category Score',
          data: [92, 88, 82, 76, 70, 62, 54, 46],
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
            max: 100
          }
        }
      }
    });
  }
});
</script>
@endpush

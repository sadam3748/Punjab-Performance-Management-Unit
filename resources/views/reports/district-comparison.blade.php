@extends('layouts.app')

@section('title', 'District Comparison Report | PPMF Portal')
@section('page_title', 'District Comparison Report')

@section('content')

<div class="page-actions-ppmf">
  <div>
    <h2>District Comparison Report</h2>
    <p>Compare district score and rank across multiple reporting weeks for selected KPI indicator.</p>
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
    <i class="bi bi-funnel"></i> Comparison Filters
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
      <label class="form-label">Frequency</label>
      <select class="form-select">
        <option selected>Weekly</option>
        <option>Monthly</option>
        <option>Quarterly</option>
        <option>Yearly</option>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label">Current Period</label>
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
      <small>Compared in report</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-primary">
      <span>Top Current Score</span>
      <strong>91.14</strong>
      <small>Highest score in current period</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-warning">
      <span>Improved Districts</span>
      <strong>14</strong>
      <small>Score/rank improved</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-danger">
      <span>Dropped Districts</span>
      <strong>22</strong>
      <small>Score/rank declined</small>
    </div>
  </div>
</div>

{{-- Chart --}}
<div class="card-ppmf mb-4">
  <div class="card-ppmf-header">
    <div>
      <div class="card-ppmf-title">
        <i class="bi bi-graph-up-arrow"></i> District Score Comparison Trend
      </div>
      <div class="card-ppmf-subtitle">
        Selected districts score comparison across recent reporting weeks.
      </div>
    </div>

    <div class="d-flex gap-2">
      <span class="badge bg-success-subtle text-success">Improved</span>
      <span class="badge bg-danger-subtle text-danger">Declined</span>
    </div>
  </div>

  <div class="card-ppmf-body">
    <canvas id="districtComparisonChart" height="95"></canvas>
  </div>
</div>

{{-- Comparison Table --}}
<div class="card-ppmf">
  <div class="card-ppmf-header">
    <div>
      <div class="card-ppmf-title">
        <i class="bi bi-table"></i> District Weekly Score Comparison
      </div>
      <div class="card-ppmf-subtitle">
        Score and rank comparison for current and previous reporting weeks.
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
      <table class="table table-ppmf align-middle mb-0 comparison-table-ppmf">
        <thead>
          <tr>
            <th rowspan="2" style="min-width: 170px;">District</th>
            <th colspan="3" class="text-center">30 Apr, 2026 - 06 May, 2026</th>
            <th colspan="3" class="text-center">23 Apr, 2026 - 29 Apr, 2026</th>
            <th colspan="3" class="text-center">16 Apr, 2026 - 22 Apr, 2026</th>
            <th rowspan="2" class="text-center">Overall Trend</th>
          </tr>
          <tr>
            <th>Score</th>
            <th>Rank</th>
            <th>Change</th>
            <th>Score</th>
            <th>Rank</th>
            <th>Change</th>
            <th>Score</th>
            <th>Rank</th>
            <th>Change</th>
          </tr>
        </thead>

        <tbody>
          <tr>
            <td><strong>Attock</strong></td>
            <td><strong class="text-danger">0</strong></td>
            <td>0</td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i></span></td>
            <td><strong>86.07</strong></td>
            <td>32</td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i></span></td>
            <td><strong class="text-success">93.08</strong></td>
            <td>30</td>
            <td><span class="text-success"><i class="bi bi-arrow-up"></i></span></td>
            <td><span class="badge bg-danger-subtle text-danger">Declined</span></td>
          </tr>

          <tr>
            <td><strong>Bahawalnagar</strong></td>
            <td><strong class="text-danger">0</strong></td>
            <td>0</td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i></span></td>
            <td><strong>88.93</strong></td>
            <td>23</td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i></span></td>
            <td><strong class="text-success">93.94</strong></td>
            <td>25</td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i></span></td>
            <td><span class="badge bg-danger-subtle text-danger">Declined</span></td>
          </tr>

          <tr>
            <td><strong>Bahawalpur</strong></td>
            <td><strong class="text-danger">0</strong></td>
            <td>0</td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i></span></td>
            <td><strong>90.72</strong></td>
            <td>12</td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i></span></td>
            <td><strong class="text-success">97.62</strong></td>
            <td>4</td>
            <td><span class="text-success"><i class="bi bi-arrow-up"></i></span></td>
            <td><span class="badge bg-warning-subtle text-warning">Watch</span></td>
          </tr>

          <tr>
            <td><strong>Bhakkar</strong></td>
            <td><strong class="text-danger">0</strong></td>
            <td>0</td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i></span></td>
            <td><strong>85.32</strong></td>
            <td>34</td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i></span></td>
            <td><strong class="text-success">90.67</strong></td>
            <td>36</td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i></span></td>
            <td><span class="badge bg-danger-subtle text-danger">Declined</span></td>
          </tr>

          <tr>
            <td><strong>Chakwal</strong></td>
            <td><strong class="text-danger">0</strong></td>
            <td>0</td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i></span></td>
            <td><strong>80.23</strong></td>
            <td>38</td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i></span></td>
            <td><strong class="text-success">92.28</strong></td>
            <td>34</td>
            <td><span class="text-success"><i class="bi bi-arrow-up"></i></span></td>
            <td><span class="badge bg-warning-subtle text-warning">Mixed</span></td>
          </tr>

          <tr>
            <td><strong>Chiniot</strong></td>
            <td><strong class="text-danger">0</strong></td>
            <td>0</td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i></span></td>
            <td><strong>88.73</strong></td>
            <td>24</td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i></span></td>
            <td><strong class="text-success">96.91</strong></td>
            <td>10</td>
            <td><span class="text-success"><i class="bi bi-arrow-up"></i></span></td>
            <td><span class="badge bg-success-subtle text-success">Improved Earlier</span></td>
          </tr>

          <tr>
            <td><strong>D. G. Khan</strong></td>
            <td><strong class="text-danger">0</strong></td>
            <td>0</td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i></span></td>
            <td><strong>88.69</strong></td>
            <td>25</td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i></span></td>
            <td><strong class="text-success">93.80</strong></td>
            <td>28</td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i></span></td>
            <td><span class="badge bg-danger-subtle text-danger">Declined</span></td>
          </tr>

          <tr>
            <td><strong>Faisalabad</strong></td>
            <td><strong class="text-danger">0</strong></td>
            <td>0</td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i></span></td>
            <td><strong>85.91</strong></td>
            <td>33</td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i></span></td>
            <td><strong class="text-success">93.81</strong></td>
            <td>27</td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i></span></td>
            <td><span class="badge bg-warning-subtle text-warning">Needs Review</span></td>
          </tr>

          <tr>
            <td><strong>Gujranwala</strong></td>
            <td><strong class="text-danger">0</strong></td>
            <td>0</td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i></span></td>
            <td><strong>91.14</strong></td>
            <td>5</td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i></span></td>
            <td><strong class="text-success">96.20</strong></td>
            <td>14</td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i></span></td>
            <td><span class="badge bg-success-subtle text-success">Strong Previous</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card-ppmf-footer d-flex justify-content-between align-items-center p-3 border-top">
    <small class="text-muted">Showing weekly district comparison for selected periods.</small>

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
  const chartEl = document.getElementById('districtComparisonChart');

  if (chartEl && window.Chart) {
    new Chart(chartEl, {
      type: 'line',
      data: {
        labels: ['16 Apr - 22 Apr', '23 Apr - 29 Apr', '30 Apr - 06 May'],
        datasets: [
          {
            label: 'Gujranwala',
            data: [96.20, 91.14, 0],
            borderWidth: 3,
            tension: 0.35
          },
          {
            label: 'Bahawalpur',
            data: [97.62, 90.72, 0],
            borderWidth: 3,
            tension: 0.35
          },
          {
            label: 'Chiniot',
            data: [96.91, 88.73, 0],
            borderWidth: 3,
            tension: 0.35
          },
          {
            label: 'Attock',
            data: [93.08, 86.07, 0],
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

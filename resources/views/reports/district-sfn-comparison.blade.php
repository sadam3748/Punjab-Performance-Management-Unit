@extends('layouts.app')

@section('title', 'District Sixty Forty & Negative Ratio Comparison Report | PPMF Portal')
@section('page_title', 'District Sixty Forty & Negative Ratio Comparison Report')

@section('content')

<div class="page-actions-ppmf">
  <div>
    <h2>District Sixty Forty & Negative Ratio Comparison Report</h2>
    <p>Compare district score, rank, and trend across multiple reporting weeks using sixty-forty and negative ratio calculations.</p>
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
      <span>Best Previous Score</span>
      <strong>96.17</strong>
      <small>Gujranwala previous period</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-warning">
      <span>Improved Districts</span>
      <strong>13</strong>
      <small>Positive previous trend</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-danger">
      <span>Declined Districts</span>
      <strong>23</strong>
      <small>Need review in current period</small>
    </div>
  </div>
</div>

{{-- Chart --}}
<div class="card-ppmf mb-4">
  <div class="card-ppmf-header">
    <div>
      <div class="card-ppmf-title">
        <i class="bi bi-graph-up"></i> SFN Comparison Trend
      </div>
      <div class="card-ppmf-subtitle">
        Score movement for selected districts across three reporting weeks.
      </div>
    </div>

    <div class="d-flex gap-2">
      <span class="badge bg-success-subtle text-success">
        <i class="bi bi-arrow-up"></i> Improved
      </span>
      <span class="badge bg-danger-subtle text-danger">
        <i class="bi bi-arrow-down"></i> Declined
      </span>
    </div>
  </div>

  <div class="card-ppmf-body">
    <canvas id="districtSfnComparisonChart" height="95"></canvas>
  </div>
</div>

{{-- Table --}}
<div class="card-ppmf">
  <div class="card-ppmf-header">
    <div>
      <div class="card-ppmf-title">
        <i class="bi bi-table"></i> District SFN Weekly Comparison
      </div>
      <div class="card-ppmf-subtitle">
        District-wise score and rank comparison for selected reporting periods.
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
            <th rowspan="2" class="text-center">Overall Status</th>
          </tr>

          <tr>
            <th>Score</th>
            <th>Rank</th>
            <th>Trend</th>

            <th>Score</th>
            <th>Rank</th>
            <th>Trend</th>

            <th>Score</th>
            <th>Rank</th>
            <th>Trend</th>
          </tr>
        </thead>

        <tbody>
          <tr>
            <td><strong>Attock</strong></td>
            <td><strong class="text-danger">0</strong></td>
            <td>0</td>
            <td><span class="trend-down"><i class="bi bi-arrow-down"></i></span></td>

            <td><strong>75.76</strong></td>
            <td>34</td>
            <td><span class="trend-down"><i class="bi bi-arrow-down"></i></span></td>

            <td><strong class="text-success">88.75</strong></td>
            <td>25</td>
            <td><span class="trend-up"><i class="bi bi-arrow-up"></i></span></td>

            <td><span class="badge bg-danger-subtle text-danger">Declined</span></td>
          </tr>

          <tr>
            <td><strong>Bahawalnagar</strong></td>
            <td><strong class="text-danger">0</strong></td>
            <td>0</td>
            <td><span class="trend-down"><i class="bi bi-arrow-down"></i></span></td>

            <td><strong>84.61</strong></td>
            <td>22</td>
            <td><span class="trend-up"><i class="bi bi-arrow-up"></i></span></td>

            <td><strong class="text-success">84.26</strong></td>
            <td>33</td>
            <td><span class="trend-up"><i class="bi bi-arrow-up"></i></span></td>

            <td><span class="badge bg-warning-subtle text-warning">Mixed</span></td>
          </tr>

          <tr>
            <td><strong>Bahawalpur</strong></td>
            <td><strong class="text-danger">0</strong></td>
            <td>0</td>
            <td><span class="trend-down"><i class="bi bi-arrow-down"></i></span></td>

            <td><strong>90.09</strong></td>
            <td>10</td>
            <td><span class="trend-down"><i class="bi bi-arrow-down"></i></span></td>

            <td><strong class="text-success">95.40</strong></td>
            <td>8</td>
            <td><span class="trend-up"><i class="bi bi-arrow-up"></i></span></td>

            <td><span class="badge bg-warning-subtle text-warning">Watch</span></td>
          </tr>

          <tr>
            <td><strong>Bhakkar</strong></td>
            <td><strong class="text-danger">0</strong></td>
            <td>0</td>
            <td><span class="trend-down"><i class="bi bi-arrow-down"></i></span></td>

            <td><strong>76.70</strong></td>
            <td>33</td>
            <td><span class="trend-down"><i class="bi bi-arrow-down"></i></span></td>

            <td><strong class="text-success">78.55</strong></td>
            <td>37</td>
            <td><span class="trend-up"><i class="bi bi-arrow-up"></i></span></td>

            <td><span class="badge bg-danger-subtle text-danger">Needs Review</span></td>
          </tr>

          <tr>
            <td><strong>Chakwal</strong></td>
            <td><strong class="text-danger">0</strong></td>
            <td>0</td>
            <td><span class="trend-down"><i class="bi bi-arrow-down"></i></span></td>

            <td><strong>68.71</strong></td>
            <td>37</td>
            <td><span class="trend-down"><i class="bi bi-arrow-down"></i></span></td>

            <td><strong class="text-success">86.94</strong></td>
            <td>29</td>
            <td><span class="trend-up"><i class="bi bi-arrow-up"></i></span></td>

            <td><span class="badge bg-danger-subtle text-danger">Critical Drop</span></td>
          </tr>

          <tr>
            <td><strong>Chiniot</strong></td>
            <td><strong class="text-danger">0</strong></td>
            <td>0</td>
            <td><span class="trend-down"><i class="bi bi-arrow-down"></i></span></td>

            <td><strong>83.51</strong></td>
            <td>24</td>
            <td><span class="trend-down"><i class="bi bi-arrow-down"></i></span></td>

            <td><strong class="text-success">92.44</strong></td>
            <td>17</td>
            <td><span class="trend-up"><i class="bi bi-arrow-up"></i></span></td>

            <td><span class="badge bg-warning-subtle text-warning">Needs Monitoring</span></td>
          </tr>

          <tr>
            <td><strong>D. G. Khan</strong></td>
            <td><strong class="text-danger">0</strong></td>
            <td>0</td>
            <td><span class="trend-down"><i class="bi bi-arrow-down"></i></span></td>

            <td><strong>83.47</strong></td>
            <td>25</td>
            <td><span class="trend-down"><i class="bi bi-arrow-down"></i></span></td>

            <td><strong>89.51</strong></td>
            <td>24</td>
            <td><span class="trend-down"><i class="bi bi-arrow-down"></i></span></td>

            <td><span class="badge bg-danger-subtle text-danger">Declined</span></td>
          </tr>

          <tr>
            <td><strong>Faisalabad</strong></td>
            <td><strong class="text-danger">0</strong></td>
            <td>0</td>
            <td><span class="trend-down"><i class="bi bi-arrow-down"></i></span></td>

            <td><strong>78.63</strong></td>
            <td>31</td>
            <td><span class="trend-down"><i class="bi bi-arrow-down"></i></span></td>

            <td><strong class="text-success">87.61</strong></td>
            <td>27</td>
            <td><span class="trend-up"><i class="bi bi-arrow-up"></i></span></td>

            <td><span class="badge bg-warning-subtle text-warning">Watch</span></td>
          </tr>

          <tr>
            <td><strong>Gujranwala</strong></td>
            <td><strong class="text-danger">0</strong></td>
            <td>0</td>
            <td><span class="trend-down"><i class="bi bi-arrow-down"></i></span></td>

            <td><strong>90.53</strong></td>
            <td>8</td>
            <td><span class="trend-down"><i class="bi bi-arrow-down"></i></span></td>

            <td><strong class="text-success">96.17</strong></td>
            <td>4</td>
            <td><span class="trend-up"><i class="bi bi-arrow-up"></i></span></td>

            <td><span class="badge bg-success-subtle text-success">Strong Previous</span></td>
          </tr>

          <tr>
            <td><strong>Gujrat</strong></td>
            <td><strong class="text-danger">0</strong></td>
            <td>0</td>
            <td><span class="trend-down"><i class="bi bi-arrow-down"></i></span></td>

            <td><strong>78.77</strong></td>
            <td>30</td>
            <td><span class="trend-down"><i class="bi bi-arrow-down"></i></span></td>

            <td><strong class="text-success">83.20</strong></td>
            <td>34</td>
            <td><span class="trend-up"><i class="bi bi-arrow-up"></i></span></td>

            <td><span class="badge bg-warning-subtle text-warning">Average</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card-ppmf-footer d-flex justify-content-between align-items-center p-3 border-top">
    <small class="text-muted">Showing district sixty-forty and negative ratio comparison for selected periods.</small>

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
  const chartEl = document.getElementById('districtSfnComparisonChart');

  if (chartEl && window.Chart) {
    new Chart(chartEl, {
      type: 'line',
      data: {
        labels: ['16 Apr - 22 Apr', '23 Apr - 29 Apr', '30 Apr - 06 May'],
        datasets: [
          {
            label: 'Gujranwala',
            data: [96.17, 90.53, 0],
            borderWidth: 3,
            tension: 0.35
          },
          {
            label: 'Bahawalpur',
            data: [95.40, 90.09, 0],
            borderWidth: 3,
            tension: 0.35
          },
          {
            label: 'Chiniot',
            data: [92.44, 83.51, 0],
            borderWidth: 3,
            tension: 0.35
          },
          {
            label: 'Attock',
            data: [88.75, 75.76, 0],
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

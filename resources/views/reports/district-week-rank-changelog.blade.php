@extends('layouts.app')

@section('title', 'District Week Wise Score/Rank Changelog | PPMF Portal')
@section('page_title', 'District Week Wise Score/Rank Changelog')

@section('content')

<div class="page-actions-ppmf">
  <div>
    <h2>District Week Wise Score/Rank Changelog</h2>
    <p>Track weekly changes in district score, rank, obtained marks, and performance movement.</p>
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

{{-- Filter --}}
<div class="filter-card-ppmf mb-4">
  <div class="filter-title">
    <i class="bi bi-funnel"></i> Changelog Filters
  </div>

  <div class="row g-3 align-items-end">
    <div class="col-md-4">
      <label class="form-label">District</label>
      <select class="form-select">
        <option selected>Attock</option>
        <option>Lahore</option>
        <option>Faisalabad</option>
        <option>Multan</option>
        <option>Sargodha</option>
        <option>Rawalpindi</option>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label">Frequency</label>
      <select class="form-select">
        <option selected>Weekly</option>
        <option>Monthly</option>
        <option>Quarterly</option>
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

    <div class="col-md-2">
      <label class="form-label">Indicator</label>
      <select class="form-select">
        <option selected>Overall Score</option>
        <option>Obtained Marks</option>
        <option>Rank</option>
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
      <span>Current Score</span>
      <strong>75.76</strong>
      <small>Latest weekly score</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-primary">
      <span>Current Rank</span>
      <strong>34</strong>
      <small>Current district rank</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-warning">
      <span>Best Rank</span>
      <strong>20</strong>
      <small>Highest achieved rank</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-danger">
      <span>Rank Movement</span>
      <strong>-12</strong>
      <small>Compared with previous week</small>
    </div>
  </div>
</div>

{{-- Graph --}}
<div class="card-ppmf mb-4">
  <div class="card-ppmf-header">
    <div>
      <div class="card-ppmf-title">
        <i class="bi bi-graph-up"></i> District Wise Obtained Marks & Rank Graph
      </div>
      <div class="card-ppmf-subtitle">
        Weekly score and rank movement trend for selected district.
      </div>
    </div>

    <div class="d-flex gap-2">
      <span class="badge bg-success-subtle text-success">Score</span>
      <span class="badge bg-primary-subtle text-primary">Rank</span>
    </div>
  </div>

  <div class="card-ppmf-body">
    <canvas id="districtWeekRankChart" height="110"></canvas>
  </div>
</div>

{{-- Table --}}
<div class="card-ppmf">
  <div class="card-ppmf-header">
    <div>
      <div class="card-ppmf-title">
        <i class="bi bi-table"></i> Weekly Score/Rank Records
      </div>
      <div class="card-ppmf-subtitle">
        Historical week-wise score and rank details.
      </div>
    </div>

    <div class="position-relative">
      <i class="bi bi-search position-absolute" style="left: 11px; top: 8px; color: var(--text-muted);"></i>
      <input type="text" class="form-control form-control-sm" style="padding-left: 32px; width: 240px;" placeholder="Search week...">
    </div>
  </div>

  <div class="card-ppmf-body p-0">
    <div class="table-responsive">
      <table class="table table-ppmf align-middle mb-0">
        <thead>
          <tr>
            <th>Sr. No.</th>
            <th>Week</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Score</th>
            <th>Rank</th>
            <th>Change</th>
            <th>Status</th>
          </tr>
        </thead>

        <tbody>
          <tr>
            <td>44</td>
            <td><strong>112</strong></td>
            <td>23 Apr, 2026</td>
            <td>29 Apr, 2026</td>
            <td><strong class="text-warning">75.76</strong></td>
            <td><span class="rank-badge">34</span></td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i> -12</span></td>
            <td><span class="badge bg-warning-subtle text-warning">Average</span></td>
          </tr>

          <tr>
            <td>45</td>
            <td><strong>111</strong></td>
            <td>16 Apr, 2026</td>
            <td>22 Apr, 2026</td>
            <td><strong class="text-success">88.75</strong></td>
            <td><span class="rank-badge">22</span></td>
            <td><span class="text-success"><i class="bi bi-arrow-up"></i> +1</span></td>
            <td><span class="badge bg-success-subtle text-success">Good</span></td>
          </tr>

          <tr>
            <td>46</td>
            <td><strong>110</strong></td>
            <td>09 Apr, 2026</td>
            <td>15 Apr, 2026</td>
            <td><strong class="text-success">85.70</strong></td>
            <td><span class="rank-badge">23</span></td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i> -3</span></td>
            <td><span class="badge bg-success-subtle text-success">Good</span></td>
          </tr>

          <tr>
            <td>47</td>
            <td><strong>109</strong></td>
            <td>02 Apr, 2026</td>
            <td>08 Apr, 2026</td>
            <td><strong class="text-success">87.04</strong></td>
            <td><span class="rank-badge rank-3">20</span></td>
            <td><span class="text-success"><i class="bi bi-arrow-up"></i> +13</span></td>
            <td><span class="badge bg-success-subtle text-success">Improved</span></td>
          </tr>

          <tr>
            <td>48</td>
            <td><strong>108</strong></td>
            <td>26 Mar, 2026</td>
            <td>01 Apr, 2026</td>
            <td><strong class="text-warning">76.38</strong></td>
            <td><span class="rank-badge">33</span></td>
            <td><span class="text-muted"><i class="bi bi-dash"></i> 0</span></td>
            <td><span class="badge bg-warning-subtle text-warning">Stable</span></td>
          </tr>

          <tr>
            <td>49</td>
            <td><strong>107</strong></td>
            <td>19 Mar, 2026</td>
            <td>25 Mar, 2026</td>
            <td><strong class="text-warning">72.60</strong></td>
            <td><span class="rank-badge">35</span></td>
            <td><span class="text-danger"><i class="bi bi-arrow-down"></i> -2</span></td>
            <td><span class="badge bg-warning-subtle text-warning">Average</span></td>
          </tr>

          <tr>
            <td>50</td>
            <td><strong>106</strong></td>
            <td>12 Mar, 2026</td>
            <td>18 Mar, 2026</td>
            <td><strong class="text-success">81.45</strong></td>
            <td><span class="rank-badge">30</span></td>
            <td><span class="text-success"><i class="bi bi-arrow-up"></i> +5</span></td>
            <td><span class="badge bg-success-subtle text-success">Good</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card-ppmf-footer d-flex justify-content-between align-items-center p-3 border-top">
    <small class="text-muted">Showing week-wise records for Attock district</small>

    <nav>
      <ul class="pagination pagination-sm mb-0">
        <li class="page-item disabled">
          <a class="page-link">Previous</a>
        </li>
        <li class="page-item active">
          <a class="page-link" href="#">1</a>
        </li>
        <li class="page-item">
          <a class="page-link" href="#">2</a>
        </li>
        <li class="page-item">
          <a class="page-link" href="#">Next</a>
        </li>
      </ul>
    </nav>
  </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const chartEl = document.getElementById('districtWeekRankChart');

  if (chartEl && window.Chart) {
    new Chart(chartEl, {
      type: 'line',
      data: {
        labels: ['106', '107', '108', '109', '110', '111', '112'],
        datasets: [
          {
            label: 'Score',
            data: [81.45, 72.60, 76.38, 87.04, 85.70, 88.75, 75.76],
            borderWidth: 3,
            tension: 0.35,
            yAxisID: 'y'
          },
          {
            label: 'Rank',
            data: [30, 35, 33, 20, 23, 22, 34],
            borderWidth: 3,
            tension: 0.35,
            yAxisID: 'y1'
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
          },
          tooltip: {
            callbacks: {
              title: function(context) {
                return 'Week ' + context[0].label;
              }
            }
          }
        },
        scales: {
          y: {
            type: 'linear',
            position: 'left',
            beginAtZero: true,
            max: 100,
            title: {
              display: true,
              text: 'Score'
            }
          },
          y1: {
            type: 'linear',
            position: 'right',
            reverse: true,
            beginAtZero: false,
            min: 1,
            max: 40,
            title: {
              display: true,
              text: 'Rank'
            },
            grid: {
              drawOnChartArea: false
            }
          }
        }
      }
    });
  }
});
</script>
@endpush

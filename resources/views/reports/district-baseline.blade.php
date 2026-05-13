@extends('layouts.app')

@section('title', 'District Baseline Data Report | PPMF Portal')
@section('page_title', 'District Baseline Data Report')

@section('content')

<div class="page-actions-ppmf">
  <div>
    <h2>District Baseline Data Report</h2>
    <p>District-wise baseline profile covering population, education, health, roads, sewerage, and infrastructure indicators.</p>
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
    <i class="bi bi-funnel"></i> Baseline Data Filters
  </div>

  <div class="row g-3 align-items-end">
    <div class="col-md-3">
      <label class="form-label">Division</label>
      <select class="form-select">
        <option selected>All Divisions</option>
        <option>Lahore</option>
        <option>Rawalpindi</option>
        <option>Faisalabad</option>
        <option>Multan</option>
        <option>Bahawalpur</option>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label">District</label>
      <select class="form-select">
        <option selected>All Districts</option>
        <option>Attock</option>
        <option>Bahawalnagar</option>
        <option>Bahawalpur</option>
        <option>Bhakkar</option>
        <option>Chakwal</option>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label">Data Year</label>
      <select class="form-select">
        <option selected>2026</option>
        <option>2025</option>
        <option>2024</option>
        <option>2023</option>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label">Sector</label>
      <select class="form-select">
        <option selected>All Sectors</option>
        <option>Education</option>
        <option>Health</option>
        <option>Roads</option>
        <option>Sewerage</option>
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
      <small>Punjab baseline coverage</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-primary">
      <span>Total UCs</span>
      <strong>3,864</strong>
      <small>Union councils recorded</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-warning">
      <span>Total Population</span>
      <strong>127M</strong>
      <small>Census based district data</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-danger">
      <span>Infrastructure Indicators</span>
      <strong>15+</strong>
      <small>Education, health, roads, sewerage</small>
    </div>
  </div>
</div>

{{-- Visual Summary --}}
<div class="row g-4 mb-4">
  <div class="col-xl-8">
    <div class="card-ppmf h-100">
      <div class="card-ppmf-header">
        <div>
          <div class="card-ppmf-title">
            <i class="bi bi-bar-chart-line"></i> Baseline Data Overview
          </div>
          <div class="card-ppmf-subtitle">
            Comparison of selected baseline indicators across major districts.
          </div>
        </div>
      </div>

      <div class="card-ppmf-body">
        <canvas id="baselineDataChart" height="105"></canvas>
      </div>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="card-ppmf h-100">
      <div class="card-ppmf-header">
        <div>
          <div class="card-ppmf-title">
            <i class="bi bi-info-circle"></i> Baseline Summary
          </div>
          <div class="card-ppmf-subtitle">
            Key reference indicators.
          </div>
        </div>
      </div>

      <div class="card-ppmf-body">
        <div class="summary-stack">
          <div class="summary-item">
            <span>Highest Population</span>
            <strong>Bahawalpur</strong>
          </div>

          <div class="summary-item">
            <span>Highest UCs</span>
            <strong>Chakwal</strong>
          </div>

          <div class="summary-item">
            <span>Most Health Facilities</span>
            <strong>Bahawalnagar</strong>
          </div>

          <div class="summary-item">
            <span>Longest Rural Roads</span>
            <strong>Bhakkar</strong>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Baseline Table --}}
<div class="card-ppmf">
  <div class="card-ppmf-header">
    <div>
      <div class="card-ppmf-title">
        <i class="bi bi-table"></i> District Baseline Indicators
      </div>
      <div class="card-ppmf-subtitle">
        District-wise administrative, education, health and infrastructure baseline data.
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
      <table class="table table-ppmf align-middle mb-0 baseline-table-ppmf">
        <thead>
          <tr>
            <th>District</th>
            <th>Total UCs</th>
            <th>Population Census 2023</th>
            <th>Street Lights</th>
            <th>Primary Schools</th>
            <th>Middle Schools</th>
            <th>High Schools</th>
            <th>Higher Secondary Schools</th>
            <th>Degree Colleges</th>
            <th>Special Children Institutions</th>
            <th>BHUs</th>
            <th>RHCs</th>
            <th>Health Facilities</th>
            <th>Rural Roads Length</th>
            <th>Urban Roads Length</th>
            <th>Sewerage Lines Length</th>
          </tr>
        </thead>

        <tbody>
          @php
            $rows = [
              ['Attock', 77, '2,170,423', 8423, 775, 179, 240, 22, 15, 9, 62, 6, 74, '2496.13', '800.2', '3000'],
              ['Bahawalnagar', 141, '3,550,000', 1353, 1556, 326, 232, 18, 22, 5, 103, 10, 118, '450', '2955', '218'],
              ['Bahawalpur', 114, '4,284,964', 18142, 1185, 253, 198, 30, 25, 12, 75, 12, 94, '2761.29', '726.12', '5818'],
              ['Bhakkar', 70, '1,957,470', 4702, 929, 196, 134, 12, 22, 6, 40, 5, 49, '2849', '336.62', '192.8'],
              ['Chakwal', 145, '1,734,854', 7131, 666, 163, 287, 25, 22, 7, 63, 13, 81, '1632.51', '125', '95'],
              ['Chiniot', 43, '1,563,024', 9598, 509, 103, 78, 7, 11, 3, 37, 3, 43, '2000', '392', '58'],
              ['D. G. Khan', 99, '3,064,000', 6180, 1020, 211, 164, 18, 16, 4, 71, 7, 83, '2200', '508', '610'],
              ['Faisalabad', 189, '7,882,444', 22150, 1600, 340, 310, 44, 32, 15, 121, 19, 146, '1950', '1230', '2480'],
            ];
          @endphp

          @foreach($rows as $row)
            <tr>
              <td><strong>{{ $row[0] }}</strong></td>
              <td>{{ $row[1] }}</td>
              <td>{{ $row[2] }}</td>
              <td>{{ $row[3] }}</td>
              <td>{{ $row[4] }}</td>
              <td>{{ $row[5] }}</td>
              <td>{{ $row[6] }}</td>
              <td>{{ $row[7] }}</td>
              <td>{{ $row[8] }}</td>
              <td>{{ $row[9] }}</td>
              <td>{{ $row[10] }}</td>
              <td>{{ $row[11] }}</td>
              <td>{{ $row[12] }}</td>
              <td>{{ $row[13] }}</td>
              <td>{{ $row[14] }}</td>
              <td>{{ $row[15] }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <div class="card-ppmf-footer d-flex justify-content-between align-items-center p-3 border-top">
    <small class="text-muted">Showing baseline indicators for selected districts.</small>

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
  const chartEl = document.getElementById('baselineDataChart');

  if (chartEl && window.Chart) {
    new Chart(chartEl, {
      type: 'bar',
      data: {
        labels: ['Attock', 'Bahawalnagar', 'Bahawalpur', 'Bhakkar', 'Chakwal', 'Chiniot', 'Faisalabad'],
        datasets: [
          {
            label: 'Primary Schools',
            data: [775, 1556, 1185, 929, 666, 509, 1600],
            borderWidth: 1
          },
          {
            label: 'Health Facilities',
            data: [74, 118, 94, 49, 81, 43, 146],
            borderWidth: 1
          },
          {
            label: 'Total UCs',
            data: [77, 141, 114, 70, 145, 43, 189],
            borderWidth: 1
          }
        ]
      },
      options: {
        responsive: true,
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
});
</script>
@endpush

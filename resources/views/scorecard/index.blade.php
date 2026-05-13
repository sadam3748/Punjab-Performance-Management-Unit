@extends('layouts.app')

@section('title', 'CM Governance Scorecard Tier Wise | PPMF Portal')
@section('page_title', 'CM Governance Scorecard')

@section('content')

<div class="page-actions-ppmf">
  <div>
    <h2>CM Governance Scorecard — Tier Wise</h2>
    <p>District ranking by performance tier with scorecard filters, tier summary, ranking table, and Punjab map view.</p>
  </div>

  <div class="d-flex gap-2">
    <button class="btn btn-outline-success">
      <i class="bi bi-file-earmark-pdf"></i> Export PDF
    </button>
    <button class="btn btn-outline-success">
      <i class="bi bi-file-earmark-excel"></i> Export Excel
    </button>
    <button class="btn btn-success">
      <i class="bi bi-printer"></i> Print
    </button>
  </div>
</div>

{{-- Filters --}}
<div class="filter-card-ppmf mb-4">
  <div class="filter-title">
    <i class="bi bi-funnel"></i> Scorecard Filters
  </div>

  <div class="row g-3 align-items-end">
    <div class="col-md-2">
      <label class="form-label">Division</label>
      <select class="form-select">
        <option>All</option>
        <option>Lahore</option>
        <option>Rawalpindi</option>
        <option>Multan</option>
        <option>Faisalabad</option>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label">Frequency</label>
      <select class="form-select">
        <option>Weekly</option>
        <option>Monthly</option>
        <option>Quarterly</option>
        <option>Yearly</option>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label">Period</label>
      <select class="form-select">
        <option>30 Apr - 06 May</option>
        <option>23 Apr - 29 Apr</option>
        <option>16 Apr - 22 Apr</option>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label">Month</label>
      <select class="form-select">
        <option>May</option>
        <option>April</option>
        <option>March</option>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label">Year</label>
      <select class="form-select">
        <option>2026</option>
        <option>2025</option>
        <option>2024</option>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label">Indicator</label>
      <select class="form-select">
        <option>Victims Negative Marking</option>
        <option>Sixty Forty</option>
        <option>Overall Score</option>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label">Area Type</label>
      <select class="form-select">
        <option>District</option>
        <option>Division</option>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label">District</label>
      <select class="form-select">
        <option>All Districts</option>
        <option>Faisalabad</option>
        <option>Lahore</option>
        <option>Rawalpindi</option>
        <option>Multan</option>
      </select>
    </div>

    <div class="col-md-3 d-flex gap-2">
      <button class="btn btn-success flex-fill">
        <i class="bi bi-search"></i> Apply
      </button>
      <button class="btn btn-outline-secondary">
        <i class="bi bi-x-circle"></i> Reset
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
      <small>Included in current scorecard</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-success">
      <span>Tier 1 Districts</span>
      <strong>06</strong>
      <small>High performance group</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-warning">
      <span>Tier 2 Districts</span>
      <strong>14</strong>
      <small>Medium performance group</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-danger">
      <span>Tier 3 Districts</span>
      <strong>16</strong>
      <small>Requires attention</small>
    </div>
  </div>
</div>

<div class="row g-4">

  {{-- Left: Tier Ranking --}}
  <div class="col-xl-7">
    <div class="card-ppmf">
      <div class="card-ppmf-header">
        <div>
          <div class="card-ppmf-title">
            <i class="bi bi-layers"></i> District Rankings by Tier
          </div>
          <div class="card-ppmf-subtitle">
            Clean ranking view based on scorecard performance.
          </div>
        </div>

        <div class="position-relative">
          <i class="bi bi-search position-absolute" style="left: 11px; top: 8px; color: var(--text-muted);"></i>
          <input type="text" class="form-control form-control-sm" style="padding-left: 32px; width: 220px;" placeholder="Search district...">
        </div>
      </div>

      <div class="card-ppmf-body">

        <ul class="nav nav-pills ppmf-tabs mb-4" id="scorecardTierTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tier1-tab" data-bs-toggle="pill" data-bs-target="#tier1" type="button" role="tab">
              <i class="bi bi-1-circle"></i> Tier 1
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tier2-tab" data-bs-toggle="pill" data-bs-target="#tier2" type="button" role="tab">
              <i class="bi bi-2-circle"></i> Tier 2
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tier3-tab" data-bs-toggle="pill" data-bs-target="#tier3" type="button" role="tab">
              <i class="bi bi-3-circle"></i> Tier 3
            </button>
          </li>
        </ul>

        <div class="tab-content">

          {{-- Tier 1 --}}
          <div class="tab-pane fade show active" id="tier1" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-ppmf align-middle mb-0">
                <thead>
                  <tr>
                    <th style="width: 80px;">Rank</th>
                    <th>District</th>
                    <th>Division</th>
                    <th>Score</th>
                    <th>Trend</th>
                    <th>Progress</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><span class="rank-badge rank-1">1</span></td>
                    <td><strong>Faisalabad</strong></td>
                    <td>Faisalabad</td>
                    <td><strong class="text-success">88%</strong></td>
                    <td><span class="text-success"><i class="bi bi-arrow-up"></i> +4</span></td>
                    <td>
                      <div class="progress progress-sm">
                        <div class="progress-bar bg-success" style="width: 88%"></div>
                      </div>
                    </td>
                    <td><span class="badge bg-success-subtle text-success">On Track</span></td>
                  </tr>

                  <tr>
                    <td><span class="rank-badge rank-2">2</span></td>
                    <td><strong>Gujranwala</strong></td>
                    <td>Gujranwala</td>
                    <td><strong class="text-success">85%</strong></td>
                    <td><span class="text-success"><i class="bi bi-arrow-up"></i> +2</span></td>
                    <td>
                      <div class="progress progress-sm">
                        <div class="progress-bar bg-success" style="width: 85%"></div>
                      </div>
                    </td>
                    <td><span class="badge bg-success-subtle text-success">On Track</span></td>
                  </tr>

                  <tr>
                    <td><span class="rank-badge rank-3">3</span></td>
                    <td><strong>Lahore</strong></td>
                    <td>Lahore</td>
                    <td><strong class="text-success">82%</strong></td>
                    <td><span class="text-danger"><i class="bi bi-arrow-down"></i> -1</span></td>
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
                    <td><strong class="text-success">80%</strong></td>
                    <td><span class="text-success"><i class="bi bi-arrow-up"></i> +1</span></td>
                    <td>
                      <div class="progress progress-sm">
                        <div class="progress-bar bg-success" style="width: 80%"></div>
                      </div>
                    </td>
                    <td><span class="badge bg-success-subtle text-success">Good</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          {{-- Tier 2 --}}
          <div class="tab-pane fade" id="tier2" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-ppmf align-middle mb-0">
                <thead>
                  <tr>
                    <th style="width: 80px;">Rank</th>
                    <th>District</th>
                    <th>Division</th>
                    <th>Score</th>
                    <th>Trend</th>
                    <th>Progress</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><span class="rank-badge">1</span></td>
                    <td><strong>Attock</strong></td>
                    <td>Rawalpindi</td>
                    <td><strong class="text-warning">74%</strong></td>
                    <td><span class="text-success"><i class="bi bi-arrow-up"></i> +3</span></td>
                    <td>
                      <div class="progress progress-sm">
                        <div class="progress-bar bg-warning" style="width: 74%"></div>
                      </div>
                    </td>
                    <td><span class="badge bg-warning-subtle text-warning">Average</span></td>
                  </tr>

                  <tr>
                    <td><span class="rank-badge">2</span></td>
                    <td><strong>Bahawalpur</strong></td>
                    <td>Bahawalpur</td>
                    <td><strong class="text-warning">71%</strong></td>
                    <td><span class="text-muted"><i class="bi bi-dash"></i> 0</span></td>
                    <td>
                      <div class="progress progress-sm">
                        <div class="progress-bar bg-warning" style="width: 71%"></div>
                      </div>
                    </td>
                    <td><span class="badge bg-warning-subtle text-warning">Average</span></td>
                  </tr>

                  <tr>
                    <td><span class="rank-badge">3</span></td>
                    <td><strong>Jhang</strong></td>
                    <td>Faisalabad</td>
                    <td><strong class="text-warning">68%</strong></td>
                    <td><span class="text-danger"><i class="bi bi-arrow-down"></i> -2</span></td>
                    <td>
                      <div class="progress progress-sm">
                        <div class="progress-bar bg-warning" style="width: 68%"></div>
                      </div>
                    </td>
                    <td><span class="badge bg-warning-subtle text-warning">Watch</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          {{-- Tier 3 --}}
          <div class="tab-pane fade" id="tier3" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-ppmf align-middle mb-0">
                <thead>
                  <tr>
                    <th style="width: 80px;">Rank</th>
                    <th>District</th>
                    <th>Division</th>
                    <th>Score</th>
                    <th>Trend</th>
                    <th>Progress</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><span class="rank-badge">1</span></td>
                    <td><strong>Bhakkar</strong></td>
                    <td>Sargodha</td>
                    <td><strong class="text-danger">49%</strong></td>
                    <td><span class="text-danger"><i class="bi bi-arrow-down"></i> -4</span></td>
                    <td>
                      <div class="progress progress-sm">
                        <div class="progress-bar bg-danger" style="width: 49%"></div>
                      </div>
                    </td>
                    <td><span class="badge bg-danger-subtle text-danger">Low</span></td>
                  </tr>

                  <tr>
                    <td><span class="rank-badge">2</span></td>
                    <td><strong>Chakwal</strong></td>
                    <td>Rawalpindi</td>
                    <td><strong class="text-danger">46%</strong></td>
                    <td><span class="text-muted"><i class="bi bi-dash"></i> 0</span></td>
                    <td>
                      <div class="progress progress-sm">
                        <div class="progress-bar bg-danger" style="width: 46%"></div>
                      </div>
                    </td>
                    <td><span class="badge bg-danger-subtle text-danger">Needs Action</span></td>
                  </tr>

                  <tr>
                    <td><span class="rank-badge">3</span></td>
                    <td><strong>Hafizabad</strong></td>
                    <td>Gujranwala</td>
                    <td><strong class="text-danger">42%</strong></td>
                    <td><span class="text-danger"><i class="bi bi-arrow-down"></i> -1</span></td>
                    <td>
                      <div class="progress progress-sm">
                        <div class="progress-bar bg-danger" style="width: 42%"></div>
                      </div>
                    </td>
                    <td><span class="badge bg-danger-subtle text-danger">Critical</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  {{-- Right: Map + Chart --}}
  <div class="col-xl-5">

    <div class="card-ppmf mb-4">
      <div class="card-ppmf-header">
        <div>
          <div class="card-ppmf-title">
            <i class="bi bi-map"></i> Punjab Performance Map
          </div>
          <div class="card-ppmf-subtitle">
            District tier visualization placeholder.
          </div>
        </div>
      </div>

      <div class="card-ppmf-body">
        <div class="map-placeholder-ppmf">
          <i class="bi bi-map-fill"></i>
          <h5>Punjab District Map</h5>
          <p>Use this section later for interactive Highmaps/SVG map. Color districts by Tier 1, Tier 2, and Tier 3.</p>
        </div>
      </div>
    </div>

    <div class="card-ppmf">
      <div class="card-ppmf-header">
        <div>
          <div class="card-ppmf-title">
            <i class="bi bi-pie-chart"></i> Tier Distribution
          </div>
          <div class="card-ppmf-subtitle">
            Current scorecard tier spread.
          </div>
        </div>
      </div>

      <div class="card-ppmf-body">
        <canvas id="tierDistributionChart" height="220"></canvas>
      </div>
    </div>

  </div>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const chartEl = document.getElementById('tierDistributionChart');

  if (chartEl && window.Chart) {
    new Chart(chartEl, {
      type: 'doughnut',
      data: {
        labels: ['Tier 1', 'Tier 2', 'Tier 3'],
        datasets: [{
          data: [6, 14, 16]
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom'
          }
        },
        cutout: '65%'
      }
    });
  }
});
</script>
@endpush

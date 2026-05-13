@extends('layouts.app')

@section('title', 'District Sixty Forty Ratio, Negative & Victim Tier Wise Score Report | PPMF Portal')
@section('page_title', 'District Sixty Forty Ratio, Negative & Victim Tier Wise Score Report')

@section('content')

<div class="page-actions-ppmf">
  <div>
    <h2>District Sixty Forty Ratio, Negative & Victim Tier Wise Score Report</h2>
    <p>Tier-wise district performance report showing obtained score, rank, sixty-forty ratio, negative marking, victim marking, and final rank.</p>
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
    <i class="bi bi-funnel"></i> Tier Wise Report Filters
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
      <small>Across all tiers</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-primary">
      <span>Tier 1 Districts</span>
      <strong>06</strong>
      <small>High priority districts</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-warning">
      <span>Tier 2 Districts</span>
      <strong>18</strong>
      <small>Medium priority districts</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-danger">
      <span>Tier 3 Districts</span>
      <strong>17</strong>
      <small>General category districts</small>
    </div>
  </div>
</div>

{{-- Tier Tabs --}}
<div class="card-ppmf mb-4">
  <div class="card-ppmf-header">
    <div>
      <div class="card-ppmf-title">
        <i class="bi bi-layers"></i> Tier Wise Performance Overview
      </div>
      <div class="card-ppmf-subtitle">
        Select tier to review district-wise scoring, negative marking, victim marking, and final rank.
      </div>
    </div>
  </div>

  <div class="card-ppmf-body">
    <ul class="nav nav-tabs ppmf-tabs mb-4" id="tierReportTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tier1-tab" data-bs-toggle="tab" data-bs-target="#tier1" type="button" role="tab">
          <i class="bi bi-1-circle"></i> Tier 1
        </button>
      </li>

      <li class="nav-item" role="presentation">
        <button class="nav-link" id="tier2-tab" data-bs-toggle="tab" data-bs-target="#tier2" type="button" role="tab">
          <i class="bi bi-2-circle"></i> Tier 2
        </button>
      </li>

      <li class="nav-item" role="presentation">
        <button class="nav-link" id="tier3-tab" data-bs-toggle="tab" data-bs-target="#tier3" type="button" role="tab">
          <i class="bi bi-3-circle"></i> Tier 3
        </button>
      </li>
    </ul>

    <div class="tab-content" id="tierReportTabsContent">

      {{-- Tier 1 --}}
      <div class="tab-pane fade show active" id="tier1" role="tabpanel">
        <div class="tier-section-header">
          <div>
            <h5>Tier 1 Districts</h5>
            <p>High priority districts with tier-wise scoring and negative marking calculation.</p>
          </div>
          <span class="badge bg-success-subtle text-success">6 Districts</span>
        </div>

        <div class="table-responsive">
          <table class="table table-ppmf align-middle mb-0">
            <thead>
              <tr>
                <th>District</th>
                <th>Obtained Score</th>
                <th>Obtained Rank</th>
                <th>Sixty Forty Ratio Score</th>
                <th>Special Branch Negative Marking</th>
                <th>Score After Negative Marking</th>
                <th>No. of Weeks Victims Occur / No. of Victims</th>
                <th>Victim Negative Marking</th>
                <th>Score After Victim Negative Marking</th>
                <th>Final Rank</th>
                <th>Status</th>
              </tr>
            </thead>

            <tbody>
              @php
                $tier1 = ['Faisalabad', 'Gujranwala', 'Lahore', 'Multan', 'Rawalpindi', 'Sialkot'];
              @endphp

              @foreach($tier1 as $index => $district)
              <tr>
                <td><strong>{{ $district }}</strong></td>
                <td>0.00</td>
                <td>0</td>
                <td>0.00</td>
                <td>0</td>
                <td>0.00</td>
                <td>0</td>
                <td>0.00</td>
                <td>0.00</td>
                <td><span class="rank-badge">{{ $index + 1 }}</span></td>
                <td><span class="badge bg-warning-subtle text-warning">Pending Score</span></td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      {{-- Tier 2 --}}
      <div class="tab-pane fade" id="tier2" role="tabpanel">
        <div class="tier-section-header">
          <div>
            <h5>Tier 2 Districts</h5>
            <p>Medium priority districts with district score, negative marking, victim marking and final rank.</p>
          </div>
          <span class="badge bg-warning-subtle text-warning">18 Districts</span>
        </div>

        <div class="table-responsive">
          <table class="table table-ppmf align-middle mb-0">
            <thead>
              <tr>
                <th>District</th>
                <th>Obtained Score</th>
                <th>Obtained Rank</th>
                <th>Sixty Forty Ratio Score</th>
                <th>Special Branch Negative Marking</th>
                <th>Score After Negative Marking</th>
                <th>No. of Weeks Victims Occur / No. of Victims</th>
                <th>Victim Negative Marking</th>
                <th>Score After Victim Negative Marking</th>
                <th>Final Rank</th>
                <th>Status</th>
              </tr>
            </thead>

            <tbody>
              @php
                $tier2 = [
                  'Attock', 'Bahawalnagar', 'Bahawalpur', 'D. G. Khan', 'Gujrat', 'Jhang',
                  'Jhelum', 'Kasur', 'Khanewal', 'Murree', 'Muzaffargarh', 'Okara',
                  'R. Y. Khan', 'Sahiwal', 'Sargodha', 'Sheikhupura', 'Toba Tek Singh', 'Vehari'
                ];
              @endphp

              @foreach($tier2 as $index => $district)
              <tr>
                <td><strong>{{ $district }}</strong></td>
                <td>0.00</td>
                <td>0</td>
                <td>0.00</td>
                <td>0</td>
                <td>0.00</td>
                <td>0</td>
                <td>0.00</td>
                <td>0.00</td>
                <td><span class="rank-badge">{{ $index + 1 }}</span></td>
                <td><span class="badge bg-warning-subtle text-warning">Pending Score</span></td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      {{-- Tier 3 --}}
      <div class="tab-pane fade" id="tier3" role="tabpanel">
        <div class="tier-section-header">
          <div>
            <h5>Tier 3 Districts</h5>
            <p>General priority districts with scoring, marking, and rank summary.</p>
          </div>
          <span class="badge bg-danger-subtle text-danger">17 Districts</span>
        </div>

        <div class="table-responsive">
          <table class="table table-ppmf align-middle mb-0">
            <thead>
              <tr>
                <th>District</th>
                <th>Obtained Score</th>
                <th>Obtained Rank</th>
                <th>Sixty Forty Ratio Score</th>
                <th>Special Branch Negative Marking</th>
                <th>Score After Negative Marking</th>
                <th>No. of Weeks Victims Occur / No. of Victims</th>
                <th>Victim Negative Marking</th>
                <th>Score After Victim Negative Marking</th>
                <th>Final Rank</th>
                <th>Status</th>
              </tr>
            </thead>

            <tbody>
              @php
                $tier3 = [
                  'Bhakkar', 'Chakwal', 'Chiniot', 'Hafizabad', 'Khushab', 'Kot Addu',
                  'Layyah', 'Lodhran', 'M. B. Din', 'Mianwali', 'Nankana Sahib',
                  'Narowal', 'Pakpattan', 'Rajanpur', 'Talagang', 'Taunsa', 'Wazirabad'
                ];
              @endphp

              @foreach($tier3 as $index => $district)
              <tr>
                <td><strong>{{ $district }}</strong></td>
                <td>0.00</td>
                <td>0</td>
                <td>0.00</td>
                <td>0</td>
                <td>0.00</td>
                <td>0</td>
                <td>0.00</td>
                <td>0.00</td>
                <td><span class="rank-badge">{{ $index + 1 }}</span></td>
                <td><span class="badge bg-warning-subtle text-warning">Pending Score</span></td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

@endsection

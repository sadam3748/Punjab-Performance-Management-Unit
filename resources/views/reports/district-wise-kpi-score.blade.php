@extends('layouts.app')

@section('title', 'District Wise KPI Score Report | PPMF Portal')
@section('page_title', 'District Wise KPI Score Report')

@section('content')

<div class="page-actions-ppmf">
  <div>
    <h2>District Wise KPI Score Report</h2>
    <p>District-wise KPI score summary with category cards, submitted values, and evidence status.</p>
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
    <i class="bi bi-funnel"></i> KPI Score Filters
  </div>

  <div class="row g-3 align-items-end">
    <div class="col-md-3">
      <label class="form-label">KPI / Indicator</label>
      <select class="form-select">
        <option selected>Price of Roti</option>
        <option>Price of Plain Bakery Bread</option>
        <option>Price Control of Essential Commodities</option>
        <option>Repair of Small Roads</option>
        <option>Inspection of Educational Institutions</option>
        <option>Inspection of Health Facilities</option>
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

{{-- KPI Cards --}}
<div class="row g-3 mb-4">
  <div class="col-xl-4 col-md-6">
    <div class="kpi-score-card-ppmf">
      <div class="kpi-score-icon">
        <i class="bi bi-clipboard-check"></i>
      </div>
      <div>
        <strong>77</strong>
        <p>DCs twice weekly review with all PCMs, Food Department and Special Branch about enforcement of Rate of Roti.</p>
      </div>
    </div>
  </div>

  <div class="col-xl-4 col-md-6">
    <div class="kpi-score-card-ppmf">
      <div class="kpi-score-icon warning">
        <i class="bi bi-search"></i>
      </div>
      <div>
        <strong>0</strong>
        <p>Inspections of Tandoors to be conducted by ACs/PCMs daily as per tier-wise targets.</p>
      </div>
    </div>
  </div>

  <div class="col-xl-4 col-md-6">
    <div class="kpi-score-card-ppmf">
      <div class="kpi-score-icon info">
        <i class="bi bi-car-front"></i>
      </div>
      <div>
        <strong>0</strong>
        <p>Special Coverage and Mobility Index for ACs/PCMs.</p>
      </div>
    </div>
  </div>

  <div class="col-xl-4 col-md-6">
    <div class="kpi-score-card-ppmf">
      <div class="kpi-score-icon danger">
        <i class="bi bi-exclamation-triangle"></i>
      </div>
      <div>
        <strong>0</strong>
        <p>Imposition of fine on violations over price, weight, or non-availability of Roti.</p>
      </div>
    </div>
  </div>

  <div class="col-xl-4 col-md-6">
    <div class="kpi-score-card-ppmf">
      <div class="kpi-score-icon success">
        <i class="bi bi-chat-dots"></i>
      </div>
      <div>
        <strong>0</strong>
        <p>Action taken on complaints submitted by citizens.</p>
      </div>
    </div>
  </div>
</div>

{{-- Table --}}
<div class="card-ppmf">
  <div class="card-ppmf-header">
    <div>
      <div class="card-ppmf-title">
        <i class="bi bi-table"></i> District Wise KPI Score
      </div>
      <div class="card-ppmf-subtitle">
        Submitted values against selected KPI indicators.
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
        <input type="text" class="form-control form-control-sm" style="padding-left: 32px; width: 240px;" placeholder="Search district...">
      </div>
    </div>
  </div>

  <div class="card-ppmf-body p-0">
    <div class="table-responsive">
      <table class="table table-ppmf align-middle mb-0 kpi-score-table-ppmf">
        <thead>
          <tr>
            <th style="width: 140px;">District</th>
            <th>DCs Twice Weekly Review</th>
            <th>Inspections of Tandoors</th>
            <th>Special Coverage & Mobility Index</th>
            <th>Fine on Violations</th>
            <th>Citizen Complaint Action</th>
            <th>Evidence</th>
            <th>Status</th>
          </tr>
        </thead>

        <tbody>
          <tr>
            <td><strong>Total</strong></td>
            <td><strong class="text-success">77</strong></td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
            <td><span class="badge bg-secondary-subtle text-secondary">N/A</span></td>
            <td><span class="badge bg-warning-subtle text-warning">Partial</span></td>
          </tr>

          <tr>
            <td><strong>Lahore</strong></td>
            <td>18</td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
            <td>
              <button class="btn btn-sm btn-outline-success">
                <i class="bi bi-eye"></i> View
              </button>
            </td>
            <td><span class="badge bg-success-subtle text-success">Submitted</span></td>
          </tr>

          <tr>
            <td><strong>Faisalabad</strong></td>
            <td>14</td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
            <td>
              <button class="btn btn-sm btn-outline-success">
                <i class="bi bi-eye"></i> View
              </button>
            </td>
            <td><span class="badge bg-success-subtle text-success">Submitted</span></td>
          </tr>

          <tr>
            <td><strong>Rawalpindi</strong></td>
            <td>12</td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
            <td>
              <button class="btn btn-sm btn-outline-success">
                <i class="bi bi-eye"></i> View
              </button>
            </td>
            <td><span class="badge bg-success-subtle text-success">Submitted</span></td>
          </tr>

          <tr>
            <td><strong>Multan</strong></td>
            <td>10</td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
            <td>
              <button class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-dash-circle"></i> Missing
              </button>
            </td>
            <td><span class="badge bg-warning-subtle text-warning">Pending Evidence</span></td>
          </tr>

          <tr>
            <td><strong>Gujranwala</strong></td>
            <td>9</td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
            <td>
              <button class="btn btn-sm btn-outline-success">
                <i class="bi bi-eye"></i> View
              </button>
            </td>
            <td><span class="badge bg-success-subtle text-success">Submitted</span></td>
          </tr>

          <tr>
            <td><strong>Bahawalpur</strong></td>
            <td>7</td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
            <td>
              <button class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-dash-circle"></i> Missing
              </button>
            </td>
            <td><span class="badge bg-warning-subtle text-warning">Pending Evidence</span></td>
          </tr>

          <tr>
            <td><strong>D. G. Khan</strong></td>
            <td>4</td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
            <td>
              <button class="btn btn-sm btn-outline-danger">
                <i class="bi bi-exclamation-circle"></i> Required
              </button>
            </td>
            <td><span class="badge bg-danger-subtle text-danger">Incomplete</span></td>
          </tr>

          <tr>
            <td><strong>Sargodha</strong></td>
            <td>3</td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
            <td>
              <button class="btn btn-sm btn-outline-danger">
                <i class="bi bi-exclamation-circle"></i> Required
              </button>
            </td>
            <td><span class="badge bg-danger-subtle text-danger">Incomplete</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card-ppmf-footer d-flex justify-content-between align-items-center p-3 border-top">
    <small class="text-muted">Showing district-wise KPI submitted values for selected period.</small>

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

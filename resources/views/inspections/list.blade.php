@extends('layouts.app')

@section('title', 'Inspection List | PPMF Portal')
@section('page_title', 'Inspections')

@section('content')

<div class="page-actions-ppmf">
  <div>
    <h2>Inspections</h2>
    <p>District and tehsil-wise inspection records with type, field details, user, and date/time tracking.</p>
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

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="stat-card-ppmf border-success">
      <span>Total Inspections</span>
      <strong>1,248</strong>
      <small>All inspection records</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-primary">
      <span>Today</span>
      <strong>42</strong>
      <small>Inspections submitted today</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-warning">
      <span>Pending Review</span>
      <strong>18</strong>
      <small>Need verification</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-danger">
      <span>Flagged</span>
      <strong>06</strong>
      <small>Require attention</small>
    </div>
  </div>
</div>

{{-- Filters --}}
<div class="filter-card-ppmf mb-4">
  <div class="filter-title">
    <i class="bi bi-funnel"></i> Inspection Filters
  </div>

  <div class="row g-3 align-items-end">
    <div class="col-md-2">
      <label class="form-label">District</label>
      <select class="form-select">
        <option>All Districts</option>
        <option>Multan</option>
        <option>Sargodha</option>
        <option>Lahore</option>
        <option>Faisalabad</option>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label">Tehsil</label>
      <select class="form-select">
        <option>All Tehsils</option>
        <option>Multan City</option>
        <option>Sargodha</option>
        <option>Lahore City</option>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label">Type</label>
      <select class="form-select">
        <option>All Types</option>
        <option>Inspection of Stray Dogs</option>
        <option>Manhole Covers</option>
        <option>Cleanliness</option>
        <option>Encroachment</option>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label">From</label>
      <input type="date" class="form-control" value="2026-05-11">
    </div>

    <div class="col-md-2">
      <label class="form-label">To</label>
      <input type="date" class="form-control" value="2026-05-11">
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

{{-- Inspection Table --}}
<div class="card-ppmf">
  <div class="card-ppmf-header">
    <div>
      <div class="card-ppmf-title">
        <i class="bi bi-list-check"></i> Inspection Records
      </div>
      <div class="card-ppmf-subtitle">
        Showing inspection listing in a modern table view.
      </div>
    </div>

    <div class="d-flex align-items-center gap-2">
      <select class="form-select form-select-sm" style="width: 90px;">
        <option>10</option>
        <option selected>50</option>
        <option>100</option>
      </select>

      <div class="position-relative">
        <i class="bi bi-search position-absolute" style="left: 11px; top: 8px; color: var(--text-muted);"></i>
        <input type="text" class="form-control form-control-sm" style="padding-left: 32px; width: 230px;" placeholder="Search inspection...">
      </div>
    </div>
  </div>

  <div class="card-ppmf-body p-0">
    <div class="table-responsive">
      <table class="table table-ppmf align-middle mb-0">
        <thead>
          <tr>
            <th style="width: 70px;">Sr. No.</th>
            <th>Type</th>
            <th>Name / Field 1</th>
            <th>Address / Field 2</th>
            <th>Tehsil</th>
            <th>District</th>
            <th>User</th>
            <th>Date & Time</th>
            <th>Status</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>

        <tbody>
          <tr>
            <td>1</td>
            <td>Inspection of Stray Dogs</td>
            <td>SRA</td>
            <td>22. Manzoor Abad</td>
            <td>Multan City</td>
            <td>Multan</td>
            <td>Zulqarnain Haider</td>
            <td>11 May, 2026 11:54</td>
            <td>
              <span class="badge bg-success-subtle text-success">Verified</span>
            </td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-success">
                <i class="bi bi-eye"></i>
              </button>
            </td>
          </tr>

          <tr>
            <td>2</td>
            <td>Inspection of Stray Dogs</td>
            <td>K Block SRA</td>
            <td>20. Writer Colony</td>
            <td>Multan City</td>
            <td>Multan</td>
            <td>Zulqarnain Haider</td>
            <td>11 May, 2026 11:55</td>
            <td>
              <span class="badge bg-success-subtle text-success">Verified</span>
            </td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-success">
                <i class="bi bi-eye"></i>
              </button>
            </td>
          </tr>

          <tr>
            <td>3</td>
            <td>Inspection of Stray Dogs</td>
            <td>Eid Gah Basti</td>
            <td>UC No MC-10 Kot Farid</td>
            <td>Sargodha</td>
            <td>Sargodha</td>
            <td>Zoya LGSGD</td>
            <td>11 May, 2026 04:51</td>
            <td>
              <span class="badge bg-warning-subtle text-warning">Pending</span>
            </td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-success">
                <i class="bi bi-eye"></i>
              </button>
            </td>
          </tr>

          <tr>
            <td>4</td>
            <td>Inspection of Stray Dogs</td>
            <td>Eid Gah Basti</td>
            <td>UC No MC-10 Kot Farid</td>
            <td>Sargodha</td>
            <td>Sargodha</td>
            <td>Zoya LGSGD</td>
            <td>11 May, 2026 05:07</td>
            <td>
              <span class="badge bg-success-subtle text-success">Verified</span>
            </td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-success">
                <i class="bi bi-eye"></i>
              </button>
            </td>
          </tr>

          <tr>
            <td>5</td>
            <td>Inspection of Stray Dogs</td>
            <td>Eid Gah Basti</td>
            <td>UC No MC-10 Kot Farid</td>
            <td>Sargodha</td>
            <td>Sargodha</td>
            <td>Zoya LGSGD</td>
            <td>11 May, 2026 04:52</td>
            <td>
              <span class="badge bg-danger-subtle text-danger">Flagged</span>
            </td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-success">
                <i class="bi bi-eye"></i>
              </button>
            </td>
          </tr>

          <tr>
            <td>6</td>
            <td>Inspection of Stray Dogs</td>
            <td>School</td>
            <td>UC No MC-10 Kot Farid</td>
            <td>Sargodha</td>
            <td>Sargodha</td>
            <td>Zoya LGSGD</td>
            <td>11 May, 2026 05:05</td>
            <td>
              <span class="badge bg-success-subtle text-success">Verified</span>
            </td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-success">
                <i class="bi bi-eye"></i>
              </button>
            </td>
          </tr>

          <tr>
            <td>7</td>
            <td>Manhole Covers</td>
            <td>NJK</td>
            <td>Field Verification Point</td>
            <td>Bhakkar</td>
            <td>Bhakkar</td>
            <td>Waqar67801</td>
            <td>11 May, 2026 13:33</td>
            <td>
              <span class="badge bg-success-subtle text-success">Verified</span>
            </td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-success">
                <i class="bi bi-eye"></i>
              </button>
            </td>
          </tr>

          <tr>
            <td>8</td>
            <td>Cleanliness Inspection</td>
            <td>Main Bazaar</td>
            <td>Near Municipal Office</td>
            <td>Lahore City</td>
            <td>Lahore</td>
            <td>Admin User</td>
            <td>11 May, 2026 14:10</td>
            <td>
              <span class="badge bg-warning-subtle text-warning">Pending</span>
            </td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-success">
                <i class="bi bi-eye"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card-ppmf-footer d-flex justify-content-between align-items-center p-3 border-top">
    <small class="text-muted">Showing 1 to 8 of 1,248 entries</small>

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
          <a class="page-link" href="#">3</a>
        </li>
        <li class="page-item">
          <a class="page-link" href="#">Next</a>
        </li>
      </ul>
    </nav>
  </div>
</div>

@endsection

@extends('layouts.app')

@section('title', 'Geo Taggings | PPMF Portal')
@section('page_title', 'Geo Taggings')

@section('content')

<div class="page-actions-ppmf">
  <div>
    <h2>Geo Taggings</h2>
    <p>District and tehsil-wise geo-tagging records with type, address, user, date/time, and location detail.</p>
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
      <span>Total Geo Taggings</span>
      <strong>2,436</strong>
      <small>All submitted geo records</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-primary">
      <span>Today</span>
      <strong>68</strong>
      <small>Submitted today</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-warning">
      <span>Pending Review</span>
      <strong>24</strong>
      <small>Need verification</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-danger">
      <span>Flagged Records</span>
      <strong>09</strong>
      <small>Location/image issue</small>
    </div>
  </div>
</div>

{{-- Filters --}}
<div class="filter-card-ppmf mb-4">
  <div class="filter-title">
    <i class="bi bi-funnel"></i> Geo Tagging Filters
  </div>

  <div class="row g-3 align-items-end">
    <div class="col-md-2">
      <label class="form-label">District</label>
      <select class="form-select">
        <option>All Districts</option>
        <option>Bhakkar</option>
        <option>Gujranwala</option>
        <option>Rawalpindi</option>
        <option>Lahore</option>
        <option>Multan</option>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label">Tehsil</label>
      <select class="form-select">
        <option>All Tehsils</option>
        <option>Bhakkar</option>
        <option>Alipur Chatha</option>
        <option>Rawalpindi</option>
        <option>Lahore City</option>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label">Type</label>
      <select class="form-select">
        <option>All Types</option>
        <option>Manhole Covers</option>
        <option>Sale Points of LPG</option>
        <option>Tandoors</option>
        <option>Cleanliness</option>
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

{{-- Geo Tagging Table --}}
<div class="card-ppmf">
  <div class="card-ppmf-header">
    <div>
      <div class="card-ppmf-title">
        <i class="bi bi-geo-alt"></i> Geo Tagging Records
      </div>
      <div class="card-ppmf-subtitle">
        Showing geo-tagging listing in a clean and searchable table.
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
        <input type="text" class="form-control form-control-sm" style="padding-left: 32px; width: 240px;" placeholder="Search geo tagging...">
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
            <th>Name</th>
            <th>Address</th>
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
            <td>Manhole Covers</td>
            <td>NJK</td>
            <td>Street No. 04, Near Main Road</td>
            <td>Bhakkar</td>
            <td>Bhakkar</td>
            <td>waqar67801@gmail.com</td>
            <td>11 May, 2026 13:33</td>
            <td><span class="badge bg-success-subtle text-success">Verified</span></td>
            <td class="text-center">
              <a href="{{ route('geo-taggings.detail') }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-eye"></i>
              </a>
            </td>
          </tr>

          <tr>
            <td>2</td>
            <td>Manhole Covers</td>
            <td>Gali</td>
            <td>Old City Area</td>
            <td>Bhakkar</td>
            <td>Bhakkar</td>
            <td>waqar67801@gmail.com</td>
            <td>11 May, 2026 13:34</td>
            <td><span class="badge bg-success-subtle text-success">Verified</span></td>
            <td class="text-center">
              <a href="{{ route('geo-taggings.detail') }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-eye"></i>
              </a>
            </td>
          </tr>

          <tr>
            <td>3</td>
            <td>Sale Points of LPG</td>
            <td>Mazhakabal</td>
            <td>Gujranwala Road</td>
            <td>Alipur Chatha</td>
            <td>Gujranwala</td>
            <td>mcalipur</td>
            <td>11 May, 2026 12:12</td>
            <td><span class="badge bg-warning-subtle text-warning">Pending</span></td>
            <td class="text-center">
              <a href="{{ route('geo-taggings.detail') }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-eye"></i>
              </a>
            </td>
          </tr>

          <tr>
            <td>4</td>
            <td>Tandoors</td>
            <td>Abdullah Bhai</td>
            <td>Kadrava Road</td>
            <td>Alipur Chatha</td>
            <td>Gujranwala</td>
            <td>mcalipur</td>
            <td>11 May, 2026 09:57</td>
            <td><span class="badge bg-success-subtle text-success">Verified</span></td>
            <td class="text-center">
              <a href="{{ route('geo-taggings.detail') }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-eye"></i>
              </a>
            </td>
          </tr>

          <tr>
            <td>5</td>
            <td>Manhole Covers</td>
            <td>Inspection Point</td>
            <td>Shamsabad UC 23</td>
            <td>Rawalpindi</td>
            <td>Rawalpindi</td>
            <td>khan80@gmail.com</td>
            <td>11 May, 2026 10:26</td>
            <td><span class="badge bg-danger-subtle text-danger">Flagged</span></td>
            <td class="text-center">
              <a href="{{ route('geo-taggings.detail') }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-eye"></i>
              </a>
            </td>
          </tr>

          <tr>
            <td>6</td>
            <td>Manhole Covers</td>
            <td>Inspection Point</td>
            <td>Shamsabad UC 23</td>
            <td>Rawalpindi</td>
            <td>Rawalpindi</td>
            <td>khan80@gmail.com</td>
            <td>11 May, 2026 10:27</td>
            <td><span class="badge bg-success-subtle text-success">Verified</span></td>
            <td class="text-center">
              <a href="{{ route('geo-taggings.detail') }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-eye"></i>
              </a>
            </td>
          </tr>

          <tr>
            <td>7</td>
            <td>Cleanliness</td>
            <td>Main Bazaar</td>
            <td>Near Municipal Office</td>
            <td>Lahore City</td>
            <td>Lahore</td>
            <td>admin@ppmf.gov.pk</td>
            <td>11 May, 2026 11:20</td>
            <td><span class="badge bg-warning-subtle text-warning">Pending</span></td>
            <td class="text-center">
              <a href="{{ route('geo-taggings.detail') }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-eye"></i>
              </a>
            </td>
          </tr>

          <tr>
            <td>8</td>
            <td>Encroachment</td>
            <td>Field Site</td>
            <td>Market Road</td>
            <td>Multan City</td>
            <td>Multan</td>
            <td>field.user@ppmf.gov.pk</td>
            <td>11 May, 2026 14:05</td>
            <td><span class="badge bg-success-subtle text-success">Verified</span></td>
            <td class="text-center">
              <a href="{{ route('geo-taggings.detail') }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-eye"></i>
              </a>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card-ppmf-footer d-flex justify-content-between align-items-center p-3 border-top">
    <small class="text-muted">Showing 1 to 8 of 2,436 entries</small>

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

@extends('layouts.app')

@section('title', 'Geo Tagging Detail')
@section('page_title', 'Geo Tagging Detail')

@section('content')

<div class="page-actions-ppmf">
  <div>
    <h2>Geo Tagging Detail</h2>
    <p>Detailed geo-tagging record with location, district, tehsil, user, image evidence, and map preview.</p>
  </div>

  <div class="d-flex gap-2">
    <a href="{{ route('geo-taggings.list') }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Back to List
    </a>
    <button class="btn btn-outline-success">
      <i class="bi bi-download"></i> Export
    </button>
    <button class="btn btn-success">
      <i class="bi bi-printer"></i> Print
    </button>
  </div>
</div>

{{-- Status Summary --}}
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="stat-card-ppmf border-success">
      <span>Verification Status</span>
      <strong style="font-size: 22px;">Verified</strong>
      <small>Record checked successfully</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-primary">
      <span>District</span>
      <strong style="font-size: 22px;">Lahore</strong>
      <small>Assigned district</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-warning">
      <span>Geo Accuracy</span>
      <strong>96%</strong>
      <small>Location confidence</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-success">
      <span>Submitted On</span>
      <strong style="font-size: 22px;">11 May</strong>
      <small>2026 · 11:45 AM</small>
    </div>
  </div>
</div>

<div class="row g-4">

  {{-- Main Detail --}}
  <div class="col-xl-7">
    <div class="card-ppmf mb-4">
      <div class="card-ppmf-header">
        <div>
          <div class="card-ppmf-title">
            <i class="bi bi-geo-alt"></i> Geo Tagging Information
          </div>
          <div class="card-ppmf-subtitle">
            Field information captured from geo-tagging activity.
          </div>
        </div>

        <span class="badge bg-success-subtle text-success">
          <i class="bi bi-check-circle"></i> Verified
        </span>
      </div>

      <div class="card-ppmf-body">
        <div class="detail-grid-ppmf">

          <div class="detail-item-ppmf">
            <span>Type</span>
            <strong>Inspection of Stray Dogs</strong>
          </div>

          <div class="detail-item-ppmf">
            <span>Performed By</span>
            <strong>Zulqarnain Haider</strong>
          </div>

          <div class="detail-item-ppmf">
            <span>District</span>
            <strong>Lahore</strong>
          </div>

          <div class="detail-item-ppmf">
            <span>Tehsil</span>
            <strong>Lahore City</strong>
          </div>

          <div class="detail-item-ppmf detail-wide">
            <span>Address</span>
            <strong>Main Bazaar, Near Municipal Office, Lahore</strong>
          </div>

          <div class="detail-item-ppmf detail-wide">
            <span>Location</span>
            <strong>31.5204° N, 74.3587° E</strong>
          </div>

          <div class="detail-item-ppmf">
            <span>Date</span>
            <strong>11 May, 2026</strong>
          </div>

          <div class="detail-item-ppmf">
            <span>Time</span>
            <strong>11:45 AM</strong>
          </div>

          <div class="detail-item-ppmf">
            <span>Record ID</span>
            <strong>GT-2026-00125</strong>
          </div>

          <div class="detail-item-ppmf">
            <span>Source</span>
            <strong>Mobile App</strong>
          </div>

        </div>
      </div>
    </div>

    {{-- Remarks --}}
    <div class="card-ppmf">
      <div class="card-ppmf-header">
        <div>
          <div class="card-ppmf-title">
            <i class="bi bi-chat-left-text"></i> Remarks / Notes
          </div>
          <div class="card-ppmf-subtitle">
            Field notes and verification comments.
          </div>
        </div>
      </div>

      <div class="card-ppmf-body">
        <div class="remarks-box-ppmf">
          Geo-tagging record verified with image evidence and location coordinates. Field activity appears consistent with the submitted district and tehsil information.
        </div>
      </div>
    </div>
  </div>

  {{-- Right Side Evidence --}}
  <div class="col-xl-5">

    {{-- Image Evidence --}}
    <div class="card-ppmf mb-4">
      <div class="card-ppmf-header">
        <div>
          <div class="card-ppmf-title">
            <i class="bi bi-image"></i> Image Evidence
          </div>
          <div class="card-ppmf-subtitle">
            Uploaded field image preview.
          </div>
        </div>

        <button class="btn btn-sm btn-outline-success">
          <i class="bi bi-arrows-fullscreen"></i> View
        </button>
      </div>

      <div class="card-ppmf-body">
        <div class="image-evidence-ppmf">
          <div class="image-placeholder-ppmf">
            <i class="bi bi-image"></i>
            <h5>Geo Tagged Image</h5>
            <p>Replace this placeholder with the actual uploaded image path later.</p>
          </div>
        </div>
      </div>
    </div>

    {{-- Map --}}
    <div class="card-ppmf">
      <div class="card-ppmf-header">
        <div>
          <div class="card-ppmf-title">
            <i class="bi bi-map"></i> Location Map
          </div>
          <div class="card-ppmf-subtitle">
            Geo coordinates preview.
          </div>
        </div>
      </div>

      <div class="card-ppmf-body">
        <div class="location-map-ppmf">
          <i class="bi bi-geo-alt-fill"></i>
          <h5>Lahore, Punjab</h5>
          <p>31.5204° N, 74.3587° E</p>
          <button class="btn btn-sm btn-success">
            <i class="bi bi-crosshair"></i> Open Location
          </button>
        </div>
      </div>
    </div>

  </div>

</div>

@endsection

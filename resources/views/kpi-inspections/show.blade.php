@extends('layouts.app')
@section('title', $inspection->reference_no . ' — Inspection Detail')
@section('content_class', 'ppmu-dashboard-content ppmu-detail-page')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ppmu-kpi.css') }}?v={{ filemtime(public_path('css/ppmu-kpi.css')) }}">
@if(empty($googleMapsKey))
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@endif
@endpush

@section('content')
@php
    $imageUrl = asset($kpiCard->resolvedImagePath());
    $mapsUrl = $inspection->googleMapsUrl();
@endphp

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="ppmu-inspection-hero card-ppmf">
    <div class="ppmu-detail-hero-bar">
        <a href="{{ route('kpi.dashboard', $kpiCard) }}" class="ppmu-back">
            <i class="bi bi-arrow-left-circle-fill"></i> Back to KPI Dashboard
        </a>
        <span class="badge rounded-pill text-bg-{{ $inspection->statusClass() }} ppmu-inspection-hero-badge">{{ $inspection->statusLabel() }}</span>
    </div>

    <div class="ppmu-inspection-hero-main">
        <div class="ppmu-detail-visual">
            <img src="{{ $imageUrl }}" alt="{{ $kpiCard->title }}" width="88" height="88">
        </div>
        <div class="ppmu-detail-info">
            <span class="ppmu-detail-category">{{ $kpiCard->category }}</span>
            <h1>{{ $inspection->inspection_title }}</h1>
            <div class="ppmu-detail-meta">
                <span><i class="bi bi-tag-fill"></i>{{ $kpiCard->title }}</span>
                <span><i class="bi bi-hash"></i>{{ $inspection->reference_no }}</span>
                <span><i class="bi bi-calendar3"></i>{{ $inspection->inspection_datetime->format('d M Y, h:i A') }}</span>
            </div>
        </div>
    </div>
</div>

<div class="ppmu-inspection-summary-grid mb-3">
    <article class="ppmu-pi-card tone-blue"><div class="ppmu-pi-icon"><i class="bi bi-geo-alt-fill"></i></div><div class="ppmu-pi-body"><span class="ppmu-pi-label">District</span><strong class="ppmu-pi-value ppmu-pi-value-text">{{ $inspection->district?->name ?? '—' }}</strong></div></article>
    <article class="ppmu-pi-card tone-blue"><div class="ppmu-pi-icon"><i class="bi bi-pin-map-fill"></i></div><div class="ppmu-pi-body"><span class="ppmu-pi-label">Tehsil</span><strong class="ppmu-pi-value ppmu-pi-value-text">{{ $inspection->tehsil?->name ?? '—' }}</strong></div></article>
    <article class="ppmu-pi-card tone-green"><div class="ppmu-pi-icon"><i class="bi bi-calendar-event-fill"></i></div><div class="ppmu-pi-body"><span class="ppmu-pi-label">Inspection Date</span><strong class="ppmu-pi-value ppmu-pi-value-text">{{ $inspection->inspection_datetime->format('d M Y') }}</strong></div></article>
    <article class="ppmu-pi-card tone-purple"><div class="ppmu-pi-icon"><i class="bi bi-person-badge-fill"></i></div><div class="ppmu-pi-body"><span class="ppmu-pi-label">Inspected By</span><strong class="ppmu-pi-value ppmu-pi-value-text">{{ $inspection->inspectedBy?->name ?? '—' }}</strong></div></article>
    <article class="ppmu-pi-card tone-orange"><div class="ppmu-pi-icon"><i class="bi bi-shield-check"></i></div><div class="ppmu-pi-body"><span class="ppmu-pi-label">Review Status</span><strong class="ppmu-pi-value ppmu-pi-value-text">{{ $inspection->statusLabel() }}</strong></div></article>
    <article class="ppmu-pi-card tone-blue"><div class="ppmu-pi-icon"><i class="bi bi-clock-history"></i></div><div class="ppmu-pi-body"><span class="ppmu-pi-label">Last Updated</span><strong class="ppmu-pi-value ppmu-pi-value-text">{{ $inspection->updated_at->format('d M Y, h:i A') }}</strong></div></article>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card-ppmf ppmu-inspection-panel h-100">
            <h3><i class="bi bi-info-circle-fill"></i> Inspection Information</h3>
            <table class="table table-sm ppmu-inspection-info-table mb-0">
                <tr><th>Reference No.</th><td>{{ $inspection->reference_no }}</td></tr>
                <tr><th>Entity Name</th><td>{{ $inspection->entity_name ?? '—' }}</td></tr>
                <tr><th>Entity Type</th><td>{{ $inspection->entity_type ?? '—' }}</td></tr>
                <tr><th>Identifier</th><td>{{ $inspection->identifier ?? '—' }}</td></tr>
                <tr><th>Inspection Title</th><td>{{ $inspection->inspection_title }}</td></tr>
            </table>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card-ppmf ppmu-inspection-panel h-100">
            <h3><i class="bi bi-geo-fill"></i> Location Details</h3>
            <table class="table table-sm ppmu-inspection-info-table mb-0">
                <tr><th>Address</th><td>{{ $inspection->address ?? '—' }}</td></tr>
                <tr><th>District</th><td>{{ $inspection->district?->name ?? '—' }}</td></tr>
                <tr><th>Tehsil</th><td>{{ $inspection->tehsil?->name ?? '—' }}</td></tr>
                <tr><th>Latitude</th><td>{{ $inspection->latitude ?? '—' }}</td></tr>
                <tr><th>Longitude</th><td>{{ $inspection->longitude ?? '—' }}</td></tr>
            </table>
        </div>
    </div>
</div>

@if(!empty($inspection->detail_data))
    <div class="card-ppmf ppmu-inspection-panel mt-3">
        <h3><i class="bi bi-list-check"></i> Additional Details</h3>
        <div class="row g-2">
            @foreach($inspection->detail_data as $key => $value)
                <div class="col-md-4 col-sm-6">
                    <div class="ppmu-inspection-detail-item">
                        <span>{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                        <strong>{{ is_array($value) ? json_encode($value) : $value }}</strong>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="card-ppmf ppmu-inspection-panel mt-3">
    <h3><i class="bi bi-images"></i> Evidence Images</h3>
    @if($inspection->attachments->isNotEmpty())
        <div class="ppmu-evidence-gallery">
            @foreach($inspection->attachments as $attachment)
                @php $url = $attachment->resolvedUrl($fallbackImage); @endphp
                <figure class="ppmu-evidence-item">
                    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer">
                        <img src="{{ $url }}" alt="{{ $attachment->caption ?? 'Evidence image' }}" loading="lazy">
                    </a>
                    <figcaption>
                        <strong>{{ $attachment->caption ?? 'Field evidence' }}</strong>
                        <small>{{ $attachment->created_at?->format('d M Y') }}</small>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    @else
        <p class="text-muted mb-0">No evidence image available.</p>
    @endif
</div>

<div class="card-ppmf ppmu-inspection-panel mt-3">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
        <h3 class="mb-0"><i class="bi bi-map-fill"></i> Map Location</h3>
        @if($mapsUrl)
            <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-box-arrow-up-right"></i> Open in Google Maps
            </a>
        @endif
    </div>
    @if($inspection->latitude && $inspection->longitude)
        @if(!empty($googleMapsKey))
            <iframe
                class="ppmu-inspection-map-frame"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                src="https://www.google.com/maps/embed/v1/place?key={{ $googleMapsKey }}&q={{ $inspection->latitude }},{{ $inspection->longitude }}&zoom=15"
                allowfullscreen></iframe>
        @else
            <div id="ppmuInspectionMap" class="ppmu-inspection-map-frame" data-lat="{{ $inspection->latitude }}" data-lng="{{ $inspection->longitude }}"></div>
        @endif
    @else
        <p class="text-muted mb-0">Location coordinates are not available for this inspection.</p>
    @endif
</div>

<div class="row g-3 mt-0">
    <div class="col-lg-6">
        <div class="card-ppmf ppmu-inspection-panel h-100">
            <h3><i class="bi bi-eye-fill"></i> Observations</h3>
            @if(!empty($inspection->observations))
                <ul class="ppmu-inspection-list mb-0">
                    @foreach($inspection->observations as $item)
                        <li>{{ is_array($item) ? ($item['text'] ?? json_encode($item)) : $item }}</li>
                    @endforeach
                </ul>
            @else
                <p class="text-muted mb-0">No observations recorded.</p>
            @endif
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card-ppmf ppmu-inspection-panel h-100">
            <h3><i class="bi bi-tools"></i> Actions Taken / Required Actions</h3>
            @if(!empty($inspection->actions_taken))
                <h6 class="ppmu-inspection-subhead">Actions Taken</h6>
                <ul class="ppmu-inspection-list">
                    @foreach($inspection->actions_taken as $item)
                        <li>{{ is_array($item) ? ($item['text'] ?? json_encode($item)) : $item }}</li>
                    @endforeach
                </ul>
            @endif
            @if(!empty($inspection->actions_required))
                <h6 class="ppmu-inspection-subhead">Required Actions</h6>
                <ul class="ppmu-inspection-list mb-0">
                    @foreach($inspection->actions_required as $item)
                        <li>{{ is_array($item) ? ($item['text'] ?? json_encode($item)) : $item }}</li>
                    @endforeach
                </ul>
            @endif
            @if(empty($inspection->actions_taken) && empty($inspection->actions_required))
                <p class="text-muted mb-0">No action details recorded.</p>
            @endif
        </div>
    </div>
</div>

<div class="card-ppmf ppmu-inspection-panel ppmu-review-card mt-3">
    <div class="ppmu-review-head">
        <div>
            <h3><i class="bi bi-check2-square"></i> Review Decision</h3>
            <p>Approve verified field evidence or return it with a clear rejection reason.</p>
        </div>
        <span class="ppmu-inspection-status ppmu-inspection-status-{{ $inspection->status }}">{{ $inspection->statusLabel() }}</span>
    </div>

    @if($inspection->isPending() && $canReview)
        <div class="ppmu-review-actions">
            <div class="ppmu-review-action-card ppmu-review-approve">
                <form method="POST" action="{{ route('kpi.inspections.approve', [$kpiCard, $inspection]) }}" class="ppmu-review-form">
                    @csrf
                    <label class="form-label">Approval remarks <span>Optional</span></label>
                    <textarea name="review_remarks" class="form-control form-control-sm" rows="3" placeholder="Add concise approval remarks">{{ old('review_remarks') }}</textarea>
                    <button type="submit" class="btn ppmu-review-btn ppmu-review-btn-approve"><i class="bi bi-check-circle-fill"></i> Approve Inspection</button>
                </form>
            </div>
            <div class="ppmu-review-action-card ppmu-review-reject">
                <form method="POST" action="{{ route('kpi.inspections.reject', [$kpiCard, $inspection]) }}" class="ppmu-review-form">
                    @csrf
                    <label class="form-label">Rejection reason <span>Required</span></label>
                    <textarea name="rejection_reason" class="form-control form-control-sm @error('rejection_reason') is-invalid @enderror" rows="3" required placeholder="Explain what must be corrected">{{ old('rejection_reason') }}</textarea>
                    @error('rejection_reason')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <button type="submit" class="btn ppmu-review-btn ppmu-review-btn-reject"><i class="bi bi-x-circle-fill"></i> Reject Inspection</button>
                </form>
            </div>
        </div>
    @else
        <div class="ppmu-review-decision">
            <p><strong>Status:</strong> <span class="badge rounded-pill text-bg-{{ $inspection->statusClass() }}">{{ $inspection->statusLabel() }}</span></p>
            @if($inspection->reviewedBy)
                <p><strong>Reviewed By:</strong> {{ $inspection->reviewedBy->name }} @if($inspection->reviewed_at)<small class="text-muted">· {{ $inspection->reviewed_at->format('d M Y, h:i A') }}</small>@endif</p>
            @endif
            @if($inspection->review_remarks)
                <p><strong>Review Remarks:</strong> {{ $inspection->review_remarks }}</p>
            @endif
            @if($inspection->rejection_reason)
                <p><strong>Rejection Reason:</strong> {{ $inspection->rejection_reason }}</p>
            @endif
            @if($inspection->isPending() && ! $canReview)
                <p class="text-muted mb-0">You have read-only access to this inspection review.</p>
            @endif
        </div>
    @endif
</div>
@endsection

@push('scripts')
@if(empty($googleMapsKey) && $inspection->latitude && $inspection->longitude)
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function () {
    const el = document.getElementById('ppmuInspectionMap');
    if (!el || typeof L === 'undefined') return;
    const lat = parseFloat(el.dataset.lat);
    const lng = parseFloat(el.dataset.lng);
    const map = L.map(el).setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);
    L.marker([lat, lng]).addTo(map);
    setTimeout(() => map.invalidateSize(), 200);
})();
</script>
@endif
@endpush

@extends('layouts.app')
@section('title', $inspection->reference_no . ' — Field Inspection Detail')
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
        <a href="{{ $backUrl }}" class="ppmu-back">
            <i class="bi bi-arrow-left-circle-fill"></i> Back
        </a>
        <span class="badge rounded-pill text-bg-{{ $inspection->statusClass() }} ppmu-inspection-hero-badge">{{ $inspection->statusLabel() }}</span>
    </div>

    <div class="ppmu-inspection-hero-main">
        <div class="ppmu-detail-visual">
            <img src="{{ $imageUrl }}" alt="{{ $kpiCard->title }}" width="88" height="88">
        </div>
        <div class="ppmu-detail-info">
            <h1>{{ $inspection->inspection_title }}</h1>
            <div class="ppmu-detail-meta">
                <span><i class="bi bi-tag-fill"></i>{{ $kpiCard->title }}</span>
                <span><i class="bi bi-hash"></i>{{ $inspection->reference_no }}</span>
                <span><i class="bi bi-calendar3"></i>{{ $inspection->inspection_datetime->format('d M Y, h:i A') }}</span>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 ppmu-inspection-detail-rows">
    <div class="col-lg-6 d-flex">
        <div class="card-ppmf ppmu-inspection-panel h-100 w-100">
            <h3><i class="bi bi-info-circle"></i> Inspection Information</h3>
            <dl class="ppmu-info-grid ppmu-info-grid-balanced mb-0">
                <div class="ppmu-info-item"><dt>Reference No.</dt><dd>{{ $inspection->reference_no }}</dd></div>
                <div class="ppmu-info-item"><dt>Entity Name</dt><dd>{{ $inspection->entity_name ?? '—' }}</dd></div>
                <div class="ppmu-info-item"><dt>Entity Type</dt><dd>{{ $inspection->entity_type ?? '—' }}</dd></div>
                <div class="ppmu-info-item"><dt>Identifier</dt><dd>{{ $inspection->identifier ?? '—' }}</dd></div>
                <div class="ppmu-info-item ppmu-info-item-wide"><dt>Inspection Title</dt><dd>{{ $inspection->inspection_title }}</dd></div>
            </dl>
        </div>
    </div>
    <div class="col-lg-6 d-flex">
        <div class="card-ppmf ppmu-inspection-panel h-100 w-100">
            <h3><i class="bi bi-geo-alt"></i> Location Details</h3>
            <dl class="ppmu-info-grid ppmu-info-grid-balanced mb-0">
                <div class="ppmu-info-item ppmu-info-item-wide"><dt>Full Address</dt><dd class="ppmu-address-text">{{ $inspection->address ?? '—' }}</dd></div>
                <div class="ppmu-info-item"><dt>Tehsil</dt><dd>{{ $inspection->tehsil?->name ?? '—' }}</dd></div>
                <div class="ppmu-info-item ppmu-coordinate-info-item">
                    <dt>Coordinates</dt>
                    <dd class="ppmu-coordinate-values">
                        <span><small>Latitude</small>{{ $inspection->latitude ?? '—' }}</span>
                        <span><small>Longitude</small>{{ $inspection->longitude ?? '—' }}</span>
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</div>

@if(!empty($observationCards))
    @php $detailFieldCount = count($observationCards); @endphp
    <div class="card-ppmf ppmu-inspection-panel mt-3">
        <h3><i class="bi bi-list-check"></i> Observations</h3>
        <div class="ppmu-kpi-specific-grid ppmu-kpi-specific-grid-count-{{ $detailFieldCount }}">
            @foreach($observationCards as $observation)
                <div class="ppmu-inspection-detail-item ppmu-kpi-specific-card tone-{{ ['green','blue','purple','orange','red','yellow'][$loop->index % 6] }}">
                    <div class="ppmu-kpi-specific-icon">
                        <i class="bi {{ ['bi-check2-circle','bi-buildings','bi-geo-alt','bi-person-check','bi-clipboard2-check','bi-shield-check'][$loop->index % 6] }}"></i>
                    </div>
                    <div class="ppmu-kpi-specific-body">
                        <span>{{ $observation['label'] }}</span>
                        <span class="badge rounded-pill text-bg-{{ ($observation['status_tone'] ?? 'neutral') === 'warning' ? 'warning' : 'success' }} ppmu-obs-status-badge">
                            {{ $observation['value'] }}
                        </span>
                        @if(($observation['key'] ?? '') !== 'overall_attention')
                            @if($observation['has_evidence'] ?? false)
                                <button
                                    type="button"
                                    class="ppmu-obs-evidence-link mt-1"
                                    data-bs-toggle="modal"
                                    data-bs-target="#ppmuObservationEvidenceModal"
                                    data-evidence-url="{{ $observation['evidence_url'] }}"
                                    data-evidence-label="{{ $observation['label'] }}"
                                    data-observation-key="{{ $observation['observation_key'] ?? '' }}">
                                    <i class="bi bi-image"></i> View Evidence
                                </button>
                            @else
                                <span class="ppmu-obs-evidence-muted mt-1"><i class="bi bi-image"></i> No Evidence</span>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="card-ppmf ppmu-inspection-panel mt-3" id="evidence-images">
    <h3><i class="bi bi-images"></i> Evidence Images</h3>
    @if($inspection->attachments->isNotEmpty())
        <div class="ppmu-evidence-gallery">
            @foreach($inspection->attachments as $attachment)
                @php
                    $url = $attachment->resolvedUrl($fallbackImage);
                    $evidenceId = $attachment->observation_key
                        ? 'evidence-'.$attachment->observation_key
                        : 'evidence-general-'.$loop->index;
                @endphp
                <figure class="ppmu-evidence-item" id="{{ $evidenceId }}">
                    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="ppmu-evidence-open" data-evidence-url="{{ $url }}">
                        <img src="{{ $url }}" alt="{{ $attachment->caption ?? 'Evidence image' }}" loading="lazy">
                    </a>
                    <figcaption>
                        <strong>Field evidence photo {{ $loop->iteration }}</strong>
                        <small>{{ $attachment->created_at?->format('d M Y') }}</small>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    @else
        <p class="text-muted mb-0">No evidence image available.</p>
    @endif
</div>

<div class="card-ppmf ppmu-inspection-panel mt-3 ppmu-map-panel">
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-2">
        <div>
            <h3 class="mb-1"><i class="bi bi-map"></i> Map Location</h3>
            @if($inspection->address)
                <p class="ppmu-map-address mb-0"><i class="bi bi-geo-alt-fill"></i>{{ $inspection->address }}</p>
            @endif
        </div>
        @if($mapsUrl)
            <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm ppmu-map-open-btn">
                <i class="bi bi-box-arrow-up-right"></i> Open in Maps
            </a>
        @endif
    </div>
    @if($inspection->latitude && $inspection->longitude)
        <div class="ppmu-map-coordinate-strip">
            <span><i class="bi bi-crosshair"></i> Latitude <strong>{{ number_format((float) $inspection->latitude, 6) }}</strong></span>
            <span><i class="bi bi-crosshair2"></i> Longitude <strong>{{ number_format((float) $inspection->longitude, 6) }}</strong></span>
        </div>
        @if(!empty($googleMapsKey))
            <iframe
                class="ppmu-inspection-map-frame"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                src="https://www.google.com/maps/embed/v1/place?key={{ $googleMapsKey }}&q={{ urlencode($inspection->address ?: ($inspection->latitude.','.$inspection->longitude)) }}&zoom=16"
                allowfullscreen></iframe>
        @else
            <div id="ppmuInspectionMap"
                 class="ppmu-inspection-map-frame"
                 data-lat="{{ $inspection->latitude }}"
                 data-lng="{{ $inspection->longitude }}"
                 data-address="{{ $inspection->address }}"></div>
        @endif
    @else
        <p class="text-muted mb-0">Location coordinates are not available for this inspection.</p>
    @endif
</div>

<div class="card-ppmf ppmu-inspection-panel ppmu-review-card mt-3">
    <div class="ppmu-review-head">
        <div>
            <h3><i class="bi bi-check2-square"></i> Review Decision</h3>
            <p class="ppmu-panel-sub">Approve verified evidence or reject with a clear reason.</p>
        </div>
        <span class="ppmu-inspection-status ppmu-inspection-status-{{ $inspection->status }}">{{ $inspection->statusLabel() }}</span>
    </div>

    @if($inspection->isPending() && $canReview)
        <div class="ppmu-review-actions-unified">
            <label class="form-label" for="review-remarks">Remarks</label>
            <textarea
                id="review-remarks"
                class="form-control @error('review_remarks') is-invalid @enderror @error('rejection_reason') is-invalid @enderror"
                rows="4"
                placeholder="Add approval or rejection remarks (optional)">{{ old('review_remarks', old('rejection_reason')) }}</textarea>
            @error('review_remarks')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            @error('rejection_reason')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

            <div class="ppmu-review-action-buttons">
                <form method="POST" action="{{ route('kpi.inspections.approve', [$kpiCard, $inspection]) }}" class="ppmu-review-form-inline" id="approve-inspection-form">
                    @csrf
                    <input type="hidden" name="return_url" value="{{ $backUrl }}">
                    <input type="hidden" name="review_remarks" id="approve-remarks-input" value="{{ old('review_remarks') }}">
                    <button type="submit" class="btn ppmu-review-btn ppmu-review-btn-approve">
                        <i class="bi bi-check-circle-fill"></i> Approve Inspection
                    </button>
                </form>
                <form method="POST" action="{{ route('kpi.inspections.reject', [$kpiCard, $inspection]) }}" class="ppmu-review-form-inline" id="reject-inspection-form">
                    @csrf
                    <input type="hidden" name="return_url" value="{{ $backUrl }}">
                    <input type="hidden" name="rejection_reason" id="reject-remarks-input" value="{{ old('rejection_reason') }}">
                    <button type="submit" class="btn ppmu-review-btn ppmu-review-btn-reject">
                        <i class="bi bi-x-circle-fill"></i> Reject Inspection
                    </button>
                </form>
            </div>
        </div>
        <script>
        (function () {
            const remarks = document.getElementById('review-remarks');
            const approveInput = document.getElementById('approve-remarks-input');
            const rejectInput = document.getElementById('reject-remarks-input');
            const approveForm = document.getElementById('approve-inspection-form');
            const rejectForm = document.getElementById('reject-inspection-form');
            if (!remarks || !approveForm || !rejectForm) return;

            approveForm.addEventListener('submit', function () {
                if (approveInput) approveInput.value = remarks.value.trim();
            });

            rejectForm.addEventListener('submit', function () {
                if (rejectInput) rejectInput.value = remarks.value.trim();
            });
        })();
        </script>
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

<div class="modal fade" id="ppmuObservationEvidenceModal" tabindex="-1" aria-labelledby="ppmuObservationEvidenceTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content ppmu-modal-content ppmu-obs-evidence-modal">
            <div class="modal-header ppmu-modal-header">
                <div>
                    <span class="ppmu-obs-evidence-modal-eyebrow">Observation Evidence</span>
                    <h5 class="modal-title mb-0" id="ppmuObservationEvidenceTitle">Evidence Preview</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body ppmu-obs-evidence-modal-body">
                <img id="ppmuObservationEvidenceImage" src="" alt="Observation evidence preview" class="ppmu-obs-evidence-modal-img">
                <p id="ppmuObservationEvidenceCaption" class="ppmu-obs-evidence-modal-caption mb-0"></p>
            </div>
            <div class="modal-footer ppmu-obs-evidence-modal-footer">
                <a href="#" id="ppmuObservationEvidenceOpenLink" class="btn btn-sm ppmu-map-open-btn" target="_blank" rel="noopener noreferrer">
                    <i class="bi bi-box-arrow-up-right"></i> Open Full Image
                </a>
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const modal = document.getElementById('ppmuObservationEvidenceModal');
    if (!modal) return;

    modal.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        if (!trigger) return;

        const url = trigger.getAttribute('data-evidence-url') || '';
        const label = trigger.getAttribute('data-evidence-label') || 'Observation';
        const key = trigger.getAttribute('data-observation-key') || '';
        const title = document.getElementById('ppmuObservationEvidenceTitle');
        const image = document.getElementById('ppmuObservationEvidenceImage');
        const caption = document.getElementById('ppmuObservationEvidenceCaption');
        const openLink = document.getElementById('ppmuObservationEvidenceOpenLink');

        if (title) title.textContent = label;
        if (image) {
            image.src = url;
            image.alt = label + ' evidence';
        }
        if (caption) {
            caption.textContent = key
                ? 'Observation field: ' + key.replace(/_/g, ' ')
                : 'Field evidence image';
        }
        if (openLink) {
            openLink.href = url || '#';
            openLink.classList.toggle('disabled', url === '');
        }
    });
})();
</script>
@if(empty($googleMapsKey) && $inspection->latitude && $inspection->longitude)
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function () {
    const el = document.getElementById('ppmuInspectionMap');
    if (!el || typeof L === 'undefined') return;
    const lat = parseFloat(el.dataset.lat);
    const lng = parseFloat(el.dataset.lng);
    const address = el.dataset.address || 'Inspection location';
    const map = L.map(el, { scrollWheelZoom: false }).setView([lat, lng], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    const pin = L.divIcon({
        className: 'ppmu-map-pin-wrap',
        html: '<div class="ppmu-map-pin"><i class="bi bi-geo-alt-fill"></i></div>',
        iconSize: [34, 42],
        iconAnchor: [17, 42],
        popupAnchor: [0, -40],
    });

    L.marker([lat, lng], { icon: pin })
        .addTo(map)
        .bindPopup('<strong>Inspection Site</strong><br>' + address)
        .openPopup();

    setTimeout(() => map.invalidateSize(), 200);
})();
</script>
@endif
@endpush

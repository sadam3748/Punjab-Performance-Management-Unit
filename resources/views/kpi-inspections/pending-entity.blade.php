@extends('layouts.app')
@section('title', $entity->name.' — Inspection Pending')
@section('content_class', 'ppmu-dashboard-content ppmu-detail-page')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ppmu-kpi.css') }}?v={{ filemtime(public_path('css/ppmu-kpi.css')) }}">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
@endpush

@section('content')
<div class="ppmu-inspection-hero card-ppmf">
    <div class="ppmu-detail-hero-bar">
        <a href="{{ $backUrl }}" class="ppmu-back"><i class="bi bi-arrow-left-circle-fill"></i> Back</a>
        <span class="badge rounded-pill text-bg-secondary ppmu-inspection-hero-badge">Inspection Pending</span>
    </div>
    <div class="ppmu-inspection-hero-main">
        <div class="ppmu-detail-visual"><img src="{{ asset($kpiCard->resolvedImagePath()) }}" alt="{{ $kpiCard->title }}" width="88" height="88"></div>
        <div class="ppmu-detail-info">
            <h1>{{ $entity->name }}</h1>
            <div class="ppmu-detail-meta">
                <span><i class="bi bi-tag-fill"></i>{{ $kpiCard->title }}</span>
                <span><i class="bi bi-calendar3"></i>{{ $periodDescription }}</span>
            </div>
        </div>
    </div>
</div>

<div class="ppmu-inspection-detail-compact">
    <div class="row g-2 ppmu-inspection-detail-rows">
        <div class="col-lg-6 d-flex"><div class="card-ppmf ppmu-inspection-panel ppmu-inspection-panel-compact h-100 w-100">
            <h3><i class="bi bi-info-circle"></i> Entity Information</h3>
            <dl class="ppmu-info-grid ppmu-info-grid-balanced mb-0">
                <div class="ppmu-info-item"><dt>Name</dt><dd>{{ $entity->name }}</dd></div>
                <div class="ppmu-info-item"><dt>Entity Type</dt><dd>{{ $entityType === 'health' ? $entity->facility_type : $entity->institution_type }}</dd></div>
                <div class="ppmu-info-item"><dt>Assigned KPI</dt><dd>{{ $kpiCard->title }}</dd></div>
                <div class="ppmu-info-item"><dt>Inspection Status</dt><dd><span class="badge text-bg-secondary">Inspection Pending</span></dd></div>
                <div class="ppmu-info-item"><dt>Inspection Date</dt><dd>Not yet inspected</dd></div>
                <div class="ppmu-info-item"><dt>Reporting Period</dt><dd>{{ $periodDescription }}</dd></div>
            </dl>
        </div></div>
        <div class="col-lg-6 d-flex"><div class="card-ppmf ppmu-inspection-panel ppmu-inspection-panel-compact h-100 w-100">
            <h3><i class="bi bi-geo-alt"></i> Location</h3>
            <dl class="ppmu-info-grid ppmu-info-grid-balanced mb-2">
                <div class="ppmu-info-item ppmu-info-item-wide"><dt>Address</dt><dd>{{ $entity->address ?: '—' }}</dd></div>
                <div class="ppmu-info-item"><dt>Tehsil</dt><dd>{{ $entity->tehsil?->name ?? '—' }}</dd></div>
                <div class="ppmu-info-item"><dt>District</dt><dd>{{ $entity->district?->name ?? '—' }}</dd></div>
            </dl>
            <div id="pendingEntityMap" class="ppmu-pending-entity-map" aria-label="Entity location map"></div>
        </div></div>
    </div>
    <div class="card-ppmf ppmu-inspection-panel ppmu-inspection-panel-compact">
        <h3><i class="bi bi-list-check"></i> Observations and Findings</h3>
        <div class="ppmu-observation-empty-state">Observation results will be available after the inspection is completed.</div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const lat = Number(@json((float) $entity->latitude));
    const lng = Number(@json((float) $entity->longitude));
    if (!window.L || !lat || !lng) return;
    const map = L.map('pendingEntityMap', { scrollWheelZoom: false }).setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
    L.marker([lat, lng]).addTo(map).bindPopup(@json($entity->name)).openPopup();
});
</script>
@endpush

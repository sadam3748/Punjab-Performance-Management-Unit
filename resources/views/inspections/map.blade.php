@extends('layouts.app')

@section('title', 'Inspection Map')

@section('content')

<div class="page-title-bar ppmf-map-titlebar">
    <div>
        <div class="ppmf-eyebrow">
            <i class="bi bi-geo-alt-fill"></i>
            Geo Monitoring
        </div>
        <h1 class="page-title">Inspection Map</h1>
        <p class="page-subtitle">
            View geo-tagged inspection records by district, tehsil, KPI category, status and date range.
        </p>
    </div>

    <div class="page-title-actions">
        <a href="{{ route('inspections.list') }}" class="btn-gov btn-gov-outline">
            <i class="bi bi-list-check"></i>
            List View
        </a>

        <a href="{{ route('kpi.graphical-report') }}" class="btn-gov btn-gov-primary">
            <i class="bi bi-bar-chart-line"></i>
            KPI Report
        </a>
    </div>
</div>

@php
    $totalRecords = count($mapRecords);
    $todayCount = collect($mapRecords)->filter(function ($record) {
        if (empty($record->inspection_datetime)) {
            return false;
        }

        try {
            return \Carbon\Carbon::parse($record->inspection_datetime)->isToday();
        } catch (\Exception $e) {
            return false;
        }
    })->count();

    $mapPoints = collect($mapRecords)->filter(function ($record) {
        return !empty($record->latitude) && !empty($record->longitude);
    })->map(function ($record) {
        return [
            'id' => $record->id,
            'title' => $record->main_title ?? 'Inspection Record',
            'category' => $record->kpiCategory->name ?? 'KPI Category',
            'district' => $record->district->name ?? 'N/A',
            'tehsil' => optional($record->tehsil)->name,
            'status' => $record->status ?? 'submitted',
            'lat' => (float) $record->latitude,
            'lng' => (float) $record->longitude,
            'date' => $record->inspection_datetime
                ? \Carbon\Carbon::parse($record->inspection_datetime)->format('d M, Y h:i A')
                : 'N/A',
            'detail_url' => route('inspections.show', $record->id),
            'google_url' => 'https://www.google.com/maps?q=' . $record->latitude . ',' . $record->longitude,
        ];
    })->values();
@endphp

{{-- Executive status strip --}}
<div class="ppmf-map-stats-grid mb-4">
    <div class="ppmf-map-stat-card total">
        <div class="stat-icon"><i class="bi bi-clipboard-data"></i></div>
        <div>
            <span>Total Inspections</span>
            <strong>{{ number_format($totalRecords) }}</strong>
        </div>
    </div>

    <div class="ppmf-map-stat-card today">
        <div class="stat-icon"><i class="bi bi-calendar-check"></i></div>
        <div>
            <span>Today&apos;s Inspections</span>
            <strong>{{ number_format($todayCount) }}</strong>
        </div>
    </div>
</div>

{{-- Old PPMF style filters, modernized --}}
<div class="card-ppmf ppmf-filter-panel mb-4">
    <div class="ppmf-filter-head">
        <div>
            <div class="card-ppmf-title">
                <i class="bi bi-funnel"></i>
                Inspections on Map
            </div>
            <p class="mb-0">Use filters to refine geo-tagged inspection markers.</p>
        </div>

        <a href="{{ route('inspections.map') }}" class="ppmf-filter-reset">
            <i class="bi bi-arrow-clockwise"></i>
            Reset Filters
        </a>
    </div>

    <div class="card-ppmf-body pt-0">
        <form method="GET" action="{{ route('inspections.map') }}" id="inspectionMapFilterForm">
            <div class="ppmf-map-filter-grid">
                <div class="ppmf-filter-field">
                    <label>District</label>
                    <select name="district_id" class="form-select ppmf-control auto-submit-filter">
                        <option value="">All Districts</option>
                        @foreach ($districts as $district)
                            <option value="{{ $district->id }}" {{ ($filters['district_id'] ?? '') == $district->id ? 'selected' : '' }}>
                                {{ $district->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="ppmf-filter-field">
                    <label>Tehsil</label>
                    <select name="tehsil_id" class="form-select ppmf-control auto-submit-filter">
                        <option value="">All Tehsils</option>
                        @foreach ($tehsils as $tehsil)
                            <option value="{{ $tehsil->id }}" {{ ($filters['tehsil_id'] ?? '') == $tehsil->id ? 'selected' : '' }}>
                                {{ $tehsil->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="ppmf-filter-field ppmf-filter-wide">
                    <label>Inspection Type</label>
                    <select name="kpi_category_id" class="form-select ppmf-control auto-submit-filter">
                        <option value="">All Types</option>
                        @foreach ($kpiCategories as $category)
                            <option value="{{ $category->id }}" {{ ($filters['kpi_category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="ppmf-filter-field">
                    <label>Status</label>
                    <select name="status" class="form-select ppmf-control auto-submit-filter">
                        <option value="">All Status</option>
                        @foreach (['submitted', 'reviewed', 'approved', 'rejected'] as $status)
                            <option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="ppmf-filter-field">
                    <label>From</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control ppmf-control">
                </div>

                <div class="ppmf-filter-field">
                    <label>To</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control ppmf-control">
                </div>

                <div class="ppmf-filter-actions">
                    <button type="submit" class="btn-gov btn-gov-primary w-100">
                        <i class="bi bi-search"></i>
                        Apply
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="row g-4">
    {{-- Map Panel --}}
    <div class="col-xl-8 col-lg-12">
        <div class="card-ppmf ppmf-map-card h-100">
            <div class="card-ppmf-header ppmf-map-card-header">
                <div>
                    <div class="card-ppmf-title">
                        <i class="bi bi-map"></i>
                        Punjab Inspection Map
                    </div>
                    <p class="card-subtitle mb-0">
                        Showing {{ number_format($mapPoints->count()) }} mapped record(s) from selected filters.
                    </p>
                </div>

                <div class="ppmf-map-tools">
                    <button type="button" class="ppmf-map-tool-btn" id="fitMapBtn">
                        <i class="bi bi-arrows-fullscreen"></i>
                        Fit Markers
                    </button>
                </div>
            </div>

            <div class="card-ppmf-body">
                <div class="ppmf-live-map-wrap">
                    <div id="inspectionLeafletMap" class="ppmf-live-map"></div>

                    @if($mapPoints->isEmpty())
                        <div class="ppmf-map-empty-state">
                            <i class="bi bi-geo-alt"></i>
                            <h5>No geo-tagged records found</h5>
                            <p>Change filters or reset the page to view inspection markers.</p>
                        </div>
                    @endif
                </div>

                <div class="ppmf-map-legend">
                    <span><i class="legend-dot submitted"></i> Submitted</span>
                    <span><i class="legend-dot reviewed"></i> Reviewed</span>
                    <span><i class="legend-dot approved"></i> Approved</span>
                    <span><i class="legend-dot rejected"></i> Rejected</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Location Records --}}
    <div class="col-xl-4 col-lg-12">
        <div class="card-ppmf ppmf-location-panel h-100">
            <div class="card-ppmf-header">
                <div>
                    <div class="card-ppmf-title">
                        <i class="bi bi-list-ul"></i>
                        Location Records
                    </div>
                    <p class="card-subtitle mb-0">Latest filtered geo-tagged inspections.</p>
                </div>
            </div>

            <div class="card-ppmf-body">
                <div class="location-list-ppmf">
                    @forelse ($mapRecords as $record)
                        @php
                            $status = $record->status ?? 'submitted';
                            $statusClass = match ($status) {
                                'approved' => 'approved',
                                'reviewed' => 'reviewed',
                                'rejected' => 'rejected',
                                default => 'submitted',
                            };
                        @endphp

                        <div class="location-record-card {{ $statusClass }}">
                            <div class="location-record-top">
                                <div>
                                    <h6>{{ $record->main_title ?? 'Inspection Record' }}</h6>
                                    <p>{{ $record->kpiCategory->name ?? 'N/A' }}</p>
                                </div>

                                <span class="ppmf-status-pill {{ $statusClass }}">
                                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                                </span>
                            </div>

                            <div class="location-record-meta">
                                <div>
                                    <i class="bi bi-building"></i>
                                    <span>{{ $record->district->name ?? 'N/A' }}{{ $record->tehsil ? ' / ' . $record->tehsil->name : '' }}</span>
                                </div>

                                <div>
                                    <i class="bi bi-person"></i>
                                    <span>{{ $record->performer->username ?? $record->performer->name ?? 'N/A' }}</span>
                                </div>

                                <div>
                                    <i class="bi bi-calendar-event"></i>
                                    <span>
                                        @if($record->inspection_datetime)
                                            {{ \Carbon\Carbon::parse($record->inspection_datetime)->format('d M, Y h:i A') }}
                                        @else
                                            N/A
                                        @endif
                                    </span>
                                </div>

                                <div>
                                    <i class="bi bi-crosshair"></i>
                                    <span>{{ $record->latitude }}, {{ $record->longitude }}</span>
                                </div>
                            </div>

                            <div class="location-record-actions">
                                <a href="{{ route('inspections.show', $record->id) }}" class="btn-gov btn-gov-outline btn-gov-sm">
                                    <i class="bi bi-eye"></i>
                                    Detail
                                </a>

                                <a href="https://www.google.com/maps?q={{ $record->latitude }},{{ $record->longitude }}" target="_blank" class="btn-gov btn-gov-primary btn-gov-sm">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                    Map
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="manual-box-ppmf">
                            <i class="bi bi-geo"></i>
                            <h5>No Location Records Found</h5>
                            <p>No inspection records with latitude and longitude are available for the selected filters.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
<style>
    .ppmf-map-titlebar {
        border-bottom: 1px solid rgba(15, 23, 42, .06);
        margin-bottom: 18px;
    }

    .ppmf-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 6px 10px;
        border-radius: 999px;
        background: rgba(20, 184, 166, .10);
        color: #0f766e;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
        margin-bottom: 9px;
    }

    .ppmf-map-stats-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .ppmf-map-stat-card {
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 18px 18px;
        border-radius: 20px;
        background: linear-gradient(180deg, #ffffff, #f8fafc);
        border: 1px solid rgba(15, 23, 42, .08);
        box-shadow: 0 12px 26px rgba(15, 23, 42, .06);
        transition: transform .14s ease, box-shadow .14s ease;
    }

    .ppmf-map-stat-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 16px 34px rgba(15, 23, 42, .08);
    }

    .ppmf-map-stat-card::before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 5px;
        background: #0f766e;
    }

    .ppmf-map-stat-card .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(15, 118, 110, .10);
        color: #0f766e;
        font-size: 20px;
        flex-shrink: 0;
    }

    .ppmf-map-stat-card span {
        display: block;
        font-size: 11px;
        font-weight: 900;
        color: #64748b;
        letter-spacing: .07em;
        text-transform: uppercase;
    }

    .ppmf-map-stat-card strong {
        display: block;
        margin-top: 2px;
        font-size: 24px;
        line-height: 1;
        font-weight: 900;
        color: #0f172a;
        letter-spacing: -.03em;
    }

    .ppmf-map-stat-card.today::before { background: #7c3aed; }
    .ppmf-map-stat-card.today .stat-icon { background: rgba(124, 58, 237, .12); color: #6d28d9; }

    .ppmf-map-stat-card.approved::before { background: #16a34a; }
    .ppmf-map-stat-card.approved .stat-icon { background: rgba(22, 163, 74, .10); color: #15803d; }
    .ppmf-map-stat-card.reviewed::before { background: #0284c7; }
    .ppmf-map-stat-card.reviewed .stat-icon { background: rgba(2, 132, 199, .10); color: #0369a1; }
    .ppmf-map-stat-card.pending::before { background: #d97706; }
    .ppmf-map-stat-card.pending .stat-icon { background: rgba(217, 119, 6, .12); color: #b45309; }
    .ppmf-map-stat-card.rejected::before { background: #dc2626; }
    .ppmf-map-stat-card.rejected .stat-icon { background: rgba(220, 38, 38, .10); color: #b91c1c; }

    .ppmf-filter-panel {
        border-radius: 20px;
        border: 1px solid rgba(15, 23, 42, .08);
        box-shadow: 0 14px 30px rgba(15, 23, 42, .06);
    }

    .ppmf-filter-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 20px 14px;
        border-bottom: 1px solid rgba(226, 232, 240, .9);
        background: linear-gradient(180deg, #ffffff, #f8fafc);
        border-radius: 20px 20px 0 0;
    }

    .ppmf-filter-head p,
    .card-subtitle {
        color: #64748b;
        font-size: 12.5px;
        font-weight: 600;
    }

    .ppmf-filter-reset {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 12px;
        border-radius: 12px;
        color: #0f766e;
        background: rgba(15, 118, 110, .08);
        font-size: 12px;
        font-weight: 900;
        text-decoration: none;
        white-space: nowrap;
    }

    .ppmf-filter-reset:hover {
        color: #134e4a;
        background: rgba(15, 118, 110, .14);
    }

    .ppmf-map-filter-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1.35fr 1fr 1fr 1fr 120px;
        gap: 14px;
        align-items: end;
        padding-top: 18px;
    }

    .ppmf-filter-field label {
        display: block;
        margin-bottom: 7px;
        color: #334155;
        font-size: 12px;
        font-weight: 900;
    }

    .ppmf-control {
        min-height: 42px;
        border-radius: 12px;
        border-color: #cbd5e1;
        color: #334155;
        font-size: 13px;
        font-weight: 700;
        box-shadow: none !important;
    }

    .ppmf-control:focus {
        border-color: #0f766e;
        box-shadow: 0 0 0 .18rem rgba(15, 118, 110, .12) !important;
    }

    .ppmf-map-card,
    .ppmf-location-panel {
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 14px 30px rgba(15, 23, 42, .06);
    }

    .ppmf-map-card-header {
        align-items: center;
    }

    .ppmf-map-tools {
        display: flex;
        gap: 8px;
    }

    .ppmf-map-tool-btn {
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #0f766e;
        border-radius: 12px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 900;
    }

    .ppmf-live-map-wrap {
        position: relative;
        min-height: 620px;
        border-radius: 18px;
        overflow: hidden;
        border: 1px solid #dbe3ea;
        background: #e2e8f0;
    }

    .ppmf-live-map {
        width: 100%;
        height: 620px;
        min-height: 620px;
        z-index: 1;
    }

    .leaflet-popup-content-wrapper {
        border-radius: 14px;
        box-shadow: 0 18px 40px rgba(15, 23, 42, .20);
    }

    .ppmf-map-popup h6 {
        margin: 0 0 6px;
        color: #0f172a;
        font-size: 14px;
        font-weight: 900;
    }

    .ppmf-map-popup p {
        margin: 0 0 5px;
        color: #475569;
        font-size: 12px;
        font-weight: 600;
    }

    .ppmf-map-popup a {
        display: inline-flex;
        margin-top: 6px;
        color: #0f766e;
        font-size: 12px;
        font-weight: 900;
        text-decoration: none;
    }

    .ppmf-map-empty-state {
        position: absolute;
        inset: 0;
        z-index: 2;
        display: grid;
        place-content: center;
        text-align: center;
        padding: 32px;
        background: linear-gradient(135deg, rgba(15, 23, 42, .78), rgba(15, 118, 110, .72));
        color: #ffffff;
    }

    .ppmf-map-empty-state i {
        font-size: 52px;
        margin-bottom: 12px;
    }

    .ppmf-map-empty-state h5 {
        font-weight: 900;
        margin-bottom: 6px;
    }

    .ppmf-map-empty-state p {
        margin: 0;
        opacity: .88;
    }

    .ppmf-map-legend {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        gap: 14px;
        margin-top: 14px;
        padding: 12px;
        border-radius: 14px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }

    .ppmf-map-legend span {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        color: #475569;
        font-size: 12px;
        font-weight: 800;
    }

    .legend-dot {
        width: 11px;
        height: 11px;
        border-radius: 999px;
        display: inline-block;
    }

    .legend-dot.submitted { background: #d97706; }
    .legend-dot.reviewed { background: #0284c7; }
    .legend-dot.approved { background: #16a34a; }
    .legend-dot.rejected { background: #dc2626; }

    .location-list-ppmf {
        max-height: 700px;
        overflow-y: auto;
        display: grid;
        gap: 12px;
        padding-right: 4px;
    }

    .location-record-card {
        position: relative;
        border: 1px solid #e2e8f0;
        border-left: 5px solid #d97706;
        border-radius: 16px;
        padding: 14px;
        background: #fff;
        transition: .2s ease;
    }

    .location-record-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 26px rgba(15, 23, 42, .08);
    }

    .location-record-card.approved { border-left-color: #16a34a; background: linear-gradient(90deg, rgba(22, 163, 74, .055), #fff 38%); }
    .location-record-card.reviewed { border-left-color: #0284c7; background: linear-gradient(90deg, rgba(2, 132, 199, .055), #fff 38%); }
    .location-record-card.rejected { border-left-color: #dc2626; background: linear-gradient(90deg, rgba(220, 38, 38, .055), #fff 38%); }
    .location-record-card.submitted { border-left-color: #d97706; background: linear-gradient(90deg, rgba(217, 119, 6, .06), #fff 38%); }

    .location-record-top {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        align-items: flex-start;
        margin-bottom: 10px;
    }

    .location-record-top h6 {
        font-size: 14px;
        font-weight: 900;
        color: #0f172a;
        margin-bottom: 3px;
    }

    .location-record-top p {
        font-size: 12px;
        color: #64748b;
        font-weight: 700;
        margin-bottom: 0;
    }

    .ppmf-status-pill {
        display: inline-flex;
        align-items: center;
        padding: 5px 9px;
        border-radius: 999px;
        font-size: 10.5px;
        font-weight: 900;
        letter-spacing: .03em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .ppmf-status-pill.approved { background: #dcfce7; color: #166534; }
    .ppmf-status-pill.reviewed { background: #e0f2fe; color: #075985; }
    .ppmf-status-pill.rejected { background: #fee2e2; color: #991b1b; }
    .ppmf-status-pill.submitted { background: #fef3c7; color: #92400e; }

    .location-record-meta {
        display: grid;
        gap: 6px;
        margin-bottom: 12px;
    }

    .location-record-meta div {
        font-size: 12.5px;
        color: #475569;
        display: flex;
        align-items: flex-start;
        gap: 8px;
        line-height: 1.35;
    }

    .location-record-meta i {
        color: #0f766e;
        margin-top: 1px;
    }

    .location-record-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .marker-pin {
        width: 28px;
        height: 28px;
        border-radius: 50% 50% 50% 0;
        transform: rotate(-45deg);
        border: 3px solid #ffffff;
        box-shadow: 0 6px 14px rgba(15, 23, 42, .28);
    }

    .marker-pin span {
        width: 10px;
        height: 10px;
        background: #ffffff;
        border-radius: 50%;
        position: absolute;
        top: 6px;
        left: 6px;
    }

    .marker-approved { background: #16a34a; }
    .marker-reviewed { background: #0284c7; }
    .marker-rejected { background: #dc2626; }
    .marker-submitted { background: #d97706; }

    @media (max-width: 1399px) {
        .ppmf-map-filter-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 991px) {
        .ppmf-map-stats-grid,
        .ppmf-map-filter-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .ppmf-live-map-wrap,
        .ppmf-live-map {
            min-height: 480px;
            height: 480px;
        }
    }

    @media (max-width: 575px) {
        .ppmf-map-stats-grid,
        .ppmf-map-filter-grid {
            grid-template-columns: 1fr;
        }

        .ppmf-filter-head,
        .ppmf-map-card-header {
            flex-direction: column;
            align-items: stretch;
        }

        .ppmf-live-map-wrap,
        .ppmf-live-map {
            min-height: 380px;
            height: 380px;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const filterForm = document.getElementById('inspectionMapFilterForm');
        document.querySelectorAll('.auto-submit-filter').forEach(function (element) {
            element.addEventListener('change', function () {
                filterForm.submit();
            });
        });

        const mapElement = document.getElementById('inspectionLeafletMap');
        const points = @json($mapPoints);

        if (!mapElement || typeof L === 'undefined') {
            return;
        }

        const map = L.map('inspectionLeafletMap', {
            scrollWheelZoom: true,
            zoomControl: true,
        }).setView([31.1704, 72.7097], 7);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const markerGroup = L.featureGroup().addTo(map);

        function markerColor(status) {
            if (status === 'approved') return 'marker-approved';
            if (status === 'reviewed') return 'marker-reviewed';
            if (status === 'rejected') return 'marker-rejected';
            return 'marker-submitted';
        }

        points.forEach(function (point) {
            const icon = L.divIcon({
                className: '',
                html: `<div class="marker-pin ${markerColor(point.status)}"><span></span></div>`,
                iconSize: [28, 28],
                iconAnchor: [14, 28],
                popupAnchor: [0, -28]
            });

            const popupHtml = `
                <div class="ppmf-map-popup">
                    <h6>${point.title ?? 'Inspection Record'}</h6>
                    <p><strong>Category:</strong> ${point.category ?? 'N/A'}</p>
                    <p><strong>District:</strong> ${point.district ?? 'N/A'}${point.tehsil ? ' / ' + point.tehsil : ''}</p>
                    <p><strong>Status:</strong> ${(point.status ?? 'submitted').replace('_', ' ')}</p>
                    <p><strong>Date:</strong> ${point.date ?? 'N/A'}</p>
                    <a href="${point.detail_url}" target="_blank">View Detail</a>
                    &nbsp; | &nbsp;
                    <a href="${point.google_url}" target="_blank">Google Map</a>
                </div>
            `;

            L.marker([point.lat, point.lng], { icon: icon })
                .bindPopup(popupHtml)
                .addTo(markerGroup);
        });

        function fitMarkers() {
            if (points.length > 0) {
                map.fitBounds(markerGroup.getBounds(), { padding: [32, 32], maxZoom: 12 });
            }
        }

        fitMarkers();

        const fitMapBtn = document.getElementById('fitMapBtn');
        if (fitMapBtn) {
            fitMapBtn.addEventListener('click', fitMarkers);
        }
    });
</script>
@endpush

@extends('layouts.app')

@section('title', 'Inspection Map')

@section('content')

@php
    use Illuminate\Support\Str;
    use Carbon\Carbon;

    $filters = $filters ?? [];
    $mapRecords = collect($mapRecords ?? []);

    $totalRecords = $mapRecords->count();

    $displayDetailLabels = [
        'name_of_street' => 'Name of Street',
        'street_name' => 'Name of Street',
        'road_name' => 'Name of Street',
        'name_of_uc' => 'Name of UC',
        'uc_name' => 'Name of UC',
        'union_council' => 'Name of UC',
        'plant_name' => 'Plant Name',
        'hall_name' => 'Hall Name',
        'shop_name' => 'Shop/Tandoor Name',
        'tandoor_name' => 'Tandoor Name',
        'location_name' => 'Location',
        'facility_name' => 'Facility Name',
    ];

    $mapPoints = $mapRecords
        ->filter(fn ($record) => !empty($record->latitude) && !empty($record->longitude))
        ->map(function ($record) use ($displayDetailLabels) {
            $categoryName = $record->kpiCategory->name ?? 'Other KPI';
            $categorySlug = $record->kpiCategory->slug ?? Str::slug($categoryName);

            $performerName = $record->performer->name
                ?? $record->performer->username
                ?? $record->performedBy->name
                ?? $record->performedBy->username
                ?? 'N/A';

            $detailData = $record->detail_data ?? [];
            if (is_string($detailData)) {
                $decoded = json_decode($detailData, true);
                $detailData = is_array($decoded) ? $decoded : [];
            }
            $detailData = is_array($detailData) ? $detailData : [];

            $detailRows = [];
            foreach ($displayDetailLabels as $key => $label) {
                if (isset($detailData[$key]) && $detailData[$key] !== '' && count($detailRows) < 4) {
                    $detailRows[] = [
                        'label' => $label,
                        'value' => is_array($detailData[$key]) ? implode(', ', $detailData[$key]) : $detailData[$key],
                    ];
                }
            }

            if (empty($detailRows)) {
                foreach ($detailData as $key => $value) {
                    if (count($detailRows) >= 4) break;
                    if (is_null($value) || $value === '' || is_array($value) || is_object($value)) continue;
                    $detailRows[] = [
                        'label' => Str::of($key)->replace('_', ' ')->title()->toString(),
                        'value' => $value,
                    ];
                }
            }

            if (empty($detailRows) && !empty($record->main_title)) {
                $detailRows[] = ['label' => 'Primary Detail', 'value' => $record->main_title];
            }

            if (!empty($record->main_address) && count($detailRows) < 4) {
                $detailRows[] = ['label' => 'Address', 'value' => $record->main_address];
            }

            return [
                'id' => $record->id,
                'lat' => (float) $record->latitude,
                'lng' => (float) $record->longitude,
                'kpi_name' => $categoryName,
                'kpi_slug' => $categorySlug,
                'district' => $record->district->name ?? 'N/A',
                'tehsil' => optional($record->tehsil)->name,
                'date' => $record->inspection_datetime
                    ? Carbon::parse($record->inspection_datetime)->format('d M, Y H:i')
                    : 'N/A',
                'title' => $record->main_title ?? 'Inspection Record',
                'address' => $record->main_address ?? 'N/A',
                'performed_by' => $performerName,
                'detail_rows' => $detailRows,
                'detail_url' => route('inspections.show', $record->id),
            ];
        })
        ->values();

    $categoryLegend = $mapPoints
        ->unique('kpi_slug')
        ->map(fn ($point) => [
            'name' => $point['kpi_name'],
            'slug' => $point['kpi_slug'],
        ])
        ->values();

    $districtChartRows = $mapPoints
        ->groupBy(fn ($point) => $point['district'] ?: 'N/A')
        ->map(fn ($items, $district) => [
            'district' => $district,
            'count' => $items->count(),
        ])
        ->sortByDesc('count')
        ->values()
        ->take(15)
        ->values();
@endphp

<div class="page-title-bar ppmf-map-titlebar">
    <div>
        <div class="ppmf-eyebrow">
            <i class="bi bi-geo-alt-fill"></i>
            Geo Monitoring
        </div>
        <h1 class="page-title">Inspection Map</h1>
        <p class="page-subtitle">
            KPI category-wise geo-tagged inspection pins across Punjab.
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

<div class="ppmf-map-summary-strip mb-4">
    <div class="ppmf-map-summary-item is-total">
        <span>Total Records</span>
        <strong>{{ number_format($totalRecords) }}</strong>
    </div>
    <div class="ppmf-map-summary-item is-mapped">
        <span>Mapped Pins</span>
        <strong>{{ number_format($mapPoints->count()) }}</strong>
    </div>
    <div class="ppmf-map-summary-item is-category">
        <span>KPI Categories</span>
        <strong>{{ number_format($categoryLegend->count()) }}</strong>
    </div>
</div>

<div class="card-ppmf ppmf-filter-panel mb-4">
    <div class="ppmf-filter-head">
        <div>
            <div class="card-ppmf-title">
                <i class="bi bi-funnel"></i>
                Map Filters
            </div>
            <p class="mb-0">Filter pins by district, tehsil, KPI category and inspection date.</p>
        </div>

        <a href="{{ route('inspections.map') }}" class="ppmf-filter-reset">
            <i class="bi bi-arrow-clockwise"></i>
            Reset
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
                    <label>KPI Category / Pin Type</label>
                    <select name="kpi_category_id" class="form-select ppmf-control auto-submit-filter">
                        <option value="">All KPI Categories</option>
                        @foreach ($kpiCategories as $category)
                            <option value="{{ $category->id }}" {{ ($filters['kpi_category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="ppmf-filter-field">
                    <label>From</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control ppmf-control auto-submit-filter">
                </div>

                <div class="ppmf-filter-field">
                    <label>To</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control ppmf-control auto-submit-filter">
                </div>

                <div class="ppmf-filter-actions">
                    <button type="submit" class="btn-gov btn-gov-primary w-100">
                        <i class="bi bi-search"></i>
                        Search
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card-ppmf ppmf-map-card mb-4">
    <div class="card-ppmf-header ppmf-map-card-header">
        <div>
            <div class="card-ppmf-title">
                <i class="bi bi-map"></i>
                Punjab Inspection Location Map
            </div>
            <p class="card-subtitle mb-0">
                Each pin icon represents a KPI category. Click a pin to view inspection detail.
            </p>
        </div>
        <button type="button" class="ppmf-map-tool-btn" id="fitMapBtn">
            <i class="bi bi-arrows-fullscreen"></i>
            Fit Punjab Pins
        </button>
    </div>

    <div class="card-ppmf-body">
        <div class="ppmf-live-map-wrap">
            <div id="inspectionLeafletMap" class="ppmf-live-map"></div>

            @if($mapPoints->isEmpty())
                <div class="ppmf-map-empty-state">
                    <i class="bi bi-geo-alt"></i>
                    <h5>No geo-tagged inspection pins found</h5>
                    <p>Change filters or add latitude/longitude in inspection records.</p>
                </div>
            @endif
        </div>

        <div class="ppmf-kpi-pin-legend" id="kpiPinLegend">
            @forelse($categoryLegend as $legend)
                <span class="ppmf-kpi-legend-item" data-kpi-slug="{{ $legend['slug'] }}" data-kpi-name="{{ $legend['name'] }}">
                    <span class="ppmf-kpi-legend-icon"><i class="bi bi-geo-alt-fill"></i></span>
                    <span>{{ $legend['name'] }}</span>
                </span>
            @empty
                <span class="ppmf-kpi-legend-item" data-kpi-slug="default" data-kpi-name="Other KPI">
                    <span class="ppmf-kpi-legend-icon"><i class="bi bi-geo-alt-fill"></i></span>
                    <span>No KPI category pins available</span>
                </span>
            @endforelse
        </div>
    </div>
</div>

<div class="card-ppmf ppmf-chart-card mb-4">
    <div class="card-ppmf-header ppmf-chart-card-header">
        <div>
            <div class="card-ppmf-title">
                <i class="bi bi-bar-chart-line"></i>
                District Wise Inspection Count
            </div>
            <p class="card-subtitle mb-0">Count of visible geo-tagged inspection pins by district.</p>
        </div>
    </div>
    <div class="card-ppmf-body">
        @if($districtChartRows->isNotEmpty())
            <div class="ppmf-chart-wrap">
                <canvas id="districtInspectionCountChart"></canvas>
            </div>
        @else
            <div class="ppmf-chart-empty">
                <i class="bi bi-bar-chart"></i>
                <span>No district-wise inspection count available.</span>
            </div>
        @endif
    </div>
</div>

@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
<style>
    .ppmf-map-titlebar{border-bottom:1px solid rgba(15,23,42,.06);margin-bottom:18px}.ppmf-eyebrow{display:inline-flex;align-items:center;gap:7px;padding:6px 10px;border-radius:999px;background:rgba(20,184,166,.10);color:#0f766e;font-size:11px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;margin-bottom:9px}.card-subtitle{color:#64748b;font-size:12.5px;font-weight:600}.ppmf-map-summary-strip{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.ppmf-map-summary-item{padding:16px 18px;border-radius:18px;background:linear-gradient(180deg,#fff,#f8fafc);border:1px solid rgba(15,23,42,.08);box-shadow:0 12px 26px rgba(15,23,42,.05);border-left:5px solid #0f766e}.ppmf-map-summary-item.is-total{border-left-color:#2563eb}.ppmf-map-summary-item.is-mapped{border-left-color:#0f766e}.ppmf-map-summary-item.is-category{border-left-color:#7c3aed}.ppmf-map-summary-item span{display:block;font-size:11px;font-weight:900;color:#64748b;letter-spacing:.07em;text-transform:uppercase}.ppmf-map-summary-item strong{display:block;margin-top:3px;font-size:24px;line-height:1;font-weight:900;color:#0f172a}.ppmf-filter-panel,.ppmf-map-card,.ppmf-chart-card{border-radius:20px;overflow:hidden;box-shadow:0 14px 30px rgba(15,23,42,.06)}.ppmf-filter-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:18px 20px 14px;border-bottom:1px solid rgba(226,232,240,.9);background:linear-gradient(180deg,#fff,#f8fafc)}.ppmf-filter-head p{color:#64748b;font-size:12.5px;font-weight:600}.ppmf-filter-reset{display:inline-flex;align-items:center;gap:7px;padding:9px 12px;border-radius:12px;color:#0f766e;background:rgba(15,118,110,.08);font-size:12px;font-weight:900;text-decoration:none;white-space:nowrap}.ppmf-map-filter-grid{display:grid;grid-template-columns:1fr 1fr 1.55fr 1fr 1fr 120px;gap:14px;align-items:end;padding-top:18px}.ppmf-filter-field label{display:block;margin-bottom:7px;color:#334155;font-size:12px;font-weight:900}.ppmf-control{min-height:42px;border-radius:12px;border-color:#cbd5e1;color:#334155;font-size:13px;font-weight:700;box-shadow:none!important}.ppmf-control:focus{border-color:#0f766e;box-shadow:0 0 0 .18rem rgba(15,118,110,.12)!important}.ppmf-map-card-header,.ppmf-chart-card-header{align-items:center}.ppmf-map-tool-btn{border:1px solid #cbd5e1;background:#fff;color:#0f766e;border-radius:12px;padding:8px 12px;font-size:12px;font-weight:900}.ppmf-live-map-wrap{position:relative;min-height:650px;border-radius:18px;overflow:hidden;border:1px solid #dbe3ea;background:#e2e8f0}.ppmf-live-map{width:100%;height:650px;min-height:650px;z-index:1}.leaflet-container{background:#e8f2ef}.ppmf-map-empty-state{position:absolute;inset:0;z-index:2;display:grid;place-content:center;text-align:center;padding:32px;background:linear-gradient(135deg,rgba(15,23,42,.78),rgba(15,118,110,.72));color:#fff}.ppmf-map-empty-state i{font-size:52px;margin-bottom:12px}.ppmf-map-empty-state h5{font-weight:900;margin-bottom:6px}.ppmf-map-empty-state p{margin:0;opacity:.88}.leaflet-popup-content-wrapper{border-radius:16px;box-shadow:0 18px 42px rgba(15,23,42,.22);overflow:hidden}.leaflet-popup-content{margin:0}.ppmf-map-popup{min-width:280px;max-width:340px;overflow:hidden}.ppmf-map-popup-head{display:flex;gap:10px;align-items:center;padding:13px 14px;color:#fff}.ppmf-map-popup-icon{width:34px;height:34px;border-radius:12px;background:rgba(255,255,255,.18);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0}.ppmf-map-popup-head h6{margin:0;font-size:13.5px;font-weight:900;color:#fff}.ppmf-map-popup-head span{display:block;margin-top:2px;font-size:11px;font-weight:700;opacity:.88}.ppmf-map-popup-body{padding:12px 14px}.ppmf-map-popup-body p{margin:0 0 7px;color:#475569;font-size:12px;font-weight:600}.ppmf-map-popup-body strong{color:#0f172a}.ppmf-map-popup-actions{display:flex;gap:8px;margin-top:10px}.ppmf-popup-link{display:inline-flex;align-items:center;gap:5px;padding:7px 9px;border-radius:10px;background:#ecfdf5;color:#0f766e;font-size:12px;font-weight:900;text-decoration:none}.ppmf-pin{width:34px;height:34px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);display:flex;align-items:center;justify-content:center;border:3px solid #fff;box-shadow:0 7px 16px rgba(15,23,42,.30)}.ppmf-pin i{color:#fff;font-size:15px;transform:rotate(45deg)}.ppmf-kpi-pin-legend{display:flex;align-items:center;justify-content:flex-start;flex-wrap:wrap;gap:10px;margin-top:14px;padding:13px;border-radius:15px;background:#f8fafc;border:1px solid #e2e8f0}.ppmf-kpi-legend-item{display:inline-flex;align-items:center;gap:8px;padding:7px 10px;border-radius:999px;background:#fff;border:1px solid #e2e8f0;color:#334155;font-size:12px;font-weight:800}.ppmf-kpi-legend-icon{width:24px;height:24px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:#0f766e;color:#fff;font-size:12px}.ppmf-district-label-marker span{display:inline-flex;align-items:center;justify-content:center;padding:3px 8px;border-radius:999px;background:rgba(255,255,255,.92);border:1px solid rgba(15,118,110,.25);color:#0f172a;font-size:11px;font-weight:900;box-shadow:0 4px 10px rgba(15,23,42,.12);white-space:nowrap}.ppmf-chart-wrap{height:360px;position:relative}.ppmf-chart-empty{height:240px;display:flex;align-items:center;justify-content:center;gap:10px;border:1px dashed #cbd5e1;border-radius:18px;color:#64748b;font-weight:800;background:#f8fafc}.btn-gov-sm{padding:6px 10px;font-size:12px;border-radius:10px}@media(max-width:1399px){.ppmf-map-filter-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:991px){.ppmf-map-summary-strip,.ppmf-map-filter-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.ppmf-live-map-wrap,.ppmf-live-map{min-height:520px;height:520px}.ppmf-chart-wrap{height:330px}}@media(max-width:575px){.ppmf-map-summary-strip,.ppmf-map-filter-grid{grid-template-columns:1fr}.ppmf-filter-head,.ppmf-map-card-header,.ppmf-chart-card-header{flex-direction:column;align-items:stretch}.ppmf-live-map-wrap,.ppmf-live-map{min-height:420px;height:420px}.ppmf-chart-wrap{height:300px}}
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const filterForm = document.getElementById('inspectionMapFilterForm');
        document.querySelectorAll('.auto-submit-filter').forEach(function (element) {
            element.addEventListener('change', function () {
                if (filterForm) filterForm.submit();
            });
        });

        const mapElement = document.getElementById('inspectionLeafletMap');
        const points = @json($mapPoints);
        const districtChartRows = @json($districtChartRows);

        const iconPalette = [
            { icon: 'bi-droplet-fill', color: '#0284c7' },
            { icon: 'bi-building-fill', color: '#7c3aed' },
            { icon: 'bi-lightbulb-fill', color: '#ca8a04' },
            { icon: 'bi-shop', color: '#16a34a' },
            { icon: 'bi-cone-striped', color: '#d97706' },
            { icon: 'bi-record-circle-fill', color: '#ea580c' },
            { icon: 'bi-exclamation-triangle-fill', color: '#dc2626' },
            { icon: 'bi-trash3-fill', color: '#0d9488' },
            { icon: 'bi-hospital-fill', color: '#059669' },
            { icon: 'bi-book-fill', color: '#2563eb' },
            { icon: 'bi-tree-fill', color: '#65a30d' },
            { icon: 'bi-water', color: '#0891b2' },
            { icon: 'bi-house-door-fill', color: '#9333ea' },
            { icon: 'bi-signpost-split-fill', color: '#be123c' },
            { icon: 'bi-clipboard2-check-fill', color: '#0f766e' },
            { icon: 'bi-geo-alt-fill', color: '#334155' },
        ];

        const keywordStyles = [
            { words: ['water', 'filtration', 'plant'], icon: 'bi-droplet-fill', color: '#0284c7' },
            { words: ['marriage', 'hall', 'function'], icon: 'bi-building-fill', color: '#7c3aed' },
            { words: ['street', 'light'], icon: 'bi-lightbulb-fill', color: '#ca8a04' },
            { words: ['roti', 'price', 'tandoor', 'bread'], icon: 'bi-shop', color: '#16a34a' },
            { words: ['encroachment', 'road', 'footpath'], icon: 'bi-cone-striped', color: '#d97706' },
            { words: ['manhole', 'sewer'], icon: 'bi-record-circle-fill', color: '#ea580c' },
            { words: ['stray', 'dog'], icon: 'bi-exclamation-triangle-fill', color: '#dc2626' },
            { words: ['clean', 'solid', 'waste'], icon: 'bi-trash3-fill', color: '#0d9488' },
            { words: ['health', 'hospital'], icon: 'bi-hospital-fill', color: '#059669' },
            { words: ['school', 'education'], icon: 'bi-book-fill', color: '#2563eb' },
            { words: ['park', 'plantation'], icon: 'bi-tree-fill', color: '#65a30d' },
        ];

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>'"]/g, function (char) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' })[char];
            });
        }

        function hashCode(value) {
            let hash = 0;
            const text = String(value || 'default');
            for (let i = 0; i < text.length; i++) {
                hash = ((hash << 5) - hash) + text.charCodeAt(i);
                hash |= 0;
            }
            return Math.abs(hash);
        }

        function getKpiStyle(slug, name) {
            const text = `${slug ?? ''} ${name ?? ''}`.toLowerCase();
            const keywordStyle = keywordStyles.find(item => item.words.some(word => text.includes(word)));
            if (keywordStyle) return keywordStyle;
            return iconPalette[hashCode(slug || name) % iconPalette.length];
        }

        function adjustedLatLng(point, index, seen) {
            const key = `${Number(point.lat).toFixed(5)}_${Number(point.lng).toFixed(5)}`;
            const current = seen[key] || 0;
            seen[key] = current + 1;

            if (current === 0) return [Number(point.lat), Number(point.lng)];

            const angle = current * 1.35;
            const radius = 0.0035 + (Math.floor(current / 8) * 0.0018);
            return [
                Number(point.lat) + Math.sin(angle) * radius,
                Number(point.lng) + Math.cos(angle) * radius,
            ];
        }

        if (mapElement && typeof L !== 'undefined') {
            const punjabCenter = [31.1704, 72.7097];
            const punjabBounds = L.latLngBounds([27.70, 69.10], [34.55, 75.85]);

            const map = L.map('inspectionLeafletMap', {
                scrollWheelZoom: false,
                zoomControl: true,
                maxBounds: punjabBounds.pad(0.12),
                maxBoundsViscosity: 0.85,
                minZoom: 6,
                maxZoom: 16,
            }).setView(punjabCenter, 7);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            const markerGroup = L.featureGroup().addTo(map);
            const seenCoordinates = {};

            function getKpiMarkerIcon(point) {
                const style = getKpiStyle(point.kpi_slug, point.kpi_name);
                return L.divIcon({
                    className: 'ppmf-kpi-marker',
                    html: `<div class="ppmf-pin" style="background:${style.color}"><i class="bi ${style.icon}"></i></div>`,
                    iconSize: [34, 42],
                    iconAnchor: [17, 42],
                    popupAnchor: [0, -42]
                });
            }

            function popupHtml(point) {
                const style = getKpiStyle(point.kpi_slug, point.kpi_name);
                const detailRows = Array.isArray(point.detail_rows) ? point.detail_rows : [];
                const detailHtml = detailRows.map(function (row) {
                    return `<p><strong>${escapeHtml(row.label)}:</strong> ${escapeHtml(row.value)}</p>`;
                }).join('');

                return `
                    <div class="ppmf-map-popup">
                        <div class="ppmf-map-popup-head" style="background:linear-gradient(135deg, ${style.color}, #0f766e)">
                            <span class="ppmf-map-popup-icon"><i class="bi ${style.icon}"></i></span>
                            <div>
                                <h6>Inspection Type: ${escapeHtml(point.kpi_name || 'Inspection')}</h6>
                                <span>Date & Time: ${escapeHtml(point.date || 'N/A')}</span>
                            </div>
                        </div>
                        <div class="ppmf-map-popup-body">
                            <p><strong>District:</strong> ${escapeHtml(point.district || 'N/A')}</p>
                            <p><strong>Tehsil:</strong> ${escapeHtml(point.tehsil || 'N/A')}</p>
                            ${detailHtml}
                            <p><strong>Performed By:</strong> ${escapeHtml(point.performed_by || 'N/A')}</p>
                            <div class="ppmf-map-popup-actions">
                                <a class="ppmf-popup-link" href="${point.detail_url}" target="_blank"><i class="bi bi-eye"></i> View Detail</a>
                            </div>
                        </div>
                    </div>`;
            }

            points.forEach(function (point, index) {
                if (!point.lat || !point.lng) return;
                const latLng = adjustedLatLng(point, index, seenCoordinates);

                L.marker(latLng, { icon: getKpiMarkerIcon(point) })
                    .bindPopup(popupHtml(point), { maxWidth: 360 })
                    .addTo(markerGroup);
            });

            const districtNames = Array.from(new Set(points.map(p => p.district).filter(Boolean)));
            districtNames.slice(0, 42).forEach(function (districtName) {
                const districtPoints = points.filter(p => p.district === districtName);
                if (!districtPoints.length) return;

                const avgLat = districtPoints.reduce((sum, p) => sum + Number(p.lat), 0) / districtPoints.length;
                const avgLng = districtPoints.reduce((sum, p) => sum + Number(p.lng), 0) / districtPoints.length;

                L.marker([avgLat, avgLng], {
                    icon: L.divIcon({
                        className: 'ppmf-district-label-marker',
                        html: `<span>${escapeHtml(districtName)}</span>`,
                        iconSize: [92, 22],
                        iconAnchor: [46, 11]
                    }),
                    interactive: false
                }).addTo(map);
            });

            document.querySelectorAll('.ppmf-kpi-legend-item').forEach(function (item) {
                const slug = item.getAttribute('data-kpi-slug');
                const name = item.getAttribute('data-kpi-name') || item.innerText.trim();
                const style = getKpiStyle(slug, name);
                const iconWrap = item.querySelector('.ppmf-kpi-legend-icon');
                if (iconWrap) {
                    iconWrap.style.background = style.color;
                    iconWrap.innerHTML = `<i class="bi ${style.icon}"></i>`;
                }
            });

            function fitPunjabPins() {
                if (markerGroup.getLayers().length > 0) {
                    map.fitBounds(markerGroup.getBounds(), { padding: [50, 50], maxZoom: 10 });
                } else {
                    map.fitBounds(punjabBounds, { padding: [30, 30] });
                    map.setZoom(7);
                }
                setTimeout(() => map.invalidateSize(), 150);
            }

            fitPunjabPins();

            const fitMapBtn = document.getElementById('fitMapBtn');
            if (fitMapBtn) fitMapBtn.addEventListener('click', fitPunjabPins);
        }

        const chartCanvas = document.getElementById('districtInspectionCountChart');
        if (chartCanvas && typeof Chart !== 'undefined' && districtChartRows.length > 0) {
            new Chart(chartCanvas, {
                type: 'bar',
                data: {
                    labels: districtChartRows.map(row => row.district),
                    datasets: [{
                        label: 'Inspection Count',
                        data: districtChartRows.map(row => row.count),
                        borderWidth: 1,
                        borderRadius: 8,
                        maxBarThickness: 34,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: context => `Inspections: ${context.parsed.y}`
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: { font: { size: 11, weight: '600' }, maxRotation: 45, minRotation: 0 },
                            grid: { display: false }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 },
                            grid: { color: 'rgba(148,163,184,.22)' }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush

@php
    $map = $healthMap ?? [];
    $pins = $map['pins'] ?? [];
    $pinCount = (int) ($map['pin_count'] ?? count($pins));
@endphp

<div class="ppmu-health-map-block" id="kpiDetailHealthMap">
    <div class="ppmu-section-head mt-4">
        <div>
            <h2><i class="bi bi-geo-alt-fill"></i> {{ $map['title'] ?? 'Health Facility Inspection Map' }}</h2>
            <p>{{ $map['subtitle'] ?? 'Showing health inspection locations for the selected period. Click any pin to view inspection detail.' }}</p>
        </div>
    </div>

    <div class="card-ppmf ppmu-health-map-panel">
        <div class="ppmu-health-map-toolbar">
            <div class="ppmu-health-map-scope" id="ppmuHealthMapScope">
                <i class="bi bi-pin-map-fill" aria-hidden="true"></i>
                <span class="ppmu-health-map-scope-label">{{ $map['scope_label'] ?? 'Inspection locations' }}</span>
            </div>
            <span class="ppmu-health-map-pin-count" id="ppmuHealthMapPinCount">
                {{ $pinCount }} {{ $pinCount === 1 ? 'inspection mapped' : 'inspections mapped' }}
            </span>
        </div>

        <div class="ppmu-health-map-frame-wrap {{ $pinCount === 0 ? 'is-empty' : '' }}" id="ppmuHealthMapFrameWrap">
            <div id="ppmuHealthDashboardMap" class="ppmu-health-dashboard-map" aria-label="Health facility inspection map"></div>
            <div class="ppmu-health-map-empty" id="ppmuHealthMapEmpty" @if($pinCount > 0) hidden @endif>
                <i class="bi bi-geo-alt"></i>
                <span>{{ $map['empty_message'] ?? 'No health inspections found for the selected period.' }}</span>
            </div>
        </div>

        @php $mapStatusCounts = $map['status_counts'] ?? null; @endphp
        <div class="ppmu-health-map-legend" aria-label="Map legend">
            <span class="ppmu-health-map-legend-item is-grey" data-map-status="not_inspected"><i class="ppmu-health-map-legend-dot grey"></i> Not Inspected{{ $mapStatusCounts ? ' ('.number_format($mapStatusCounts['not_inspected'] ?? 0).')' : '' }}</span>
            <span class="ppmu-health-map-legend-item is-blue" data-map-status="inspected"><i class="ppmu-health-map-legend-dot blue"></i> Inspected{{ $mapStatusCounts ? ' ('.number_format($mapStatusCounts['inspected'] ?? 0).')' : '' }}</span>
            <span class="ppmu-health-map-legend-item is-orange" data-map-status="pending_review"><i class="ppmu-health-map-legend-dot orange"></i> Pending Review{{ $mapStatusCounts ? ' ('.number_format($mapStatusCounts['pending_review'] ?? 0).')' : '' }}</span>
            <span class="ppmu-health-map-legend-item is-green" data-map-status="approved"><i class="ppmu-health-map-legend-dot green"></i> Approved{{ $mapStatusCounts ? ' ('.number_format($mapStatusCounts['approved'] ?? 0).')' : '' }}</span>
            <span class="ppmu-health-map-legend-item is-red" data-map-status="rejected"><i class="ppmu-health-map-legend-dot red"></i> Rejected{{ $mapStatusCounts ? ' ('.number_format($mapStatusCounts['rejected'] ?? 0).')' : '' }}</span>
        </div>
        @if(array_key_exists('unmapped_count', $map))
            <p class="ppmu-health-map-unmapped mb-0" id="ppmuHealthMapUnmapped">Records without mapped location: <strong>{{ number_format((int) $map['unmapped_count']) }}</strong></p>
        @endif
    </div>
</div>

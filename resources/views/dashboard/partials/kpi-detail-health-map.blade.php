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

        <div class="ppmu-health-map-legend" aria-label="Map legend">
            <span class="ppmu-health-map-legend-item is-blue"><i class="ppmu-health-map-legend-dot blue"></i> Inspected</span>
            <span class="ppmu-health-map-legend-item is-orange"><i class="ppmu-health-map-legend-dot orange"></i> Pending Review</span>
            <span class="ppmu-health-map-legend-item is-green"><i class="ppmu-health-map-legend-dot green"></i> Approved</span>
            <span class="ppmu-health-map-legend-item is-red"><i class="ppmu-health-map-legend-dot red"></i> Rejected</span>
        </div>
    </div>
</div>

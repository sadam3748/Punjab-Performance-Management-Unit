@php
    $query = array_filter([
        'kpi_card_id' => $kpiCard->id,
        'period_type' => $period['period_type'] ?? null,
        'week_no' => $period['week_no'] ?? null,
        'month' => $period['month'] ?? null,
        'year' => $period['year'] ?? null,
        'date' => $period['date'] ?? null,
        'geo_division' => $geo['geo_division'] ?? null,
        'geo_district' => $geo['geo_district'] ?? null,
        'geo_tehsil' => $geo['geo_tehsil'] ?? null,
    ], fn ($v) => $v !== null && $v !== '');
    $total = $inspectionRecords->total();
@endphp

<div class="ppmu-section-head mt-4 ppmu-inspections-link-section" id="kpiInspectionsHead">
    <div>
        <h2><i class="bi bi-clipboard2-check-fill"></i> Field Inspections</h2>
        <p>
            <strong>{{ number_format($total) }}</strong> field inspection{{ $total === 1 ? '' : 's' }} in the selected period.
            Inspection evidence is tracked separately from submission reports.
        </p>
    </div>
    <a href="{{ route('inspections.index', $query) }}" class="btn btn-success btn-sm ppmu-view-inspections-btn">
        <i class="bi bi-clipboard2-check-fill me-1"></i>View Inspections
    </a>
</div>

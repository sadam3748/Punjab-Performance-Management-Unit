@php
    use App\Data\KpiLocationSlugs;

    $query = array_filter([
        'kpi_card_id' => $kpiCard->id,
        'geo_division' => $geo['geo_division'] ?? null,
        'geo_district' => $geo['geo_district'] ?? null,
        'geo_tehsil' => $geo['geo_tehsil'] ?? null,
        'insp_per_page' => 20,
    ], fn ($v) => $v !== null && $v !== '');
    $total = $inspectionRecords->total();
    $sectionTitle = KpiLocationSlugs::listSectionTitle($kpiCard->slug);
    $sectionDescription = KpiLocationSlugs::listSectionDescription($kpiCard->slug, $total);
@endphp

<div class="ppmu-section-head mt-4 ppmu-inspections-link-section" id="kpiInspectionsHead">
    <div>
        <h2><i class="bi bi-clipboard2-check-fill"></i> {{ $sectionTitle }}</h2>
        <p>{{ $sectionDescription }}</p>
    </div>
    <a href="{{ route('inspections.index', $query) }}" class="btn btn-success btn-sm ppmu-view-inspections-btn">
        <i class="bi bi-clipboard2-check-fill me-1"></i>View Inspections
    </a>
</div>

@php
    $filters = $inspectionFilters ?? [];
@endphp

<div class="ppmu-section-head mt-4" id="kpiInspectionsHead">
    <div>
        <h2><i class="bi bi-clipboard2-check-fill"></i> Inspection List</h2>
        <p>Field inspections with status and quick view access.</p>
    </div>
</div>

<form id="kpiInspectionFilter" class="ppmu-inspection-filter card-ppmf mb-3" method="GET" action="{{ route('kpi.dashboard', $kpiCard) }}">
    @foreach(request()->except(['insp_status', 'insp_per_page', 'insp_page', 'page']) as $key => $value)
        @if(is_scalar($value))
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach

    <div class="ppmu-inspection-filter-row">
        <label>
            <span>Status</span>
            <select name="insp_status" class="form-select form-select-sm" data-insp-filter="insp_status">
                <option value="">All statuses</option>
                @foreach(($filters['statuses'] ?? []) as $value => $label)
                    <option value="{{ $value }}" @selected(request('insp_status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>

        <label>
            <span>Per Page</span>
            <select name="insp_per_page" class="form-select form-select-sm" data-insp-filter="insp_per_page">
                @foreach([10, 25, 50] as $n)
                    <option value="{{ $n }}" @selected((int) request('insp_per_page', 10) === $n)>{{ $n }}</option>
                @endforeach
            </select>
        </label>

        <button type="submit" class="btn btn-sm ppmu-inspection-filter-btn">
            <i class="bi bi-funnel-fill"></i>
            Apply
        </button>
    </div>
</form>

<div class="card-ppmf ppmu-table-card ppmu-inspection-list-card">
    <x-kpi-inspection-table
        :kpi-card="$kpiCard"
        :inspection-records="$inspectionRecords"
        :table-columns="$inspectionTableColumns ?? []"
    />
</div>

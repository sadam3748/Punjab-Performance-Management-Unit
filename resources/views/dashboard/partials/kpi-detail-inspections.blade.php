@php
    $counts = $inspectionStatusCounts ?? ['total' => 0, 'pending_review' => 0, 'approved' => 0, 'rejected' => 0];
    $filters = $inspectionFilters ?? [];
@endphp

<div class="ppmu-section-head mt-4" id="kpiInspectionsHead">
    <div>
        <h2><i class="bi bi-clipboard2-check-fill"></i> Inspection Records</h2>
        <p>Field inspection evidence, location details, and review status for this KPI.</p>
    </div>
</div>

<div class="ppmu-inspection-summary-grid mb-3">
    <article class="ppmu-pi-card tone-blue">
        <div class="ppmu-pi-icon"><i class="bi bi-collection-fill"></i></div>
        <div class="ppmu-pi-body">
            <span class="ppmu-pi-label">Total Inspections</span>
            <strong class="ppmu-pi-value">{{ number_format($counts['total']) }}</strong>
        </div>
    </article>
    <article class="ppmu-pi-card tone-orange">
        <div class="ppmu-pi-icon"><i class="bi bi-hourglass-split"></i></div>
        <div class="ppmu-pi-body">
            <span class="ppmu-pi-label">Pending Review</span>
            <strong class="ppmu-pi-value">{{ number_format($counts['pending_review']) }}</strong>
        </div>
    </article>
    <article class="ppmu-pi-card tone-green">
        <div class="ppmu-pi-icon"><i class="bi bi-check-circle-fill"></i></div>
        <div class="ppmu-pi-body">
            <span class="ppmu-pi-label">Approved</span>
            <strong class="ppmu-pi-value">{{ number_format($counts['approved']) }}</strong>
        </div>
    </article>
    <article class="ppmu-pi-card tone-red">
        <div class="ppmu-pi-icon"><i class="bi bi-x-circle-fill"></i></div>
        <div class="ppmu-pi-body">
            <span class="ppmu-pi-label">Rejected</span>
            <strong class="ppmu-pi-value">{{ number_format($counts['rejected']) }}</strong>
        </div>
    </article>
</div>

<form id="kpiInspectionFilter" class="ppmu-inspection-filter card-ppmf mb-3" method="GET" action="{{ route('kpi.dashboard', $kpiCard) }}">
    @foreach(request()->except(['insp_status', 'insp_district', 'insp_tehsil', 'insp_date_from', 'insp_date_to', 'insp_per_page', 'insp_page', 'page']) as $key => $value)
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

        @if(!empty($filters['show_district_filter']))
            <label>
                <span>District</span>
                <select name="insp_district" class="form-select form-select-sm" data-insp-filter="insp_district">
                    <option value="">All districts</option>
                    @foreach(($filters['districts'] ?? []) as $id => $name)
                        <option value="{{ $id }}" @selected((string) request('insp_district') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </label>
        @endif

        @if(!empty($filters['show_tehsil_filter']))
            <label>
                <span>Tehsil</span>
                <select name="insp_tehsil" class="form-select form-select-sm" data-insp-filter="insp_tehsil">
                    <option value="">All tehsils</option>
                    @foreach(($filters['tehsils'] ?? []) as $id => $name)
                        <option value="{{ $id }}" @selected((string) request('insp_tehsil') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </label>
        @endif

        <label>
            <span>Date From</span>
            <input type="date" name="insp_date_from" value="{{ request('insp_date_from') }}" class="form-control form-control-sm" data-insp-filter="insp_date_from">
        </label>

        <label>
            <span>Date To</span>
            <input type="date" name="insp_date_to" value="{{ request('insp_date_to') }}" class="form-control form-control-sm" data-insp-filter="insp_date_to">
        </label>

        <label>
            <span>Per Page</span>
            <select name="insp_per_page" class="form-select form-select-sm" data-insp-filter="insp_per_page">
                @foreach([10, 25, 50] as $n)
                    <option value="{{ $n }}" @selected((int) request('insp_per_page', 10) === $n)>{{ $n }}</option>
                @endforeach
            </select>
        </label>

        <a href="{{ route('kpi.dashboard', $kpiCard) }}" class="btn btn-sm btn-outline-secondary">Reset</a>
    </div>
</form>

<div class="card-ppmf ppmu-table-card">
    <x-kpi-inspection-table :kpi-card="$kpiCard" :inspection-records="$inspectionRecords" />
</div>

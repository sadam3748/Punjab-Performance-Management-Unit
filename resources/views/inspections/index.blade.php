@extends('layouts.app')
@section('title', 'Inspections')
@section('content_class', 'ppmu-dashboard-content ppmu-inspections-page')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ppmu-kpi.css') }}?v={{ filemtime(public_path('css/ppmu-kpi.css')) }}">
@endpush

@section('content')
<div class="ppmu-inspections-page-inner">
    <div class="ppmu-detail-hero card-ppmf ppmu-inspection-index-hero">
        <div class="ppmu-detail-hero-main">
            <div class="ppmu-detail-info">
                <span class="ppmu-detail-category">Field Evidence · Inspection Module</span>
                <h1>Inspection List</h1>
                <p class="mb-0">Field inspections with status and quick view access.</p>
            </div>
        </div>
    </div>

    <form class="ppmu-inspection-page-filters card-ppmf mb-3" method="GET" action="{{ route('inspections.index') }}" id="inspectionPageFilters">
        <div class="ppmu-inspection-filter-row">
            <label>
                <span>KPI</span>
                <select name="kpi_card_id" class="form-select form-select-sm">
                    <option value="">All KPIs</option>
                    @foreach($kpiCards as $card)
                        <option value="{{ $card->id }}" @selected((string) ($selectedKpiCardId ?? '') === (string) $card->id)>{{ $card->title }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Review Status</span>
                <select name="insp_status" class="form-select form-select-sm">
                    <option value="">All statuses</option>
                    @foreach(($inspectionFilters['statuses'] ?? []) as $value => $label)
                        <option value="{{ $value }}" @selected(request('insp_status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Per Page</span>
                <select name="insp_per_page" class="form-select form-select-sm">
                    @foreach([10, 25, 50] as $n)
                        <option value="{{ $n }}" @selected((int) request('insp_per_page', 10) === $n)>{{ $n }}</option>
                    @endforeach
                </select>
            </label>
        </div>
    </form>

    <div class="ppmu-section-head ppmu-inspection-list-head">
        <div>
            <p id="inspectionDateRangeLabel">
                <i class="bi bi-calendar3"></i>
                Last completed day:
                <strong>{{ $inspectionDateRange['start']->format('d M Y') }}</strong>
                · 12:00 AM – 11:59 PM
            </p>
            <p><strong id="inspectionListCount">{{ number_format($inspectionRecords->total()) }}</strong> inspection{{ $inspectionRecords->total() === 1 ? '' : 's' }} in scope</p>
        </div>
    </div>

    <div class="card-ppmf ppmu-table-card ppmu-inspection-list-card" id="inspectionListWrap">
        @include('inspections.partials.list-table', ['inspectionRecords' => $inspectionRecords])
    </div>
</div>
@endsection

@push('scripts')
<script>
window.PPMU_INSPECTIONS = {
    ajaxUrl: @json(route('inspections.data')),
    indexUrl: @json(route('inspections.index')),
};
</script>
<script src="{{ asset('js/ppmu-inspections.js') }}?v={{ filemtime(public_path('js/ppmu-inspections.js')) }}"></script>
@endpush

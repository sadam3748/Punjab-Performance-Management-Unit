@extends('layouts.app')
@section('title', 'Home')
@section('content_class', 'ppmu-dashboard-content ppmu-main-dashboard')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ppmu-kpi.css') }}?v={{ filemtime(public_path('css/ppmu-kpi.css')) }}">
@endpush

@section('content')
@php
    $periodQuery = $periodQuery ?? app(\App\Services\KpiPeriodService::class)->queryString(request());
@endphp
<div class="ppmu-page-head ppmu-page-head-compact">
    <h1>Home</h1>
    <span class="ppmu-page-head-meta">
        {{ $user->role?->name ?? 'User' }} · {{ $location }} ·
        <span id="kpiMainCount">{{ $cards->count() }}</span> KPIs
    </span>
</div>

<x-period-filter
    :filters="$filters"
    :period="$period"
    :action="route('dashboard.data')"
    :ajax="true"
    :period-description="$period_description"/>

<div id="kpiMainRefreshable" class="ppmu-main-refreshable">
    @include('dashboard.partials.kpi-grid', ['cards' => $cards, 'periodQuery' => $periodQuery])
</div>
@endsection

@push('scripts')
<script>
window.PPMU_KPI_MAIN = {
    ajaxUrl: @json(route('dashboard.data')),
    defaults: @json($filters['defaults'] ?? []),
    period: @json($period),
};
</script>
<script src="{{ asset('js/ppmu-kpi-main.js') }}?v={{ filemtime(public_path('js/ppmu-kpi-main.js')) }}"></script>
@endpush

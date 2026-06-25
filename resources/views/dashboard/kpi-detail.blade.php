@extends('layouts.app')
@section('title', $kpiCard->title . ' — KPI Detail Dashboard')
@section('content_class', 'ppmu-dashboard-content ppmu-detail-page')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ppmu-kpi.css') }}?v={{ filemtime(public_path('css/ppmu-kpi.css')) }}">
@endpush

@section('content')
@php
    $imageUrl  = asset($kpiCard->resolvedImagePath());
    $pct       = max(0, min(100, (float) $header['achievement_percentage']));
    $score     = (float) ($header['score'] ?? 0);
    $marks     = (float) ($header['total_marks'] ?? $kpiCard->total_marks);
    $labels    = $header['labels'] ?? app(\App\Services\KpiDashboardConfigService::class)->headerLabelsFor($kpiCard->slug);
    $userScope = $header['scope_label'] ?? ($user->tehsil?->name ?? $user->district?->name ?? $user->division?->name ?? 'All Punjab');
    $statusDonut = $charts['status_donut'] ?? $charts['donut'];
    $comparisonTitle = $charts['comparison_label'] ?? 'Area comparison';
    $visibleCharts = $chartDefinitions ?? $kpiConfig['charts'] ?? [];
    $chartCount = count($visibleCharts);
    $areaChartColors = $charts['areas']->values()->map(fn ($v) =>
        $v >= 85 ? '#087443' : ($v >= 70 ? '#2563eb' : ($v >= 50 ? '#e07b00' : '#dc2626'))
    );
    $periodTypesForJs = $filters['period_types'] ?? ['daily', 'weekly', 'monthly', 'yearly'];
@endphp

<div class="ppmu-detail-hero card-ppmf" id="kpiDetailHero">
    <div class="ppmu-detail-hero-bar">
        <a href="{{ route('dashboard') }}" class="ppmu-back">
            <i class="bi bi-arrow-left-circle-fill"></i> Main Dashboard
        </a>
    </div>

    <div class="ppmu-detail-hero-main">
        <div class="ppmu-detail-visual">
            <img src="{{ $imageUrl }}" alt="{{ $kpiCard->title }}" width="96" height="96">
        </div>

        <div class="ppmu-detail-info">
            <span class="ppmu-detail-category">KPI Detail Dashboard · {{ $kpiCard->category }}</span>
            <h1>{{ $kpiCard->title }}</h1>
            <div class="ppmu-detail-meta">
                <span><i class="bi bi-person-badge-fill"></i>{{ $user->role?->name }}</span>
                <span><i class="bi bi-geo-alt-fill"></i>{{ $userScope }}</span>
                <span id="kpiDetailPeriodLabel"><i class="bi bi-calendar3"></i>{{ $header['period_label'] ?? 'All Periods' }}</span>
                <span><i class="bi bi-award-fill"></i>{{ $kpiCard->total_marks }} marks</span>
            </div>
        </div>

        <div class="ppmu-detail-stats" id="kpiDetailHeaderStats"
             data-label-target="{{ $labels['target'] }}"
             data-label-completed="{{ $labels['completed'] }}">
            <div class="ppmu-ds-item" data-stat="target" title="Required operational work for the selected period">
                <span data-label="target">{{ $labels['target'] }}</span>
                <strong>{{ number_format($header['operational_target'] ?? $header['target'], 1) }}</strong>
            </div>
            <div class="ppmu-ds-item ppmu-ds-accent" data-stat="achieved" title="Actual operational work completed">
                <span data-label="completed">{{ $labels['completed'] }}</span>
                <strong>{{ number_format($header['completed'] ?? $header['achieved'], 1) }}</strong>
            </div>
            <div class="ppmu-ds-item ppmu-ds-pct" data-stat="pct" title="Completed ÷ Operational Target × 100">
                <span>Progress</span>
                <strong>{{ $pct }}%</strong>
            </div>
            <div class="ppmu-ds-item ppmu-ds-status" data-stat="status" title="Performance status from progress">
                <span>Status</span>
                <x-status-badge :status="$header['status_label']"/>
            </div>
        </div>
    </div>
</div>

<x-period-filter
    :filters="$filters"
    :period="$period"
    :action="route('kpi.dashboard.data', $kpiCard)"
    :ajax="true"
    :hide-all="true"
    :period-description="$period_description ?? ''"/>

<x-kpi-geo-filter :geo-filters="$geoFilters" :kpi-card="$kpiCard" :geo="$geo ?? []"/>

@if(!empty($data_fallback))
    <div class="ppmu-data-fallback-note alert alert-light border py-2 px-3 mb-3" role="status">
        <i class="bi bi-info-circle text-success me-1"></i>
        No records matched the exact period filter. Showing the nearest available scoped data for this KPI.
    </div>
@endif

<div id="kpiDetailRefreshable" class="ppmu-detail-refreshable">
    <div class="ppmu-section-head">
        <div>
            <h2><i class="bi bi-speedometer2"></i> KPI Performance Cards</h2>
            <p>Key counts and rates for this KPI in the selected period.</p>
        </div>
    </div>
    <div id="kpiDetailMetrics">
        @include('dashboard.partials.kpi-detail-metrics', ['metrics' => $metrics, 'metricSections' => $metricSections ?? []])
    </div>

    <div class="ppmu-section-head mt-4">
        <div>
            <h2><i class="bi bi-bar-chart-fill"></i> KPI Charts</h2>
            <p>Charts defined in the KPI specification for this indicator.</p>
        </div>
    </div>
    <div class="ppmu-chart-grid ppmu-chart-grid-count-{{ $chartCount }}" id="kpiDetailCharts">
        @foreach($visibleCharts as $index => $chart)
            <x-chart-card
                :title="$chart['title']"
                :subtitle="ucfirst($chart['type']).' chart'"
                :canvas="'kpiChart_'.$index"/>
        @endforeach
    </div>

    <div id="kpiDetailRecords">
        @include('dashboard.partials.kpi-detail-records', [
            'kpiCard' => $kpiCard,
            'summary' => $summary,
            'tableSubmissions' => $tableSubmissions,
            'imageUrl' => $imageUrl,
            'periodDescription' => $period_description ?? '',
        ])
    </div>
</div>

@endsection

@push('scripts')
<script>
window.PPMU_KPI_DETAIL = {
    ajaxUrl: @json(route('kpi.dashboard.data', $kpiCard)),
    defaults: @json($filters['defaults'] ?? []),
    periodTypes: @json($periodTypesForJs),
    period: @json($period),
    chartDefinitions: @json($charts['definitions'] ?? []),
    charts: {
        definitions: @json($charts['definitions'] ?? []),
        status_donut: @json($statusDonut),
        target_achieved: @json($charts['target_achieved']),
        trend: @json($charts['trend']),
        areas: @json($charts['areas']),
        comparison_label: @json($comparisonTitle),
        area_colors: @json($areaChartColors),
    },
    statusLabels: {
        excellent: 'Excellent',
        good: 'Good',
        attention: 'Attention',
        critical: 'Critical',
    },
};
</script>
<script src="{{ asset('js/ppmu-kpi-detail.js') }}?v={{ filemtime(public_path('js/ppmu-kpi-detail.js')) }}"></script>
@endpush

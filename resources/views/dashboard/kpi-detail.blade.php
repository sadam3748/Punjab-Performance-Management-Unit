@extends('layouts.app')
@section('title', $kpiCard->title . ' — KPI Detail')
@section('content_class', 'ppmu-dashboard-content ppmu-detail-page')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ppmu-kpi.css') }}?v={{ filemtime(public_path('css/ppmu-kpi.css')) }}">
@endpush

@section('content')
@php
    $imageUrl  = asset($kpiCard->resolvedImagePath());
    $pct       = (float) $header['achievement_percentage'];
    $score     = (float) ($header['score'] ?? 0);
    $userScope = $header['scope_label'] ?? ($user->tehsil?->name ?? $user->district?->name ?? $user->division?->name ?? 'All Punjab');
    $statusDonut = $charts['status_donut'] ?? $charts['donut'];
    $comparisonTitle = $charts['comparison_label'] ?? 'Area comparison';
    $areaChartColors = $charts['areas']->values()->map(fn ($v) =>
        $v >= 85 ? '#087443' : ($v >= 70 ? '#2563eb' : ($v >= 50 ? '#e07b00' : '#dc2626'))
    );
@endphp

<div class="ppmu-detail-hero card-ppmf" id="kpiDetailHero">
    <div class="ppmu-detail-hero-bar">
        <a href="{{ route('dashboard') }}" class="ppmu-back">
            <i class="bi bi-arrow-left-circle-fill"></i> Main Dashboard
        </a>
        @if(in_array($user->role?->slug, ['ac', 'field_user', 'super_admin']))
            <a href="{{ route('kpi-submissions.create', $kpiCard) }}" class="btn btn-success btn-sm ppmu-submit-btn">
                <i class="bi bi-plus-circle-fill me-1"></i>Submit Data
            </a>
        @endif
    </div>

    <div class="ppmu-detail-hero-main">
        <div class="ppmu-detail-visual">
            <img src="{{ $imageUrl }}" alt="{{ $kpiCard->title }}" width="96" height="96">
        </div>

        <div class="ppmu-detail-info">
            <span class="ppmu-detail-category">{{ $kpiCard->category }}</span>
            <h1>{{ $kpiCard->title }}</h1>
            <div class="ppmu-detail-meta">
                <span><i class="bi bi-person-badge-fill"></i>{{ $user->role?->name }}</span>
                <span><i class="bi bi-geo-alt-fill"></i>{{ $userScope }}</span>
                <span id="kpiDetailPeriodLabel"><i class="bi bi-calendar3"></i>{{ $header['period_label'] ?? 'All Periods' }}</span>
                <span><i class="bi bi-award-fill"></i>{{ $kpiCard->total_marks }} marks</span>
            </div>
        </div>

        <div class="ppmu-detail-stats" id="kpiDetailHeaderStats">
            <div class="ppmu-ds-item" data-stat="target">
                <span>Target</span>
                <strong>{{ number_format($header['target'], 1) }}</strong>
            </div>
            <div class="ppmu-ds-item" data-stat="reported">
                <span>Reported</span>
                <strong>{{ number_format($header['reported']) }}</strong>
            </div>
            <div class="ppmu-ds-item ppmu-ds-accent" data-stat="achieved">
                <span>Achieved</span>
                <strong>{{ number_format($header['achieved'], 1) }}</strong>
            </div>
            <div class="ppmu-ds-item" data-stat="pending">
                <span>Pending</span>
                <strong>{{ number_format($header['pending'], 1) }}</strong>
            </div>
            <div class="ppmu-ds-item ppmu-ds-pct" data-stat="pct">
                <span>Achievement</span>
                <strong>{{ $pct }}%</strong>
            </div>
            <div class="ppmu-ds-item" data-stat="score">
                <span>Score</span>
                <strong>{{ number_format($score, 2) }}</strong>
            </div>
            <div class="ppmu-ds-item ppmu-ds-status" data-stat="status">
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
    :period-description="$period_description ?? ''"/>

<div id="kpiDetailRefreshable" class="ppmu-detail-refreshable">
    <div id="kpiDetailSummary">
        @include('dashboard.partials.kpi-detail-summary', ['summary' => $summary])
    </div>

    <div class="ppmu-section-head">
        <div>
            <h2><i class="bi bi-speedometer2"></i> Performance Indicators</h2>
            <p>Key KPI values and operational indicators for the selected period.</p>
        </div>
    </div>
    <div id="kpiDetailMetrics">
        @include('dashboard.partials.kpi-detail-metrics', ['metrics' => $metrics])
    </div>

    <div class="ppmu-section-head mt-4">
        <div>
            <h2><i class="bi bi-bar-chart-fill"></i> Performance Charts</h2>
            <p>Status, targets, trends and geographic comparison at a glance.</p>
        </div>
    </div>
    <div class="ppmu-chart-grid" id="kpiDetailCharts">
        <x-chart-card title="Status Distribution" subtitle="Approved, submitted, pending & rejected" canvas="statusChart"/>
        <x-chart-card title="Target vs Achieved" subtitle="Period performance against target" canvas="targetChart"/>
        <x-chart-card title="Performance Trend" subtitle="Achievement % over reporting dates" canvas="trendChart"/>
        <x-chart-card :title="$comparisonTitle" subtitle="Achievement % by geographic scope" canvas="areaChart"/>
    </div>

    <div id="kpiDetailRecords">
        @include('dashboard.partials.kpi-detail-records', [
            'kpiCard' => $kpiCard,
            'summary' => $summary,
            'tableSubmissions' => $tableSubmissions,
            'imageUrl' => $imageUrl,
            'periodDescription' => $period_description ?? 'All periods · Complete available data',
        ])
    </div>
</div>

@endsection

@push('scripts')
<script>
window.PPMU_KPI_DETAIL = {
    ajaxUrl: @json(route('kpi.dashboard.data', $kpiCard)),
    defaults: @json($filters['defaults'] ?? []),
    period: @json($period),
    charts: {
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

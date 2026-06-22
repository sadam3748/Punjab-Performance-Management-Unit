@extends('layouts.app')
@section('title', $kpiCard->title.' KPI Dashboard')
@section('content_class', 'ppmu-dashboard-content')
@push('styles')<link rel="stylesheet" href="{{ asset('css/ppmu-kpi.css') }}?v={{ filemtime(public_path('css/ppmu-kpi.css')) }}">@endpush

@section('content')
@php
    $iconPath = str_contains($kpiCard->icon ?? '', '.') ? asset('assets/images/kpi-icons/'.$kpiCard->icon) : null;
@endphp

<div class="ppmu-detail-header">
    <div class="ppmu-detail-header-main">
        <a href="{{ route('dashboard') }}" class="ppmu-back"><i class="bi bi-arrow-left"></i> Main KPI Dashboard</a>
        <div class="ppmu-detail-title-row">
            <span class="ppmu-kpi-icon ppmu-detail-icon">
                @if($iconPath)
                    <img src="{{ $iconPath }}" alt="{{ $kpiCard->title }}" width="48" height="48">
                @else
                    <i class="bi {{ $kpiCard->icon }}"></i>
                @endif
            </span>
            <div>
                <div class="ppmu-eyebrow">{{ $kpiCard->category }}</div>
                <h1>{{ $kpiCard->title }}</h1>
                <p>{{ $user->role?->name }} · {{ $location }} · {{ $header['period_label'] ?? 'All periods' }}</p>
            </div>
        </div>
    </div>
    <div class="ppmu-detail-header-stats">
        <div class="ppmu-detail-stat"><span>Target</span><strong>{{ number_format($header['target'], 1) }}</strong></div>
        <div class="ppmu-detail-stat"><span>Achieved</span><strong>{{ number_format($header['achieved'], 1) }}</strong></div>
        <div class="ppmu-detail-stat highlight"><span>Achievement</span><strong>{{ $header['achievement_percentage'] }}%</strong></div>
        <x-status-badge :status="$header['status_label']" />
        @if(in_array($user->role?->slug, ['ac','field_user','super_admin']))
            <a href="{{ route('kpi-submissions.create', $kpiCard) }}" class="btn btn-success btn-sm"><i class="bi bi-plus-circle me-1"></i>Submit Data</a>
        @endif
    </div>
</div>

<x-period-filter :filters="$filters" :period="$period" :action="route('kpi.dashboard', $kpiCard)" />

<div class="ppmu-section-title"><div><h2>KPI Metrics</h2><p>KPI-specific performance indicators for the selected period.</p></div></div>
<div class="ppmu-metric-grid">
    @foreach($metrics as $metric)
        <x-kpi-metric-card :label="$metric['label']" :value="$metric['value']" :icon="$metric['icon'] ?? 'bi-bar-chart'" />
    @endforeach
</div>

<div class="ppmu-chart-grid">
    <x-chart-card title="Status Distribution" subtitle="Achievement split by status" canvas="statusChart" />
    <x-chart-card title="Target vs Achieved" subtitle="Performance against target" canvas="targetChart" />
    <x-chart-card title="Performance Trend" subtitle="Score movement by period" canvas="trendChart" />
    <x-chart-card title="Area Comparison" subtitle="Average performance by area" canvas="areaChart" />
</div>

<div class="card-ppmf mt-4">
    <div class="card-ppmf-header">
        <div>
            <div class="card-ppmf-title">Detailed KPI Records</div>
            <small class="text-muted">{{ $submissions->count() }} records</small>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table-ppmf ppmu-table">
            <thead>
                <tr>
                    <th>Target</th>
                    <th>Achieved</th>
                    <th>Percentage</th>
                    <th>Status</th>
                    <th>Submitted By</th>
                    <th>Period</th>
                    <th>Updated</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($submissions as $item)
                    @php $pct = $summary['target'] > 0 ? round(min(100, ($item->score / $summary['target']) * 100), 1) : 0; @endphp
                    <tr>
                        <td><strong>{{ number_format($summary['target'], 1) }}</strong></td>
                        <td><strong>{{ number_format((float) $item->score, 1) }}</strong></td>
                        <td><strong>{{ $pct }}%</strong></td>
                        <td><x-status-badge :status="$item->status"/></td>
                        <td>{{ $item->user?->name }}</td>
                        <td><strong>{{ $item->period_label }}</strong><small>{{ ucfirst($item->period_type) }}</small></td>
                        <td>{{ $item->updated_at->diffForHumans() }}</td>
                        <td><button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#record{{ $item->id }}">View</button></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-5 text-muted">No records match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@foreach($submissions as $item)
<div class="modal fade" id="record{{ $item->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $kpiCard->title }} - {{ $item->period_label }}</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    @foreach($item->values as $value)
                        <div class="col-md-6">
                            <small class="text-muted d-block">{{ $value->field?->field_label }}</small>
                            <strong>{{ $value->value ?: '-' }}</strong>
                        </div>
                    @endforeach
                </div>
                @if($item->remarks)<hr><p>{{ $item->remarks }}</p>@endif
            </div>
        </div>
    </div>
</div>
@endforeach
@endsection

@push('scripts')
<script>
const palette = ['#087443','#2563eb','#f59e0b','#dc2626','#7c3aed','#0891b2'];
const grid = { color: 'rgba(148,163,184,.18)' };

new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: { labels: @json($charts['status']->keys()), datasets: [{ data: @json($charts['status']->values()), backgroundColor: palette, borderWidth: 0 }] },
    options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { position: 'bottom' } } }
});

new Chart(document.getElementById('targetChart'), {
    type: 'bar',
    data: {
        labels: @json($charts['target_achieved']->keys()),
        datasets: [{ label: 'Value', data: @json($charts['target_achieved']->values()), backgroundColor: ['#94a3b8','#087443'], borderRadius: 6 }]
    },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, grid }, x: { grid: { display: false } } }, plugins: { legend: { display: false } } }
});

new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: @json($charts['trend']->keys()),
        datasets: [{ label: 'Performance %', data: @json($charts['trend']->values()), borderColor: '#087443', backgroundColor: 'rgba(8,116,67,.1)', fill: true, tension: .35 }]
    },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 100, grid }, x: { grid: { display: false } } }, plugins: { legend: { display: false } } }
});

new Chart(document.getElementById('areaChart'), {
    type: 'bar',
    data: {
        labels: @json($charts['areas']->keys()),
        datasets: [{ label: 'Performance %', data: @json($charts['areas']->values()), backgroundColor: '#2563eb', borderRadius: 6 }]
    },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 100, grid }, x: { grid: { display: false } } }, plugins: { legend: { display: false } } }
});
</script>
@endpush

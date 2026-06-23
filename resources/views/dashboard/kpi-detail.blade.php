@extends('layouts.app')
@section('title', $kpiCard->title . ' — KPI Detail')
@section('content_class', 'ppmu-dashboard-content')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ppmu-kpi.css') }}?v={{ filemtime(public_path('css/ppmu-kpi.css')) }}">
@endpush

@section('content')
@php
    $imageUrl   = asset($kpiCard->resolvedImagePath());
    $pct        = (float) $header['achievement_percentage'];
    $ringOffset = round(314 - (314 * min(100, $pct) / 100), 2);
    $ringColor  = $pct >= 85 ? '#087443' : ($pct >= 70 ? '#2563eb' : ($pct >= 50 ? '#e07b00' : '#dc2626'));
    $userScope  = $user->tehsil?->name ?? $user->district?->name ?? $user->division?->name ?? 'All Punjab';
@endphp

{{-- ───────────────────────────────────────────────────────────────────────
     HERO HEADER
──────────────────────────────────────────────────────────────────────── --}}
<div class="ppmu-kpi-hero">

    <div class="ppmu-hero-left">
        <a href="{{ route('dashboard') }}" class="ppmu-back">
            <i class="bi bi-arrow-left-circle-fill"></i> Main Dashboard
        </a>
        <div class="ppmu-hero-identity">
            <div class="ppmu-hero-icon">
                <img src="{{ $imageUrl }}" alt="{{ $kpiCard->title }}" width="56" height="56">
            </div>
            <div class="ppmu-hero-meta">
                <div class="ppmu-eyebrow">{{ $kpiCard->category }}</div>
                <h1>{{ $kpiCard->title }}</h1>
                <div class="ppmu-hero-scope">
                    <span><i class="bi bi-person-badge-fill"></i> {{ $user->role?->name }}</span>
                    <span class="ppmu-scope-sep">·</span>
                    <span><i class="bi bi-geo-alt-fill"></i> {{ $userScope }}</span>
                    <span class="ppmu-scope-sep">·</span>
                    <span><i class="bi bi-calendar3"></i> {{ $header['period_label'] ?? 'All Periods' }}</span>
                    <span class="ppmu-scope-sep">·</span>
                    <span><i class="bi bi-award-fill"></i> {{ $kpiCard->total_marks }} marks</span>
                </div>
            </div>
        </div>
    </div>

    <div class="ppmu-hero-right">
        <div class="ppmu-achievement-ring">
            <svg viewBox="0 0 120 120" class="ppmu-ring-svg" aria-hidden="true">
                <circle cx="60" cy="60" r="50" class="ppmu-ring-track"/>
                <circle cx="60" cy="60" r="50" class="ppmu-ring-fill"
                    style="stroke-dashoffset:{{ $ringOffset }};stroke:{{ $ringColor }}"/>
            </svg>
            <div class="ppmu-ring-label">
                <strong>{{ $pct }}%</strong>
                <span>Achievement</span>
            </div>
        </div>

        <div class="ppmu-hero-stats">
            <div class="ppmu-hero-stat">
                <span>Target</span>
                <strong>{{ number_format($header['target'], 1) }}</strong>
            </div>
            <div class="ppmu-hero-stat">
                <span>Reported</span>
                <strong>{{ number_format($header['reported']) }}</strong>
            </div>
            <div class="ppmu-hero-stat ppmu-hs-highlight">
                <span>Achieved</span>
                <strong>{{ number_format($header['achieved'], 1) }}</strong>
            </div>
            <div class="ppmu-hero-stat ppmu-hs-status">
                <x-status-badge :status="$header['status_label']" />
            </div>
            @if(in_array($user->role?->slug, ['ac','field_user','super_admin']))
                <a href="{{ route('kpi-submissions.create', $kpiCard) }}"
                   class="btn btn-success btn-sm ppmu-submit-btn">
                    <i class="bi bi-plus-circle-fill me-1"></i>Submit Data
                </a>
            @endif
        </div>
    </div>

</div>

{{-- ───────────────────────────────────────────────────────────────────────
     PERIOD FILTER
──────────────────────────────────────────────────────────────────────── --}}
<x-period-filter :filters="$filters" :period="$period" :action="route('kpi.dashboard', $kpiCard)"/>

{{-- ───────────────────────────────────────────────────────────────────────
     SUMMARY STRIP
──────────────────────────────────────────────────────────────────────── --}}
<div class="ppmu-summary-strip">
    <div class="ppmu-strip-card ppmu-sc-blue">
        <div class="ppmu-sc-icon"><i class="bi bi-collection-fill"></i></div>
        <div class="ppmu-sc-body"><strong>{{ number_format($summary['total']) }}</strong><span>Total Records</span></div>
    </div>
    <div class="ppmu-strip-card ppmu-sc-green">
        <div class="ppmu-sc-icon"><i class="bi bi-check-circle-fill"></i></div>
        <div class="ppmu-sc-body"><strong>{{ number_format($summary['approved']) }}</strong><span>Approved</span></div>
    </div>
    <div class="ppmu-strip-card ppmu-sc-teal">
        <div class="ppmu-sc-icon"><i class="bi bi-send-fill"></i></div>
        <div class="ppmu-sc-body"><strong>{{ number_format($summary['submitted']) }}</strong><span>Submitted</span></div>
    </div>
    <div class="ppmu-strip-card ppmu-sc-yellow">
        <div class="ppmu-sc-icon"><i class="bi bi-hourglass-split"></i></div>
        <div class="ppmu-sc-body"><strong>{{ number_format($summary['pending']) }}</strong><span>Pending</span></div>
    </div>
    <div class="ppmu-strip-card ppmu-sc-red">
        <div class="ppmu-sc-icon"><i class="bi bi-x-circle-fill"></i></div>
        <div class="ppmu-sc-body"><strong>{{ number_format($summary['rejected']) }}</strong><span>Rejected</span></div>
    </div>
    <div class="ppmu-strip-card ppmu-sc-gold">
        <div class="ppmu-sc-icon"><i class="bi bi-trophy-fill"></i></div>
        <div class="ppmu-sc-body"><strong class="ppmu-area-label">{{ $summary['best_area'] }}</strong><span>Top Area</span></div>
    </div>
</div>

{{-- ───────────────────────────────────────────────────────────────────────
     KPI METRIC CARDS
──────────────────────────────────────────────────────────────────────── --}}
<div class="ppmu-section-head">
    <div>
        <h2><i class="bi bi-speedometer2"></i> KPI Metrics</h2>
        <p>Aggregated performance indicators for the selected period.</p>
    </div>
</div>
<div class="ppmu-metric-grid">
    @foreach($metrics as $metric)
        <x-kpi-metric-card
            :label="$metric['label']"
            :value="$metric['value']"
            :icon="$metric['icon'] ?? 'bi-bar-chart'"
            :tone="$metric['tone'] ?? 'blue'"/>
    @endforeach
</div>

{{-- ───────────────────────────────────────────────────────────────────────
     PERFORMANCE CHARTS
──────────────────────────────────────────────────────────────────────── --}}
<div class="ppmu-section-head mt-4">
    <div>
        <h2><i class="bi bi-bar-chart-fill"></i> Performance Charts</h2>
        <p>Visual breakdown of KPI performance, trends and area comparison.</p>
    </div>
</div>
<div class="ppmu-chart-grid">
    <x-chart-card title="Status Distribution"  subtitle="Submission status breakdown"       canvas="statusChart"/>
    <x-chart-card title="Target vs Achieved"   subtitle="Period performance vs target"       canvas="targetChart"/>
    <x-chart-card title="Performance Trend"    subtitle="Achievement % over time"             canvas="trendChart"/>
    <x-chart-card title="Area Comparison"      subtitle="Achievement % by geographic scope"  canvas="areaChart"/>
</div>

{{-- ───────────────────────────────────────────────────────────────────────
     DETAILED RECORDS TABLE
──────────────────────────────────────────────────────────────────────── --}}
<div class="ppmu-section-head mt-4">
    <div>
        <h2><i class="bi bi-table"></i> Detailed Records</h2>
        <p>{{ $tableSubmissions->total() }} record{{ $tableSubmissions->total() === 1 ? '' : 's' }} in selected period.</p>
    </div>
    <div class="ppmu-table-toolbar">
        <div class="ppmu-search-box">
            <i class="bi bi-search"></i>
            <input type="text" id="kpiTableSearch" placeholder="Search period, status, area…" autocomplete="off">
        </div>
    </div>
</div>

<div class="card-ppmf ppmu-table-card">
    <div class="table-responsive">
        <table class="table-ppmf ppmu-table" id="kpiDetailTable">
            <thead>
                <tr>
                    <th class="ppmu-th-num">#</th>
                    <th>Date</th>
                    <th>PPMF Week</th>
                    <th>Area</th>
                    <th>Target</th>
                    <th>Reported</th>
                    <th>Achieved</th>
                    <th>Pending</th>
                    <th>Achievement %</th>
                    <th>Status</th>
                    <th>Submitted By</th>
                    <th>Updated</th>
                    <th class="ppmu-th-action"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($tableSubmissions as $rowIndex => $item)
                    @php
                        $itemTarget = (float) ($item->target_value ?? $summary['target']);
                        $itemReported = (float) ($item->reported_value ?? 0);
                        $itemAchieved = (float) ($item->achieved_value ?? $item->score);
                        $itemPending = (float) ($item->pending_value ?? max(0, $itemTarget - $itemAchieved));
                        $itemPct = (float) ($item->achievement_percentage ?? ($itemTarget > 0 ? round(min(100, ($itemAchieved / $itemTarget) * 100), 1) : 0));
                        $pctClass = $itemPct >= 85 ? 'ppmu-pct-excellent'
                                  : ($itemPct >= 70 ? 'ppmu-pct-good'
                                  : ($itemPct >= 50 ? 'ppmu-pct-warn'
                                  : 'ppmu-pct-critical'));
                        $itemArea = $item->tehsil?->name
                            ?? $item->district?->name
                            ?? $item->division?->name
                            ?? 'Punjab';
                        $weekLabel = $item->week_start_date && $item->week_end_date
                            ? $item->week_start_date->format('d M').' - '.$item->week_end_date->format('d M Y')
                            : ($item->week_no ?: '—');
                        $searchStr = strtolower($item->period_label.' '.$item->status.' '.($item->user?->name ?? '').' '.$itemArea);
                    @endphp
                    <tr data-search="{{ $searchStr }}">
                        <td class="ppmu-td-num text-muted">{{ $tableSubmissions->firstItem() + $rowIndex }}</td>
                        <td>{{ $item->submission_date->format('d M Y') }}</td>
                        <td><small>{{ $weekLabel }}</small></td>
                        <td><small class="text-muted">{{ $itemArea }}</small></td>
                        <td class="fw-semibold">{{ number_format($itemTarget, 1) }}</td>
                        <td>{{ number_format($itemReported, 1) }}</td>
                        <td class="fw-bold">{{ number_format($itemAchieved, 1) }}</td>
                        <td>{{ number_format($itemPending, 1) }}</td>
                        <td><span class="ppmu-pct-badge {{ $pctClass }}">{{ $itemPct }}%</span></td>
                        <td><x-status-badge :status="$item->status"/></td>
                        <td class="text-truncate" style="max-width:130px">{{ $item->user?->name ?? '—' }}</td>
                        <td><small class="text-muted">{{ $item->updated_at->diffForHumans() }}</small></td>
                        <td>
                            <button class="ppmu-view-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#recModal{{ $item->id }}"
                                    title="View details">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr class="ppmu-empty-row">
                        <td colspan="13">
                            <div class="ppmu-empty-state">
                                <i class="bi bi-inbox"></i>
                                <h5>No records found</h5>
                                <p>Adjust filters or submit data to populate this table.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($tableSubmissions->hasPages())
        <div class="ppmu-pagination-wrap">
            {{ $tableSubmissions->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

{{-- ───────────────────────────────────────────────────────────────────────
     RECORD DETAIL MODALS  (current page only)
──────────────────────────────────────────────────────────────────────── --}}
@foreach($tableSubmissions as $item)
<div class="modal fade" id="recModal{{ $item->id }}" tabindex="-1"
     aria-labelledby="recLbl{{ $item->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content ppmu-modal-content">

            <div class="modal-header ppmu-modal-header">
                <div class="d-flex align-items-center gap-2">
                    <img src="{{ $imageUrl }}" alt="" width="30" height="30" class="rounded">
                    <div>
                        <h5 class="modal-title mb-0" id="recLbl{{ $item->id }}">{{ $kpiCard->title }}</h5>
                        <small class="text-muted">{{ $item->period_label }} &nbsp;·&nbsp; {{ ucfirst($item->period_type) }}</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                @php
                    $mTarget = (float) ($item->target_value ?? $summary['target']);
                    $mReported = (float) ($item->reported_value ?? 0);
                    $mAchieved = (float) ($item->achieved_value ?? $item->score);
                    $mPending = (float) ($item->pending_value ?? max(0, $mTarget - $mAchieved));
                    $mPct = (float) ($item->achievement_percentage ?? ($mTarget > 0 ? round(min(100, ($mAchieved / $mTarget) * 100), 1) : 0));
                    $mWeek = $item->week_start_date && $item->week_end_date
                        ? $item->week_start_date->format('d M Y').' - '.$item->week_end_date->format('d M Y')
                        : '—';
                    $snapshot = is_array($item->metric_snapshot) ? $item->metric_snapshot : json_decode($item->metric_snapshot ?? '[]', true);
                @endphp

                <div class="ppmu-modal-stats">
                    <div class="ppmu-ms-item"><span>Status</span><x-status-badge :status="$item->status"/></div>
                    <div class="ppmu-ms-item"><span>Target</span><strong>{{ number_format($mTarget, 1) }}</strong></div>
                    <div class="ppmu-ms-item"><span>Reported</span><strong>{{ number_format($mReported, 1) }}</strong></div>
                    <div class="ppmu-ms-item"><span>Achieved</span><strong>{{ number_format($mAchieved, 1) }}</strong></div>
                    <div class="ppmu-ms-item"><span>Pending</span><strong>{{ number_format($mPending, 1) }}</strong></div>
                    <div class="ppmu-ms-item"><span>Achievement</span><strong>{{ $mPct }}%</strong></div>
                    <div class="ppmu-ms-item"><span>Submitted By</span><strong>{{ $item->user?->name ?? '—' }}</strong></div>
                    <div class="ppmu-ms-item"><span>Area</span><strong>{{ $item->tehsil?->name ?? $item->district?->name ?? $item->division?->name ?? 'Punjab' }}</strong></div>
                    <div class="ppmu-ms-item"><span>Date</span><strong>{{ $item->submission_date->format('d M Y') }}</strong></div>
                    <div class="ppmu-ms-item"><span>PPMF Week</span><strong>{{ $mWeek }}</strong></div>
                </div>

                @if(!empty($snapshot))
                    <hr class="my-3">
                    <h6 class="mb-2">Metric Breakdown</h6>
                    <div class="ppmu-modal-fields">
                        @foreach($snapshot as $key => $val)
                            <div class="ppmu-mf-item">
                                <span>{{ str_replace('_', ' ', ucfirst($key)) }}</span>
                                <strong>{{ $val }}</strong>
                            </div>
                        @endforeach
                    </div>
                @elseif($item->values->isNotEmpty())
                    <hr class="my-3">
                    <div class="ppmu-modal-fields">
                        @foreach($item->values as $val)
                            <div class="ppmu-mf-item">
                                <span>{{ $val->field?->field_label ?? $val->field?->field_name ?? 'Field' }}</span>
                                <strong>{{ $val->value ?: '—' }}</strong>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($item->remarks)
                    <hr class="my-3">
                    <div class="ppmu-modal-remarks">
                        <span><i class="bi bi-chat-quote me-1"></i>Remarks</span>
                        <p>{{ $item->remarks }}</p>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>
@endforeach

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const G = '#087443', B = '#2563eb', W = '#e07b00', R = '#dc2626';
    const T = '#0891b2', P = '#7c3aed';
    const palette = [G, B, W, R, T, P, '#059669', '#d97706', '#0284c7', '#9333ea'];
    const grid  = { color: 'rgba(100,116,139,.14)' };
    const fnt   = { family: "'Plus Jakarta Sans', system-ui, sans-serif", size: 11 };
    Chart.defaults.font = fnt;

    // Donut — achieved vs pending
    const donutData = @json($charts['donut'] ?? $charts['target_achieved']);
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(donutData),
            datasets: [{ data: Object.values(donutData), backgroundColor: [G, '#e2e8f0'], borderWidth: 3, borderColor: '#fff', hoverOffset: 6 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '68%',
            plugins: { legend: { position: 'bottom', labels: { padding: 14, font: fnt } } }
        }
    });

    // Target vs Achieved — horizontal bar
    new Chart(document.getElementById('targetChart'), {
        type: 'bar',
        data: {
            labels: @json($charts['target_achieved']->keys()),
            datasets: [{ label: 'Value', data: @json($charts['target_achieved']->values()), backgroundColor: ['#e2e8f0', G], borderRadius: 8, borderSkipped: false, barThickness: 34 }]
        },
        options: {
            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
            scales: { x: { beginAtZero: true, grid, ticks: { font: fnt } }, y: { grid: { display: false }, ticks: { font: fnt } } },
            plugins: { legend: { display: false } }
        }
    });

    // Trend — line + fill
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: @json($charts['trend']->keys()),
            datasets: [{ label: 'Achievement %', data: @json($charts['trend']->values()), borderColor: G, backgroundColor: 'rgba(8,116,67,.07)', fill: true, tension: .42, pointBackgroundColor: G, pointBorderColor: '#fff', pointBorderWidth: 2, pointRadius: 5, pointHoverRadius: 7 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, max: 100, grid, ticks: { font: fnt, callback: v => v+'%' } }, x: { grid: { display: false }, ticks: { font: fnt } } },
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ' '+ctx.parsed.y+'%' } } }
        }
    });

    // Area Comparison — horizontal bar, colour-coded by score
    const areaColors = @json($charts['areas']->values()->map(fn($v) =>
        $v >= 85 ? '#087443' : ($v >= 70 ? '#2563eb' : ($v >= 50 ? '#e07b00' : '#dc2626'))
    ));
    new Chart(document.getElementById('areaChart'), {
        type: 'bar',
        data: {
            labels: @json($charts['areas']->keys()),
            datasets: [{ label: 'Achievement %', data: @json($charts['areas']->values()), backgroundColor: areaColors, borderRadius: 6, borderSkipped: false }]
        },
        options: {
            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
            scales: { x: { beginAtZero: true, max: 100, grid, ticks: { font: fnt, callback: v => v+'%' } }, y: { grid: { display: false }, ticks: { font: fnt } } },
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ' '+ctx.parsed.x+'%' } } }
        }
    });

    // Client-side table search
    const searchInput = document.getElementById('kpiTableSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            document.querySelectorAll('#kpiDetailTable tbody tr[data-search]').forEach(row => {
                row.style.display = (!q || row.dataset.search.includes(q)) ? '' : 'none';
            });
        });
    }

})();
</script>
@endpush

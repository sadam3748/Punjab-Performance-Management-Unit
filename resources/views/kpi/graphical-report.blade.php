@extends('layouts.app')

@section('title', 'District Wise KPI Graphical Report')

@push('styles')
<style>
    :root{
        --gr-green:#0d6b42;
        --gr-green-dark:#0b3b2e;
        --gr-green-bright:#18b979;
        --gr-green-soft:#e8f7ef;
        --gr-bg:#f3f7f6;
        --gr-card:#ffffff;
        --gr-border:#d9e8e3;
        --gr-text:#10231d;
        --gr-muted:#658579;
        --gr-blue:#2f80ed;
        --gr-teal:#0d9488;
        --gr-amber:#f59e0b;
        --gr-red:#dc5a5a;
        --gr-purple:#7c3aed;
    }

    .gr-page{
        background: transparent;
    }

    .gr-hero{
        background: linear-gradient(135deg, #ffffff 0%, #f3fbf7 100%);
        border:1px solid var(--gr-border);
        border-radius:18px;
        padding:18px 20px;
        box-shadow:0 12px 28px rgba(15, 35, 29, .06);
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:16px;
        flex-wrap:wrap;
        margin-bottom:16px;
    }

    .gr-eyebrow{
        display:inline-flex;
        align-items:center;
        gap:7px;
        background:#e0f7ef;
        color:var(--gr-green-dark);
        border:1px solid #bfe9d6;
        border-radius:999px;
        padding:5px 10px;
        font-size:11px;
        font-weight:900;
        letter-spacing:.06em;
        text-transform:uppercase;
    }

    .gr-hero h1{
        margin:8px 0 4px;
        font-size:28px;
        font-weight:950;
        letter-spacing:-.035em;
        color:var(--gr-text);
        line-height:1.1;
    }

    .gr-hero p{
        margin:0;
        color:var(--gr-muted);
        font-size:13px;
        font-weight:650;
        max-width:780px;
        line-height:1.45;
    }

    .gr-actions{
        display:flex;
        gap:8px;
        flex-wrap:wrap;
        justify-content:flex-end;
    }

    .gr-actions .btn-gov{
        height:40px;
        display:inline-flex;
        align-items:center;
        gap:7px;
        border-radius:11px;
        white-space:nowrap;
        font-size:13px;
    }

    .gr-filter-card,
    .gr-panel{
        background:var(--gr-card);
        border:1px solid var(--gr-border);
        border-radius:16px;
        box-shadow:0 10px 24px rgba(15, 35, 29, .045);
        overflow:hidden;
    }

    .gr-filter-card{
        margin-bottom:16px;
    }

    .gr-panel-head{
        padding:13px 16px;
        border-bottom:1px solid #edf5f2;
        background:linear-gradient(180deg, #fbfefd, #ffffff);
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:12px;
        flex-wrap:wrap;
    }

    .gr-panel-title{
        margin:0;
        font-size:14px;
        font-weight:950;
        color:var(--gr-green-dark);
        display:flex;
        align-items:center;
        gap:8px;
        letter-spacing:-.01em;
    }

    .gr-panel-title i{
        color:var(--gr-green-bright);
        font-size:16px;
    }

    .gr-panel-subtitle{
        margin:3px 0 0;
        color:var(--gr-muted);
        font-size:12px;
        font-weight:650;
    }

    .gr-panel-body{
        padding:16px;
    }

    .gr-filter-card .form-label{
        margin-bottom:6px;
        color:#334155;
        font-size:12px;
        font-weight:850;
    }

    .gr-filter-card .form-control,
    .gr-filter-card .form-select{
        min-height:40px;
        border-radius:11px;
        border-color:#cbd8d4;
        color:#16362b;
        font-size:13px;
        font-weight:650;
        box-shadow:none;
    }

    .gr-section-label{
        display:flex;
        align-items:center;
        gap:7px;
        margin:2px 0 10px;
        color:var(--gr-green-dark);
        font-size:11px;
        font-weight:950;
        text-transform:uppercase;
        letter-spacing:.08em;
    }

    .gr-section-label i{
        color:var(--gr-green-bright);
        font-size:15px;
    }

    .gr-section-label span{
        color:var(--gr-muted);
        text-transform:none;
        letter-spacing:0;
        font-weight:650;
    }

    .gr-metric-grid{
        display:grid;
        gap:10px;
        margin-bottom:14px;
        align-items:stretch;
    }

    /* Standard balanced card rows: never more than 4 cards per row.
       5 = 3+2, 6 = 3+3, 7 = 4+3, 8 = 4+4, 9 = 3+3+3. */
    .gr-metric-grid.metric-count-1{grid-template-columns:repeat(1, minmax(0, 240px));}
    .gr-metric-grid.metric-count-2{grid-template-columns:repeat(2, minmax(0, 1fr));}
    .gr-metric-grid.metric-count-3{grid-template-columns:repeat(3, minmax(0, 1fr));}
    .gr-metric-grid.metric-count-4{grid-template-columns:repeat(4, minmax(0, 1fr));}
    .gr-metric-grid.metric-count-5,
    .gr-metric-grid.metric-count-6,
    .gr-metric-grid.metric-count-9{grid-template-columns:repeat(3, minmax(0, 1fr));}
    .gr-metric-grid.metric-count-7,
    .gr-metric-grid.metric-count-8{grid-template-columns:repeat(4, minmax(0, 1fr));}
    .gr-metric-grid.metric-count-many{grid-template-columns:repeat(4, minmax(0, 1fr));}

    .gr-metric-card{
        position:relative;
        background:#ffffff;
        border:1px solid var(--gr-border);
        border-radius:12px;
        padding:9px 10px 8px;
        min-height:96px;
        overflow:hidden;
        box-shadow:0 6px 14px rgba(15, 35, 29, .035);
    }

    .gr-metric-card::before{
        content:"";
        position:absolute;
        inset:0 0 auto 0;
        height:3px;
        background:var(--accent, var(--gr-green-bright));
    }

    .gr-metric-icon{
        width:26px;
        height:26px;
        border-radius:8px;
        display:grid;
        place-items:center;
        background:var(--accent-soft, #e0f7ef);
        color:var(--accent, var(--gr-green-bright));
        font-size:14px;
        margin-bottom:7px;
    }

    .gr-metric-value{
        margin:0;
        font-size:20px;
        font-weight:950;
        color:var(--gr-text);
        letter-spacing:-.04em;
        line-height:1;
        word-break:break-word;
    }

    .gr-metric-title{
        margin:5px 0 0;
        color:#31584a;
        font-size:10.5px;
        font-weight:850;
        line-height:1.2;
        min-height:22px;
    }

    .gr-metric-meta{
        margin-top:6px;
        display:flex;
        flex-wrap:wrap;
        gap:5px;
    }

    .gr-mini-pill{
        display:inline-flex;
        align-items:center;
        gap:5px;
        border-radius:999px;
        padding:3px 8px;
        font-size:10px;
        font-weight:900;
        color:#1a5c47;
        background:#e0f7ef;
        border:1px solid #c3ead8;
        max-width:100%;
    }

    .gr-donut-row{
        display:grid;
        grid-template-columns:repeat(3, minmax(0, 1fr));
        gap:10px;
        margin-bottom:12px;
    }

    .gr-donut-row.donut-count-1{grid-template-columns:minmax(0, 360px);}
    .gr-donut-row.donut-count-2{grid-template-columns:repeat(2, minmax(0, 1fr));}
    .gr-donut-row.donut-count-3{grid-template-columns:repeat(3, minmax(0, 1fr));}
    .gr-donut-row.donut-count-4{grid-template-columns:repeat(4, minmax(0, 1fr));}
    .gr-donut-row.donut-count-many{grid-template-columns:repeat(4, minmax(0, 1fr));}

    .gr-graph-row{
        display:grid;
        grid-template-columns:repeat(2, minmax(0, 1fr));
        gap:12px;
        margin-bottom:14px;
    }

    .gr-chart-card{
        background:#ffffff;
        border:1px solid var(--gr-border);
        border-radius:12px;
        box-shadow:0 6px 14px rgba(15, 35, 29, .035);
        overflow:hidden;
        min-height:205px;
        display:flex;
        flex-direction:column;
    }

    .gr-chart-card.is-graph{
        min-height:260px;
    }

    .gr-chart-head{
        padding:10px 12px 6px;
    }

    .gr-chart-title{
        margin:0;
        color:var(--gr-text);
        font-size:13px;
        font-weight:950;
        display:flex;
        align-items:center;
        gap:7px;
    }

    .gr-chart-title i{
        color:var(--gr-green-bright);
    }

    .gr-chart-sub{
        margin:3px 0 0;
        color:var(--gr-muted);
        font-size:11px;
        font-weight:650;
    }

    .gr-chart-body{
        position:relative;
        flex:1;
        padding:4px 10px 10px;
        min-height:138px;
    }

    .gr-chart-card.is-graph .gr-chart-body{
        min-height:198px;
    }

    .gr-empty{
        min-height:120px;
        display:grid;
        place-items:center;
        text-align:center;
        color:var(--gr-muted);
        font-size:13px;
        font-weight:750;
        padding:18px;
        background:#fbfefd;
        border:1px dashed #cfe2dc;
        border-radius:14px;
    }

    .gr-table-card{
        background:#ffffff;
        border:1px solid var(--gr-border);
        border-radius:14px;
        box-shadow:0 8px 18px rgba(15, 35, 29, .04);
        overflow:hidden;
    }

    .gr-table-wrap{
        width:100%;
        overflow-x:visible;
    }

    .gr-table-top{
        padding:13px 16px;
        border-bottom:1px solid #edf5f2;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        flex-wrap:wrap;
        background:linear-gradient(180deg, #fbfefd, #ffffff);
    }

    .gr-table-title{
        display:flex;
        align-items:center;
        gap:8px;
        color:var(--gr-green-dark);
        font-size:14px;
        font-weight:950;
    }

    .gr-live-badge{
        background:#e0f7ef;
        color:#168453;
        border:1px solid #c3ead8;
        border-radius:999px;
        font-size:10px;
        font-weight:900;
        padding:3px 8px;
    }

    .gr-table{
        width:100%;
        margin:0;
        border-collapse:collapse;
    }

    .gr-table{
        table-layout:fixed;
    }

    .gr-table thead th{
        background:linear-gradient(180deg, var(--gov-green-dark) 0%, var(--gov-green) 100%);
        color:#ffffff;
        font-size:10.5px;
        font-weight:900;
        text-transform:uppercase;
        letter-spacing:.035em;
        border-bottom:2px solid var(--gold);
        padding:10px 8px;
        white-space:normal;
        line-height:1.2;
    }

    .gr-table tbody td{
        padding:8px 8px;
        font-size:11.2px;
        color:#243d34;
        border-bottom:1px solid #f0f7f4;
        vertical-align:middle;
        line-height:1.25;
        word-break:break-word;
    }

    .gr-table tbody tr:hover td{
        background:#fbfefd;
    }

    .gr-row-title{
        font-weight:900;
        color:#10231d;
        line-height:1.25;
        max-width:180px;
        display:-webkit-box;
        -webkit-line-clamp:2;
        -webkit-box-orient:vertical;
        overflow:hidden;
    }

    .gr-row-sub{
        color:var(--gr-muted);
        font-size:10.5px;
        font-weight:650;
        margin-top:2px;
        max-width:190px;
        display:-webkit-box;
        -webkit-line-clamp:2;
        -webkit-box-orient:vertical;
        overflow:hidden;
    }

    .gr-type-badge,
    .gr-district-badge{
        display:inline-flex;
        align-items:center;
        gap:5px;
        border-radius:8px;
        padding:4px 8px;
        font-size:11px;
        font-weight:850;
        white-space:normal;
        line-height:1.25;
    }

    .gr-type-badge{
        color:#14532d;
        background:#ecfdf3;
        border:1px solid #bbf7d0;
    }

    .gr-district-badge{
        color:#14532d;
        background:#ecfdf3;
        border:1px solid #bbf7d0;
    }

    .gr-user{
        display:flex;
        align-items:center;
        gap:8px;
        min-width:160px;
    }

    .gr-user-avatar{
        width:32px;
        height:32px;
        border-radius:10px;
        display:grid;
        place-items:center;
        background:#e0f7ef;
        border:1px solid #c3ead8;
        color:#14532d;
        font-size:12px;
        font-weight:950;
        flex:0 0 auto;
    }

    .gr-user-name{
        color:#10231d;
        font-size:12px;
        font-weight:900;
        line-height:1.2;
    }

    .gr-user-role{
        color:var(--gr-muted);
        font-size:11px;
        font-weight:650;
        line-height:1.2;
    }

    .gr-date{
        color:#10231d;
        font-size:12px;
        font-weight:900;
        white-space:nowrap;
    }

    .gr-time{
        color:var(--gr-muted);
        font-size:11px;
        font-weight:700;
        white-space:nowrap;
    }

    .gr-view-btn{
        width:34px;
        height:34px;
        border-radius:10px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        border:1px solid #cbd8d4;
        background:#ffffff;
        color:#14532d;
        text-decoration:none;
        transition:.18s ease;
    }

    .gr-view-btn:hover{
        background:#14532d;
        color:#ffffff;
        border-color:#14532d;
        transform:translateY(-1px);
    }

    .inspection-pagination-bar{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:14px;
        padding:14px 16px;
        border-top:1px solid var(--gr-border);
        background:#fff;
    }

    .inspection-pagination-summary-group{display:flex; flex-direction:column; gap:3px; min-width:240px;}
    .inspection-pagination-summary{color:#334155; font-size:13px; font-weight:850; white-space:nowrap;}
    .inspection-pagination-per-page{color:#64748b; font-size:12px; font-weight:750; white-space:nowrap;}
    .inspection-pagination-nav{display:flex; align-items:center; justify-content:flex-end; gap:6px; flex-wrap:wrap;}
    .inspection-page-link,
    .inspection-page-number,
    .inspection-page-dots{
        min-width:38px;
        height:36px;
        padding:0 12px;
        border-radius:11px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        border:1px solid #cbd5e1;
        background:#fff;
        color:#14532d;
        font-size:13px;
        font-weight:900;
        text-decoration:none;
        line-height:1;
        white-space:nowrap;
    }
    .inspection-page-link{gap:6px;}
    .inspection-page-number.active{background:#166534; color:#fff; border-color:#166534; box-shadow:0 8px 18px rgba(22,101,52,.22);}
    .inspection-page-link:hover,
    .inspection-page-number:hover{background:#ecfdf3; color:#14532d; border-color:#86efac;}
    .inspection-page-link.disabled{pointer-events:none; color:#94a3b8; background:#f1f5f9; border-color:#e2e8f0; box-shadow:none;}
    .inspection-page-dots{border-color:transparent; background:transparent; color:#94a3b8; min-width:26px; padding:0 4px;}

    @media (max-width:1199px){
        .gr-metric-grid.metric-count-4,
        .gr-metric-grid.metric-count-7,
        .gr-metric-grid.metric-count-8,
        .gr-metric-grid.metric-count-many{grid-template-columns:repeat(3, minmax(0,1fr));}
        .gr-donut-row{grid-template-columns:repeat(2, minmax(0,1fr));}
        .gr-graph-row{grid-template-columns:repeat(2, minmax(0,1fr));}
    }

    @media (max-width:991px){
        .inspection-pagination-bar{flex-direction:column; align-items:flex-start;}
        .inspection-pagination-summary{white-space:normal;}
        .inspection-pagination-summary-group{min-width:0;}
        .inspection-pagination-nav{justify-content:flex-start; width:100%;}
    }

    @media (max-width:767px){
        .gr-hero h1{font-size:23px;}
        .gr-metric-grid,
        .gr-metric-grid.metric-count-1,
        .gr-metric-grid.metric-count-2,
        .gr-metric-grid.metric-count-3,
        .gr-metric-grid.metric-count-4,
        .gr-metric-grid.metric-count-5,
        .gr-metric-grid.metric-count-6,
        .gr-metric-grid.metric-count-7,
        .gr-metric-grid.metric-count-8,
        .gr-metric-grid.metric-count-9,
        .gr-metric-grid.metric-count-many,
        .gr-donut-row,
        .gr-donut-row.donut-count-1,
        .gr-donut-row.donut-count-2,
        .gr-graph-row{grid-template-columns:1fr;}
        .gr-panel-body{padding:14px;}
        .gr-table thead{display:none;}
        .gr-table,
        .gr-table tbody,
        .gr-table tr,
        .gr-table td{display:block; width:100%;}
        .gr-table tbody tr{border-bottom:1px solid #edf5f2; padding:10px 12px;}
        .gr-table tbody td{border-bottom:0; padding:5px 0;}
        .inspection-page-link,
        .inspection-page-number,
        .inspection-page-dots{min-width:34px; height:34px; padding:0 10px; font-size:12px;}
    }
</style>
@endpush

@section('content')
@php
    $activePeriod = $filters['period_type'] ?? 'last_week';
@endphp

<div class="gr-page">
    <div class="gr-hero">
        <div>
            <div class="gr-eyebrow"><i class="bi bi-pie-chart"></i> District Wise KPI Graphical Report</div>
            <h1>{{ $scopeTitle ?? 'PUNJAB' }}</h1>
            <p>Graphical and tabular review of selected KPI category and administrative scope. Sidebar, routes, filters, and data variables remain unchanged.</p>
        </div>

        <div class="gr-actions">
            <a href="{{ route('kpi.provincial-data') }}" class="btn-gov btn-gov-outline">
                <i class="bi bi-table"></i> Provincial Data
            </a>
            <a href="{{ route('kpi.reporting-status') }}" class="btn-gov btn-gov-outline">
                <i class="bi bi-list-check"></i> Reporting Status
            </a>
            @if(Route::has('kpi.district-wise-kpi-score'))
                <a href="{{ route('kpi.district-wise-kpi-score', ['kpi_category_id' => $filters['kpi_category_id'] ?? null, 'period_type' => $activePeriod]) }}" class="btn-gov btn-gov-primary">
                    <i class="bi bi-speedometer2"></i> District Wise KPI Score
                </a>
            @endif
        </div>
    </div>

    <div class="gr-filter-card">
        <div class="gr-panel-head">
            <div>
                <div class="gr-panel-title"><i class="bi bi-funnel"></i> Filters</div>
                <div class="gr-panel-subtitle">Select KPI category, period, week, month, or year. Existing data options are unchanged.</div>
            </div>
        </div>
        <div class="gr-panel-body">
            <form id="graphicalReportFilters" method="GET" action="{{ route('kpi.graphical-report') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <label class="form-label">KPI Category</label>
                        <select name="kpi_category_id" class="form-select">
                            @foreach ($kpiCategories as $category)
                                <option value="{{ $category->id }}" {{ ($filters['kpi_category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <label class="form-label">Period</label>
                        <select name="period_type" class="form-select">
                            @foreach(($periodOptions ?? []) as $value => $label)
                                <option value="{{ $value }}" {{ $activePeriod === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <label class="form-label">Week</label>
                        <select name="week_no" class="form-select" {{ $activePeriod !== 'weekly' ? 'disabled' : '' }}>
                            <option value="">-</option>
                            @foreach(($weekOptions ?? []) as $wk => $label)
                                <option value="{{ $wk }}" {{ (string)($filters['week_no'] ?? '') === (string)$wk ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-lg-2 col-md-6">
                        <label class="form-label">Month</label>
                        <select name="month" class="form-select">
                            <option value="">-</option>
                            @foreach(($months ?? []) as $m)
                                <option value="{{ $m['value'] }}" {{ (string)($filters['month'] ?? '') === (string)$m['value'] ? 'selected' : '' }}>{{ $m['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-lg-2 col-md-6">
                        <label class="form-label">Year</label>
                        <select name="year" class="form-select">
                            <option value="">-</option>
                            @foreach(($years ?? []) as $y)
                                <option value="{{ $y }}" {{ (string)($filters['year'] ?? '') === (string)$y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-12 d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn-gov btn-gov-primary">
                            <i class="bi bi-search"></i> Apply
                        </button>
                        <a href="{{ route('kpi.graphical-report') }}" class="btn-gov btn-gov-outline">
                            <i class="bi bi-arrow-clockwise"></i> Reset
                        </a>
                        <button type="submit" class="btn-gov btn-gov-outline" name="report" value="1">
                            <i class="bi bi-printer"></i> Report
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="graphicalReportContentContainer">
        @include('kpi.partials._graphical-report-content', [
            'filters' => $filters,
            'scopeTitle' => $scopeTitle ?? 'PUNJAB',
            'summaryCards' => $summaryCards ?? [],
            'chartData' => $chartData ?? [],
            'tableData' => $tableData ?? [],
            'kpiCategories' => $kpiCategories ?? collect(),
            'periodOptions' => $periodOptions ?? [],
        ])
    </div>
</div>
@endsection

@push('scripts')
<script>
    const grPalette = {
        green: '#18b979',
        darkGreen: '#0b3b2e',
        teal: '#0d9488',
        amber: '#f59e0b',
        red: '#dc5a5a',
        blue: '#2f80ed',
        purple: '#7c3aed',
        grey: '#dbe8e3',
        text: '#10231d',
        muted: '#658579',
        grid: '#edf5f2',
    };

    const chartInstances = {};

    function destroyCharts() {
        Object.keys(chartInstances).forEach((key) => {
            try { chartInstances[key].destroy(); } catch (e) {}
            delete chartInstances[key];
        });
    }


    function makeLine(id, labels, datasets, extraOptions = {}) {
        const el = document.getElementById(id);
        if (!el) return null;

        return new Chart(el, {
            type: 'line',
            data: {
                labels,
                datasets: datasets.map((d, i) => ({
                    label: d.label,
                    data: d.data,
                    borderColor: [palette.greenBright, palette.blue, palette.amber, palette.teal, palette.red, palette.purple][i % 6],
                    backgroundColor: [palette.greenBright, palette.blue, palette.amber, palette.teal, palette.red, palette.purple][i % 6] + '22',
                    borderWidth: 2,
                    tension: .35,
                    pointRadius: 3,
                    pointHoverRadius: 4,
                    fill: true,
                })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10, weight: '600' } } },
                    tooltip: { titleFont: { size: 12, weight: '700' }, bodyFont: { size: 12 } }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10, weight: '600' }, color: palette.muted, maxRotation: 35 } },
                    y: { beginAtZero: true, grid: { color: '#edf5f2' }, ticks: { precision: 0, font: { size: 10 }, color: palette.muted }, border: { display: false } }
                },
                ...extraOptions
            }
        });
    }

    function extractChartDataFromContent() {
        const el = document.getElementById('graphicalReportContent');
        if (!el) return {};
        const raw = el.getAttribute('data-chart');
        if (!raw) return {};
        try { return JSON.parse(raw); } catch (e) { return {}; }
    }

    function makeDonut(id, labels, data, colors) {
        const el = document.getElementById(id);
        if (!el || !window.Chart || !labels || !labels.length) return null;

        return new Chart(el, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 9,
                            boxHeight: 9,
                            usePointStyle: true,
                            pointStyle: 'rectRounded',
                            color: grPalette.muted,
                            font: { size: 10, weight: '600' }
                        }
                    },
                    tooltip: {
                        titleFont: { size: 12, weight: '700' },
                        bodyFont: { size: 12 }
                    }
                }
            }
        });
    }

    function makeBar(id, labels, datasets, extraOptions = {}) {
        const el = document.getElementById(id);
        if (!el || !window.Chart) return null;

        const colors = [grPalette.green, grPalette.blue, grPalette.amber, grPalette.red, grPalette.teal, grPalette.purple];

        return new Chart(el, {
            type: 'bar',
            data: {
                labels: labels || [],
                datasets: (datasets || []).map((d, i) => ({
                    label: d.label || 'Value',
                    data: d.data || [],
                    backgroundColor: d.backgroundColor || colors[i % colors.length],
                    borderRadius: 7,
                    borderSkipped: false,
                    maxBarThickness: extraOptions.indexAxis === 'y' ? 18 : 28,
                })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: (datasets || []).length > 1,
                        position: 'bottom',
                        labels: {
                            boxWidth: 9,
                            color: grPalette.muted,
                            font: { size: 10, weight: '600' }
                        }
                    },
                    tooltip: {
                        titleFont: { size: 12, weight: '700' },
                        bodyFont: { size: 12 }
                    }
                },
                scales: {
                    x: {
                        grid: { display: extraOptions.indexAxis === 'y', color: grPalette.grid },
                        ticks: { color: grPalette.muted, font: { size: 10, weight: '600' }, maxRotation: 35 },
                        border: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { display: extraOptions.indexAxis !== 'y', color: grPalette.grid },
                        ticks: { precision: 0, color: extraOptions.indexAxis === 'y' ? grPalette.text : grPalette.muted, font: { size: 10, weight: '600' } },
                        border: { display: false }
                    }
                },
                ...extraOptions
            }
        });
    }

    function renderCharts(data) {
        destroyCharts();

        if (!window.Chart) {
            console.warn('Chart.js is not loaded. Add Chart.js in layouts/app.blade.php or this page.');
            return;
        }

        if (!data || typeof data !== 'object') return;

        const donutColors = [grPalette.green, grPalette.blue, grPalette.amber, grPalette.red, grPalette.teal, grPalette.purple];

        const donuts = data?.meta?.donuts ?? [
            { key: 'coverageChart', id: 'coverageChart' },
            { key: 'functionalChart', id: 'functionalChart' },
            { key: 'cleanlinessChart', id: 'cleanlinessChart' },
            { key: 'filterChangeChart', id: 'filterChangeChart' },
        ];

        const bars = data?.meta?.large ?? [
            { key: 'districtBarChart', id: 'districtBarChart' },
            { key: 'districtFunctionalChart', id: 'districtFunctionalChart' },
            { key: 'topDistrictsChart', id: 'topDistrictsChart' },
            { key: 'topIssuesChart', id: 'topIssuesChart' },
        ];

        donuts.forEach((c) => {
            const cfg = data?.[c.key];
            if (!cfg || !cfg.labels || !cfg.data) return;
            const inst = makeDonut(c.id, cfg.labels, cfg.data, cfg.colors || donutColors);
            if (inst) chartInstances[c.id] = inst;
        });

        bars.forEach((c, index) => {
            const cfg = data?.[c.key];
            if (!cfg || !cfg.labels || !cfg.datasets) return;

            const options = cfg.options || {};
            if (String(c.key || '').toLowerCase().includes('top') && !options.indexAxis) {
                options.indexAxis = 'y';
            }

            const inst = makeBar(c.id, cfg.labels, cfg.datasets, options);
            if (inst) chartInstances[c.id] = inst;
        });
    }

    function updateQueryString(serialized) {
        const qs = serialized ? ('?' + serialized) : '';
        window.history.pushState({}, '', window.location.pathname + qs);
    }

    function toggleWeekDisabled() {
        const period = document.querySelector('#graphicalReportFilters select[name="period_type"]')?.value;
        const weekSelect = document.querySelector('#graphicalReportFilters select[name="week_no"]');
        if (!weekSelect) return;
        weekSelect.disabled = (period !== 'weekly');
    }

    function loadGraphicalReportData(extra = {}) {
        const form = document.getElementById('graphicalReportFilters');
        if (!form) return;

        const params = new URLSearchParams(new FormData(form));
        Object.keys(extra).forEach((key) => params.set(key, extra[key]));

        const container = document.getElementById('graphicalReportContentContainer');
        if (container) container.classList.add('loading');

        // Prefer jQuery if available, otherwise fallback to fetch (still AJAX, no full reload).
        if (window.jQuery) {
            jQuery.ajax({
                url: "{{ route('kpi.graphical-report.data') }}",
                type: "GET",
                data: params.toString(),
                success: function (res) {
                    if (res && res.status === 'success') {
                        jQuery('#graphicalReportContentContainer').html(res.html);
                        toggleWeekDisabled();
                        renderCharts(extractChartDataFromContent());
                        updateQueryString(params.toString());
                    }
                },
                error: function () {
                    if (container) {
                        container.innerHTML = '<div class="gr-empty"><div>Failed to load report data.</div></div>';
                    }
                },
                complete: function () {
                    if (container) container.classList.remove('loading');
                }
            });
            return;
        }

        fetch("{{ route('kpi.graphical-report.data') }}?" + params.toString(), {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((r) => r.json())
            .then((res) => {
                if (res && res.status === 'success') {
                    container.innerHTML = res.html;
                    toggleWeekDisabled();
                    renderCharts(extractChartDataFromContent());
                    updateQueryString(params.toString());
                }
            })
            .catch(() => {
                if (container) {
                    container.innerHTML = '<div class="gr-empty"><div>Failed to load report data.</div></div>';
                }
            })
            .finally(() => {
                if (container) container.classList.remove('loading');
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        toggleWeekDisabled();
        renderCharts(extractChartDataFromContent());

        // Strict onchange: any dropdown change triggers AJAX
        document.getElementById('graphicalReportFilters')?.addEventListener('change', function (e) {
            const t = e.target;
            if (!t) return;
            if (t.matches('select')) {
                loadGraphicalReportData();
            }
        });

        // Keep Apply/Report buttons but do not reload page
        document.getElementById('graphicalReportFilters')?.addEventListener('submit', function (e) {
            e.preventDefault();
            loadGraphicalReportData();
        });

        // Pagination via AJAX
        document.addEventListener('click', function (e) {
            const a = e.target?.closest?.('#graphicalReportContentContainer .inspection-pagination-nav a, #graphicalReportContentContainer .pagination a');
            if (!a) return;
            const href = a.getAttribute('href');
            if (!href || href === 'javascript:void(0)' || href === '#') return;
            e.preventDefault();
            const url = new URL(href, window.location.origin);
            const page = url.searchParams.get('page');
            loadGraphicalReportData(page ? { page } : {});
        });
    });
</script>
@endpush

@extends('layouts.app')

@section('title', 'District Wise KPI Graphical Report')

@push('styles')
<style>
    :root{
        --ppmf-green: #006b3f;
        --ppmf-green-dark: #004f2e;
        --ppmf-green-soft: #e8f6ef;
        --ppmf-border: #dbe5ee;
        --ppmf-muted: #64748b;
        --ppmf-text: #0f172a;
        --ppmf-bg: #f4f7fa;
        --ppmf-teal: #17b8c1;
        --ppmf-amber: #f8c945;
        --ppmf-red: #e35d6a;
    }

    .gr-hero{
        background: #fff;
        border: 1px solid var(--ppmf-border);
        border-radius: 20px;
        padding: 18px 18px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap: 14px;
        flex-wrap: wrap;
    }

    .gr-eyebrow{
        display:inline-flex;
        align-items:center;
        gap:8px;
        background: var(--ppmf-green-soft);
        color: var(--ppmf-green-dark);
        border: 1px solid #cfe8da;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 900;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .gr-hero h1{
        margin: 8px 0 4px;
        font-size: 30px;
        font-weight: 950;
        letter-spacing: -0.03em;
        color: var(--ppmf-text);
    }

    .gr-hero p{
        margin: 0;
        color: var(--ppmf-muted);
        font-size: 13px;
        font-weight: 650;
        max-width: 820px;
        line-height: 1.45;
    }

    .gr-actions{
        display:flex;
        gap:10px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .gr-actions .btn-gov{
        height: 42px;
        display:inline-flex;
        align-items:center;
        gap:8px;
        border-radius: 12px;
        white-space: nowrap;
    }

    .gr-card{
        background:#fff;
        border:1px solid var(--ppmf-border);
        border-radius: 20px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        overflow:hidden;
    }

    .gr-card-header{
        padding: 14px 16px;
        border-bottom: 1px solid var(--ppmf-border);
        background: linear-gradient(180deg, #f8fafc, #ffffff);
        display:flex;
        align-items:flex-start;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .gr-card-title{
        margin: 0;
        font-size: 16px;
        font-weight: 900;
        color: var(--ppmf-green-dark);
        display:flex;
        align-items:center;
        gap:8px;
    }

    .gr-card-subtitle{
        margin: 4px 0 0;
        font-size: 12px;
        font-weight: 650;
        color: var(--ppmf-muted);
    }

    .gr-card-body{ padding: 16px; }

    .gr-filter .form-label{
        font-size: 12px;
        font-weight: 850;
        color:#334155;
        margin-bottom: 6px;
    }

    .gr-filter .form-control,
    .gr-filter .form-select{
        min-height: 42px;
        border-radius: 12px;
        border-color: #cbd5e1;
        font-size: 13px;
        font-weight: 650;
    }

    .gr-metric-grid{
        display:grid;
        grid-template-columns: repeat(5, minmax(0,1fr));
        gap: 12px;
    }

    .gr-metric-card{
        position:relative;
        border: 1px solid var(--ppmf-border);
        border-radius: 18px;
        padding: 14px;
        background: linear-gradient(180deg, #ffffff, #f8fafc);
        min-height: 120px;
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.04);
        overflow:hidden;
    }

    .gr-metric-card::before{
        content:'';
        position:absolute;
        inset:0 auto 0 0;
        width: 4px;
        background: linear-gradient(180deg, var(--ppmf-green), var(--ppmf-amber));
    }

    .gr-metric-value{
        margin:0;
        color: var(--ppmf-text);
        font-size: 30px;
        font-weight: 950;
        letter-spacing: -0.04em;
        line-height: 1.1;
    }

    .gr-metric-title{
        margin: 8px 0 0;
        color: #14532d;
        font-size: 14px;
        font-weight: 850;
        line-height: 1.3;
    }

    .gr-metric-meta{
        margin-top: 8px;
        display:flex;
        gap:8px;
        flex-wrap: wrap;
        color: var(--ppmf-muted);
        font-size: 12px;
        font-weight: 750;
        align-items:center;
    }

    .gr-pill{
        display:inline-flex;
        align-items:center;
        gap:6px;
        border-radius: 999px;
        padding: 4px 9px;
        font-size: 11px;
        font-weight: 850;
        border: 1px solid #cfe8da;
        background: var(--ppmf-green-soft);
        color: var(--ppmf-green-dark);
        white-space: nowrap;
    }

    .chart-grid{
        display:grid;
        grid-template-columns: repeat(4, minmax(0,1fr));
        gap: 12px;
    }

    .chart-card{
        background:#fff;
        border:1px solid var(--ppmf-border);
        border-radius: 18px;
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.04);
        overflow:hidden;
        min-height: 300px;
        display:flex;
        flex-direction: column;
    }

    .chart-card .head{
        padding: 12px 14px;
        border-bottom: 1px solid var(--ppmf-border);
        background: linear-gradient(180deg, #f8fafc, #ffffff);
        font-size: 14px;
        font-weight: 900;
        color: var(--ppmf-green-dark);
        display:flex;
        align-items:center;
        gap:8px;
    }

    .chart-card .body{
        padding: 10px 12px 14px;
        flex:1;
        position: relative;
    }

    .chart-empty{
        display:grid;
        place-items:center;
        height: 100%;
        color: var(--ppmf-muted);
        font-weight: 750;
        font-size: 13px;
        text-align:center;
        padding: 12px;
    }

    .chart-large-grid{
        display:grid;
        grid-template-columns: 2fr 1fr;
        gap: 12px;
    }

    .chart-large{
        min-height: 380px;
    }

    .gr-table thead th{
        background: var(--ppmf-green-dark);
        color:#fff;
        font-size: 12px;
        font-weight: 850;
        letter-spacing: 0.03em;
        padding-top: 12px;
        padding-bottom: 12px;
        white-space: nowrap;
    }

    .gr-table tbody td{
        font-size: 13px;
        padding-top: 10px;
        padding-bottom: 10px;
        vertical-align: top;
    }

    .gr-table tbody tr:hover td{
        background:#f8fafc;
    }

    /* Reuse Inspection List button look for detail */
    .inspection-view-btn{
        width: 36px;
        height: 36px;
        border-radius: 11px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #14532d;
        text-decoration:none;
        transition: 0.18s ease;
    }

    .inspection-view-btn:hover{
        background: #166534;
        color: #ffffff;
        border-color: #166534;
        transform: translateY(-1px);
    }

    /* Inspection List style helpers (copied/adapted) */
    .inspection-user{
        display:flex;
        align-items:center;
        gap:10px;
        min-width: 190px;
    }
    .inspection-user-avatar{
        width: 34px;
        height: 34px;
        border-radius: 12px;
        display:grid;
        place-items:center;
        background: #ecfdf3;
        border: 1px solid #bbf7d0;
        color: #14532d;
        font-weight: 900;
        font-size: 13px;
        flex-shrink: 0;
    }
    .inspection-user-name{
        font-weight: 900;
        color:#0f172a;
        line-height: 1.2;
        font-size: 13px;
    }
    .inspection-user-role{
        color:#64748b;
        font-size: 12px;
        font-weight: 650;
    }
    .inspection-date{
        font-weight: 900;
        font-size: 13px;
        color:#0f172a;
        line-height: 1.2;
        white-space: nowrap;
    }
    .inspection-time{
        font-size: 12px;
        font-weight: 750;
        color:#64748b;
        white-space: nowrap;
    }
    .inspection-status-badge{
        border-radius: 999px;
        padding: 5px 10px;
        font-size: 12px;
        font-weight: 850;
        display:inline-flex;
        align-items:center;
        gap:6px;
        border: 1px solid var(--ppmf-border);
        white-space: nowrap;
    }
    .inspection-status-badge.status-approved{ background:#ecfdf3; color:#14532d; border-color:#bbf7d0; }
    .inspection-status-badge.status-reviewed{ background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }
    .inspection-status-badge.status-submitted{ background:#fffbeb; color:#92400e; border-color:#fde68a; }
    .inspection-status-badge.status-rejected{ background:#fff1f2; color:#be123c; border-color:#fecdd3; }

    .inspection-pagination-bar{
        display:flex;
        align-items:center;
        justify-content: space-between;
        gap: 14px;
        padding: 14px 18px;
        border-top: 1px solid var(--ppmf-border);
        background:#fff;
    }
    .inspection-pagination-summary-group{
        display:flex;
        flex-direction: column;
        gap: 3px;
        min-width: 240px;
    }
    .inspection-pagination-summary{
        color:#334155;
        font-size: 13px;
        font-weight: 850;
        white-space: nowrap;
    }
    .inspection-pagination-per-page{
        color:#64748b;
        font-size: 12px;
        font-weight: 750;
        white-space: nowrap;
    }
    .inspection-pagination-nav{
        display:flex;
        align-items:center;
        justify-content: flex-end;
        gap: 6px;
        flex-wrap: wrap;
    }
    .inspection-page-link,
    .inspection-page-number,
    .inspection-page-dots{
        min-width: 38px;
        height: 36px;
        padding: 0 12px;
        border-radius: 11px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        border: 1px solid #cbd5e1;
        background:#fff;
        color:#14532d;
        font-size: 13px;
        font-weight: 900;
        text-decoration:none;
        line-height: 1;
        white-space: nowrap;
    }
    .inspection-page-link{ gap: 6px; }
    .inspection-page-number.active{
        background:#166534;
        color:#fff;
        border-color:#166534;
        box-shadow: 0 8px 18px rgba(22, 101, 52, 0.22);
    }
    .inspection-page-link:hover,
    .inspection-page-number:hover{
        background:#ecfdf3;
        color:#14532d;
        border-color:#86efac;
    }
    .inspection-page-link.disabled{
        pointer-events:none;
        color:#94a3b8;
        background:#f1f5f9;
        border-color:#e2e8f0;
        box-shadow:none;
    }
    .inspection-page-dots{
        border-color: transparent;
        background: transparent;
        color:#94a3b8;
        min-width: 26px;
        padding: 0 4px;
    }
    @media (max-width: 991px){
        .inspection-pagination-bar{ flex-direction: column; align-items: flex-start; }
        .inspection-pagination-summary{ white-space: normal; }
        .inspection-pagination-summary-group{ min-width: 0; }
        .inspection-pagination-nav{ justify-content: flex-start; width: 100%; }
    }
    @media (max-width: 767px){
        .inspection-pagination-bar{ padding: 14px; }
        .inspection-page-link,
        .inspection-page-number,
        .inspection-page-dots{
            min-width: 34px;
            height: 34px;
            padding: 0 10px;
            font-size: 12px;
        }
    }

    .gr-pagination{
        display:flex;
        justify-content: space-between;
        align-items:center;
        flex-wrap: wrap;
        gap: 12px;
    }

    .gr-pagination .pagination{ margin:0; gap:6px; flex-wrap:wrap; }
    .gr-pagination .page-link{
        border-radius: 10px !important;
        min-width: 38px;
        min-height: 36px;
        font-weight: 800;
        box-shadow: none;
    }

    @media (max-width: 1399px){
        .gr-metric-grid{ grid-template-columns: repeat(4, minmax(0,1fr)); }
    }
    @media (max-width: 1199px){
        .gr-metric-grid{ grid-template-columns: repeat(3, minmax(0,1fr)); }
        .chart-grid{ grid-template-columns: repeat(2, minmax(0,1fr)); }
        .chart-large-grid{ grid-template-columns: 1fr; }
    }
    @media (max-width: 767px){
        .gr-metric-grid{ grid-template-columns: 1fr; }
        .chart-grid{ grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
@php
    $activePeriod = $filters['period_type'] ?? 'last_week';
    $perPage = (int) ($filters['per_page'] ?? 10);

    $cleanMetricText = function ($text) {
        $text = trim((string) $text);
        if ($text === '') return null;
        $lower = strtolower($text);
        foreach (['dummy', 'test', 'sample', 'lorem', 'seeded'] as $bad) {
            if (str_contains($lower, $bad)) return null;
        }
        return $text;
    };

    $selectedCategory = collect($kpiCategories ?? [])->firstWhere('id', (int) ($filters['kpi_category_id'] ?? 0));
@endphp

<div class="gr-hero mb-4">
    <div>
        <div class="gr-eyebrow"><i class="bi bi-pie-chart"></i> District Wise KPI Graphical Report</div>
        <h1>{{ $scopeTitle ?? 'PUNJAB' }}</h1>
        <p>Graphical and tabular review of selected KPI category and administrative scope.</p>
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

<div class="gr-card gr-filter mb-4">
    <div class="gr-card-header">
        <div>
            <div class="gr-card-title"><i class="bi bi-funnel"></i> Filters</div>
            <div class="gr-card-subtitle">Select KPI category, period, and scope for the graphical report.</div>
        </div>
    </div>
    <div class="gr-card-body">
        <form method="GET" action="{{ route('kpi.graphical-report') }}">
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
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
                </div>

                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
                </div>

                <div class="col-xl-1 col-lg-2 col-md-6">
                    <label class="form-label">Month</label>
                    <select name="month" class="form-select">
                        <option value="">-</option>
                        @foreach(($months ?? []) as $m)
                            <option value="{{ $m['value'] }}" {{ (string)($filters['month'] ?? '') === (string)$m['value'] ? 'selected' : '' }}>{{ $m['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-1 col-lg-2 col-md-6">
                    <label class="form-label">Year</label>
                    <select name="year" class="form-select">
                        <option value="">-</option>
                        @foreach(($years ?? []) as $y)
                            <option value="{{ $y }}" {{ (string)($filters['year'] ?? '') === (string)$y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">District</label>
                    <select name="district_id" class="form-select">
                        <option value="">Punjab / All Districts</option>
                        @foreach ($districts as $district)
                            <option value="{{ $district->id }}" {{ ($filters['district_id'] ?? '') == $district->id ? 'selected' : '' }}>
                                {{ $district->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">Tehsil</label>
                    <select name="tehsil_id" class="form-select">
                        <option value="">All Tehsils</option>
                        @foreach ($tehsils as $tehsil)
                            <option value="{{ $tehsil->id }}" {{ ($filters['tehsil_id'] ?? '') == $tehsil->id ? 'selected' : '' }}>
                                {{ $tehsil->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label">Per Page</label>
                    <select name="per_page" class="form-select">
                        @foreach ([10, 20, 25, 50] as $option)
                            <option value="{{ $option }}" {{ $perPage === $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-4 col-lg-8 col-md-12">
                    <label class="form-label">Search (Table)</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Search name/address/remarks">
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

<div class="gr-card mb-4">
    <div class="gr-card-header">
        <div>
            <div class="gr-card-title"><i class="bi bi-grid-3x3-gap"></i> KPI Metric Cards</div>
            <div class="gr-card-subtitle">
                {{ $selectedCategory->name ?? 'KPI Category' }} — {{ $periodOptions[$activePeriod] ?? 'Last Week' }}
            </div>
        </div>
    </div>
    <div class="gr-card-body">
        @if (count($summaryCards ?? []))
            <div class="gr-metric-grid">
                @foreach($summaryCards as $card)
                    <div class="gr-metric-card">
                        <div class="gr-metric-value">{{ $card['value'] ?? '-' }}</div>
                        <div class="gr-metric-title">{{ $card['title'] ?? 'Metric' }}</div>
                        <div class="gr-metric-meta">
                            <span class="gr-pill"><i class="bi bi-tag"></i> {{ $card['unit'] ?? 'VALUE' }}</span>
                            @if(!empty($card['source']))
                                <span class="gr-pill"><i class="bi bi-person-badge"></i> {{ $card['source'] }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="chart-empty">
                <div>
                    <div class="fw-bold mb-1">No KPI metric cards available</div>
                    <div>Select a KPI category/period with seeded metrics.</div>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="gr-card mb-4">
    <div class="gr-card-header">
        <div>
            <div class="gr-card-title"><i class="bi bi-graph-up"></i> Charts</div>
            <div class="gr-card-subtitle">Donut and district-wise comparison charts for selected KPI category.</div>
        </div>
    </div>
    <div class="gr-card-body">
        @php
            $donuts = $chartData['meta']['donuts'] ?? [
                ['key' => 'coverageChart', 'id' => 'coverageChart', 'title' => 'Metric Distribution', 'icon' => 'bi-pie-chart'],
                ['key' => 'functionalChart', 'id' => 'functionalChart', 'title' => 'Metric Distribution', 'icon' => 'bi-pie-chart'],
                ['key' => 'cleanlinessChart', 'id' => 'cleanlinessChart', 'title' => 'Top Districts', 'icon' => 'bi-geo-alt'],
                ['key' => 'filterChangeChart', 'id' => 'filterChangeChart', 'title' => 'Average Score', 'icon' => 'bi-speedometer2'],
            ];
            $largeCharts = $chartData['meta']['large'] ?? [
                ['key' => 'districtBarChart', 'id' => 'districtBarChart', 'title' => 'District Comparison', 'icon' => 'bi-bar-chart'],
                ['key' => 'topDistrictsChart', 'id' => 'topDistrictsChart', 'title' => 'Top Districts', 'icon' => 'bi-diagram-3'],
            ];
        @endphp

        <div class="chart-grid mb-3">
            @foreach ($donuts as $c)
                <div class="chart-card">
                    <div class="head"><i class="bi {{ $c['icon'] }}"></i> {{ $c['title'] }}</div>
                    <div class="body">
                        @if(!empty(($chartData[$c['key']] ?? null)))
                            <canvas id="{{ $c['id'] }}"></canvas>
                        @else
                            <div class="chart-empty">No chart data available for selected filters.</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="chart-large-grid">
            @foreach ($largeCharts as $c)
                <div class="chart-card chart-large">
                    <div class="head"><i class="bi {{ $c['icon'] }}"></i> {{ $c['title'] }}</div>
                    <div class="body">
                        @if(!empty(($chartData[$c['key']] ?? null)))
                            <canvas id="{{ $c['id'] }}"></canvas>
                        @else
                            <div class="chart-empty">No chart data available for selected filters.</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="gr-card">
    <div class="gr-card-header">
        <div>
            <div class="gr-card-title"><i class="bi bi-table"></i> KPI Detail Records</div>
            <div class="gr-card-subtitle">Inspection/location records for selected KPI category and scope.</div>
        </div>
    </div>
    <div class="gr-card-body p-0">
        <div class="table-responsive">
            <table class="table-ppmf gr-table">
                <thead>
                    <tr>
                        <th style="width:70px;">Sr.</th>
                        <th>Type</th>
                        <th>Name / Field 1</th>
                        <th>Address / Field 2</th>
                        <th>Tehsil</th>
                        <th>District</th>
                        <th>User</th>
                        <th>Date &amp; Time</th>
                        <th class="text-center">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tableData as $index => $row)
                        @php
                            $detailUrl = Route::has('inspections.show') ? route('inspections.show', $row->id) : '#';
                        @endphp
                        <tr>
                            <td>{{ method_exists($tableData, 'firstItem') ? $tableData->firstItem() + $index : $index + 1 }}</td>
                            <td>{{ $row->kpiCategory->name ?? 'N/A' }}</td>
                            <td>{{ $row->main_title ?? 'N/A' }}</td>
                            <td>{{ $row->main_address ?? 'N/A' }}</td>
                            <td>{{ $row->tehsil->name ?? 'N/A' }}</td>
                            <td>{{ $row->district->name ?? 'N/A' }}</td>
                            <td>
                                <div class="inspection-user">
                                    <div class="inspection-user-avatar">
                                        {{ strtoupper(substr($row->performer->username ?? $row->performer->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="inspection-user-name">
                                            {{ $row->performer->username ?? $row->performer->name ?? 'N/A' }}
                                        </div>
                                        <small class="inspection-user-role">
                                            {{ $row->performer->designation ?? '' }}
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($row->inspection_datetime)
                                    <div class="inspection-date">{{ \Carbon\Carbon::parse($row->inspection_datetime)->format('d M, Y') }}</div>
                                    <div class="inspection-time">{{ \Carbon\Carbon::parse($row->inspection_datetime)->format('h:i A') }}</div>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ $detailUrl }}" class="inspection-view-btn" title="View Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <div class="manual-box-ppmf">
                                    <i class="bi bi-inbox"></i>
                                    <h5>No KPI detail records</h5>
                                    <p>No inspection records found for selected filters.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination (match Inspection List colours/style) --}}
        @if (method_exists($tableData, 'lastPage') && $tableData->lastPage() > 1)
            @php
                $tableData->appends(request()->query());

                $currentPage = $tableData->currentPage();
                $lastPage = $tableData->lastPage();
                $startPage = max(1, $currentPage - 2);
                $endPage = min($lastPage, $currentPage + 2);

                if ($currentPage <= 3) {
                    $endPage = min($lastPage, 5);
                }

                if ($currentPage >= $lastPage - 2) {
                    $startPage = max(1, $lastPage - 4);
                }

                $currentPerPage = (int) ($filters['per_page'] ?? request('per_page', 10));
            @endphp

            <div class="inspection-pagination-bar">
                <div class="inspection-pagination-summary-group">
                    <div class="inspection-pagination-summary">
                        Showing {{ number_format($tableData->firstItem() ?? 0) }} to {{ number_format($tableData->lastItem() ?? 0) }}
                        of {{ number_format($tableData->total() ?? 0) }} records
                    </div>
                    <div class="inspection-pagination-per-page">
                        {{ $currentPerPage }} per page
                    </div>
                </div>

                <nav class="inspection-pagination-nav" aria-label="Graphical report pagination">
                    <a
                        href="{{ $tableData->previousPageUrl() ?: 'javascript:void(0)' }}"
                        class="inspection-page-link {{ $tableData->onFirstPage() ? 'disabled' : '' }}"
                    >
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </a>

                    @if ($startPage > 1)
                        <a href="{{ $tableData->url(1) }}" class="inspection-page-number">1</a>
                        @if ($startPage > 2)
                            <span class="inspection-page-dots">...</span>
                        @endif
                    @endif

                    @for ($page = $startPage; $page <= $endPage; $page++)
                        <a
                            href="{{ $tableData->url($page) }}"
                            class="inspection-page-number {{ $page == $currentPage ? 'active' : '' }}"
                        >
                            {{ $page }}
                        </a>
                    @endfor

                    @if ($endPage < $lastPage)
                        @if ($endPage < $lastPage - 1)
                            <span class="inspection-page-dots">...</span>
                        @endif
                        <a href="{{ $tableData->url($lastPage) }}" class="inspection-page-number">{{ $lastPage }}</a>
                    @endif

                    <a
                        href="{{ $tableData->nextPageUrl() ?: 'javascript:void(0)' }}"
                        class="inspection-page-link {{ $tableData->hasMorePages() ? '' : 'disabled' }}"
                    >
                        Next
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </nav>
            </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
    const chartData = @json($chartData ?? []);

    const palette = {
        green: '#006b3f',
        darkGreen: '#004f2e',
        softGreen: '#e8f6ef',
        teal: '#17b8c1',
        amber: '#f8c945',
        red: '#e35d6a',
        blue: '#2563eb',
        midGreen: '#198754',
        text: '#0f172a',
        muted: '#64748b',
        border: '#dbe5ee',
    };

    function makeDonut(id, labels, data, colors) {
        const el = document.getElementById(id);
        if (!el) return;
        if (!labels || !labels.length) return;

        new Chart(el, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data,
                    backgroundColor: colors,
                    borderColor: '#ffffff',
                    borderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, font: { size: 11, weight: '600' } }
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
        if (!el) return;

        new Chart(el, {
            type: 'bar',
            data: {
                labels,
                datasets: datasets.map((d, i) => ({
                    label: d.label,
                    data: d.data,
                    backgroundColor: [palette.teal, palette.green, palette.red, palette.blue, palette.amber][i % 5],
                    borderRadius: 8,
                    maxBarThickness: 26,
                })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, font: { size: 11, weight: '600' } }
                    },
                    tooltip: { titleFont: { size: 12, weight: '700' }, bodyFont: { size: 12 } }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 11, weight: '600' }, maxRotation: 55, minRotation: 0 } },
                    y: { beginAtZero: true, grid: { color: '#e8eef5' }, ticks: { precision: 0, font: { size: 11 } } }
                },
                ...extraOptions
            }
        });
    }

    // Donuts (only render if data exists)
    if (chartData.coverageChart) {
        makeDonut('coverageChart', chartData.coverageChart.labels, chartData.coverageChart.data, [palette.teal, palette.amber, palette.green, palette.blue, palette.red]);
    }
    if (chartData.functionalChart) {
        makeDonut('functionalChart', chartData.functionalChart.labels, chartData.functionalChart.data, [palette.green, palette.red, palette.teal, palette.blue, palette.amber]);
    }
    if (chartData.cleanlinessChart) {
        makeDonut('cleanlinessChart', chartData.cleanlinessChart.labels, chartData.cleanlinessChart.data, [palette.green, palette.red, palette.teal, palette.blue, palette.amber]);
    }
    if (chartData.filterChangeChart) {
        makeDonut('filterChangeChart', chartData.filterChangeChart.labels, chartData.filterChangeChart.data, [palette.green, palette.amber, palette.red, palette.teal, palette.blue]);
    }

    // District comparison bar
    if (chartData.districtBarChart) {
        makeBar(
            'districtBarChart',
            chartData.districtBarChart.labels,
            chartData.districtBarChart.datasets,
            chartData.districtBarChart.options ?? {}
        );
    }

    if (chartData.districtFunctionalChart) {
        makeBar(
            'districtFunctionalChart',
            chartData.districtFunctionalChart.labels,
            chartData.districtFunctionalChart.datasets,
            chartData.districtFunctionalChart.options ?? {}
        );
    }

    if (chartData.topDistrictsChart) {
        makeBar(
            'topDistrictsChart',
            chartData.topDistrictsChart.labels,
            chartData.topDistrictsChart.datasets,
            chartData.topDistrictsChart.options ?? {}
        );
    }

    if (chartData.topIssuesChart) {
        makeBar(
            'topIssuesChart',
            chartData.topIssuesChart.labels,
            chartData.topIssuesChart.datasets,
            chartData.topIssuesChart.options ?? {}
        );
    }
</script>
@endpush

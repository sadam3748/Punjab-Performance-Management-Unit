@extends('layouts.app')

@section('title', 'District Wise KPI Score Report')

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
    }

    .kpi-summary .stat-card-ppmf span {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.05em;
    }

    .kpi-summary-value {
        font-size: 22px;
        font-weight: 900;
        color: var(--ppmf-text);
        line-height: 1.2;
    }

    .kpi-summary-period {
        font-size: 22px;
        font-weight: 900;
        color: var(--ppmf-text);
        line-height: 1.2;
    }

    .kpi-selected-category {
        font-weight: 900;
        font-size: 16px;
        line-height: 1.2;
        color: var(--ppmf-text);
        max-width: 520px;
    }

    .kpi-summary .stat-icon-ppmf {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        font-size: 20px;
    }

    .kpi-metric-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
    }

    .kpi-metric-grid.cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
    .kpi-metric-grid.cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .kpi-metric-grid.cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .kpi-metric-grid.cols-4 { grid-template-columns: repeat(3, minmax(0, 1fr)); }

    .kpi-metric-card {
        position: relative;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: linear-gradient(180deg, #ffffff, #f8fafc);
        padding: 12px 12px 10px;
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.05);
        min-height: 132px;
        overflow: hidden;
    }

    .kpi-metric-card::before {
        content: '';
        position: absolute;
        inset: 0 auto 0 0;
        width: 4px;
        background: linear-gradient(180deg, #198754, #d6a84f);
    }

    .kpi-metric-value {
        margin: 0;
        font-size: 26px;
        font-weight: 900;
        letter-spacing: -0.04em;
        color: #0f172a;
        line-height: 1;
    }

    .kpi-metric-unit {
        display: inline-flex;
        margin-top: 8px;
        border-radius: 999px;
        padding: 4px 9px;
        font-size: 10px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        background: #ecfdf5;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .kpi-metric-title {
        margin: 10px 0 4px;
        color: #14532d;
        font-size: 14px;
        font-weight: 800;
    }

    .kpi-metric-desc {
        margin: 0;
        color: #475569;
        font-size: 12px;
        line-height: 1.5;
        font-weight: 600;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: calc(1.5em * 2);
    }

    .sticky-sr {
        position: sticky;
        left: 0;
        background: #fff;
        z-index: 3;
        width: 70px;
        min-width: 70px;
    }

    .sticky-district {
        position: sticky;
        left: 70px;
        background: #fff;
        z-index: 2;
        min-width: 160px;
    }

    .metric-cell {
        min-width: 300px;
    }

    .metric-cell .sub {
        margin-top: 4px;
        font-size: 11px;
        font-weight: 800;
        color: #64748b;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }

    .kpi-score-table thead th {
        background: linear-gradient(180deg, var(--gov-green-dark) 0%, var(--gov-green) 100%);
        color: #fff;
        font-size: 13px;
        font-weight: 700;
        text-align: center;
        vertical-align: middle;
        white-space: normal;
        word-break: normal;
        line-height: 1.35;
        padding: 14px 12px;
        white-space: normal;
        border-bottom: 2px solid var(--gold);
    }

    .kpi-score-table thead th.sticky-sr,
    .kpi-score-table thead th.sticky-district {
        background: linear-gradient(180deg, var(--gov-green-dark) 0%, var(--gov-green) 100%);
        z-index: 5;
    }

    .kpi-score-table tbody td {
        font-size: 13px;
        padding: 14px 12px;
        vertical-align: middle;
        text-align: center;
    }

    .kpi-score-table tbody tr:hover td {
        background: #f8fafc;
    }

    .kpi-score-table tbody tr:hover td.sticky-sr,
    .kpi-score-table tbody tr:hover td.sticky-district {
        background: #f8fafc;
    }

    .kpi-score-table thead th.col-sr {
        min-width: 60px;
        width: 60px;
        max-width: 60px;
    }

    .kpi-score-table thead th.col-district {
        min-width: 160px;
        width: 160px;
        max-width: 160px;
        text-align: left;
    }

    .kpi-score-table thead th.col-evidence {
        min-width: 110px;
        width: 110px;
        max-width: 110px;
    }

    .kpi-score-table tbody td.district-cell {
        text-align: left;
        font-weight: 800;
        text-transform: uppercase;
    }

    .metric-score-label {
        color: #1f2937;
        font-weight: 600;
        margin-bottom: 4px;
        font-size: 12px;
    }

    .metric-score-value {
        color: #0f172a;
        font-weight: 800;
        font-size: 14px;
    }

    .metric-value-text {
        margin-top: 4px;
        font-weight: 800;
        color: #0f172a;
        font-size: 13px;
        line-height: 1.2;
    }

    @media (max-width: 991px) {
        .metric-cell { min-width: 260px; }
    }

    @media (max-width: 575px) {
        .metric-cell { min-width: 220px; }
    }

    .kpi-pagination-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }

    .kpi-pagination-footer .pagination {
        margin: 0;
        gap: 6px;
        flex-wrap: wrap;
    }

    .kpi-pagination-footer .page-link {
        border-radius: 10px !important;
        min-width: 38px;
        min-height: 36px;
        font-weight: 800;
        box-shadow: none;
    }

    /* Match Inspection List pagination look */
    .kpi-pagination-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 14px 18px;
        border-top: 1px solid var(--ppmf-border);
        background: #ffffff;
    }

    .kpi-pagination-summary-group {
        display: flex;
        flex-direction: column;
        gap: 3px;
        min-width: 240px;
    }

    .kpi-pagination-summary {
        color: #334155;
        font-size: 13px;
        font-weight: 850;
        white-space: nowrap;
    }

    .kpi-pagination-controls {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .kpi-per-page-label {
        color: #64748b;
        font-size: 12px;
        font-weight: 750;
        white-space: nowrap;
    }

    .kpi-per-page-select {
        width: 118px;
        height: 36px;
        border-radius: 11px;
        border: 1px solid #cbd5e1;
        font-size: 13px;
        font-weight: 800;
        color: #14532d;
        background: #ffffff;
        padding: 0 10px;
    }

    .kpi-pagination-nav {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 6px;
        flex-wrap: wrap;
    }

    .kpi-page-link,
    .kpi-page-number,
    .kpi-page-dots {
        min-width: 38px;
        height: 36px;
        padding: 0 12px;
        border-radius: 11px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #14532d;
        font-size: 13px;
        font-weight: 900;
        text-decoration: none;
        line-height: 1;
        white-space: nowrap;
    }

    .kpi-page-link {
        gap: 6px;
    }

    .kpi-page-number.active {
        background: #166534;
        color: #ffffff;
        border-color: #166534;
        box-shadow: 0 8px 18px rgba(22, 101, 52, 0.22);
    }

    .kpi-page-link:hover,
    .kpi-page-number:hover {
        background: #ecfdf3;
        color: #14532d;
        border-color: #86efac;
    }

    .kpi-page-link.disabled {
        pointer-events: none;
        color: #94a3b8;
        background: #f1f5f9;
        border-color: #e2e8f0;
        box-shadow: none;
    }

    .kpi-page-dots {
        border-color: transparent;
        background: transparent;
        color: #94a3b8;
        min-width: 26px;
        padding: 0 4px;
    }

    @media (max-width: 991px) {
        .kpi-metric-grid,
        .kpi-metric-grid.cols-4,
        .kpi-metric-grid.cols-3 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .kpi-metric-grid.cols-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .kpi-pagination-bar {
            align-items: flex-start;
            flex-direction: column;
        }

        .kpi-pagination-summary {
            white-space: normal;
        }

        .kpi-pagination-summary-group {
            min-width: 0;
        }

        .kpi-pagination-nav {
            justify-content: flex-start;
            width: 100%;
        }
    }

    @media (max-width: 767px) {
        .kpi-metric-grid,
        .kpi-metric-grid.cols-1,
        .kpi-metric-grid.cols-2,
        .kpi-metric-grid.cols-3,
        .kpi-metric-grid.cols-4 {
            grid-template-columns: 1fr;
        }

        .kpi-pagination-bar {
            padding: 14px;
        }

        .kpi-page-link,
        .kpi-page-number,
        .kpi-page-dots {
            min-width: 34px;
            height: 34px;
            padding: 0 10px;
            font-size: 12px;
        }
    }
</style>
@endpush

@section('content')
@php
    $periodOptions = [
        'last_week' => 'Last Week',
        'current_week' => 'Current Week',
        'last_four_weeks' => 'Last Four Weeks',
        'custom' => 'Custom Date',
    ];

    $activePeriod = $filters['period_type'] ?? 'last_week';
    $perPage = (int) ($filters['per_page'] ?? 10);

    $bestEvidence = function (array $states) {
        $statesLower = array_map(fn ($s) => strtolower((string) $s), $states);
        if (collect($statesLower)->contains(fn ($s) => str_contains($s, 'verified'))) return 'Verified';
        if (collect($statesLower)->contains(fn ($s) => str_contains($s, 'uploaded'))) return 'Uploaded';
        if (collect($statesLower)->contains(fn ($s) => str_contains($s, 'pending'))) return 'Pending';
        return 'N/A';
    };
@endphp

<div class="page-title-bar">
    <div>
        <h1 class="page-title">District Wise KPI Score Report</h1>
        <p class="page-subtitle">
            District-wise performance scores for the selected KPI category and reporting period.
        </p>
    </div>

    <div class="page-title-actions">
        <a href="{{ route('kpi.provincial-data') }}" class="btn-gov btn-gov-outline">
            <i class="bi bi-arrow-left"></i>
            Provincial KPI Wise Data
        </a>
    </div>
</div>

<div class="row g-3 mb-4 kpi-summary" style="display: none;">
    <div class="col-xl-4 col-lg-6">
        <div class="stat-card-ppmf">
            <div class="stat-icon-ppmf success">
                <i class="bi bi-tag"></i>
            </div>
            <div>
                <span>Selected KPI Category</span>
                <div class="kpi-selected-category">
                    {{ $selectedCategory->name ?? 'N/A' }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-lg-6 col-md-6">
        <div class="stat-card-ppmf">
            <div class="stat-icon-ppmf info">
                <i class="bi bi-buildings"></i>
            </div>
            <div>
                <span>Districts</span>
                <div class="kpi-summary-value">{{ number_format($summary['total_districts'] ?? 0) }}</div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="stat-card-ppmf">
            <div class="stat-icon-ppmf primary">
                <i class="bi bi-grid-3x3-gap"></i>
            </div>
            <div>
                <span>Metric Columns</span>
                <div class="kpi-summary-value">{{ number_format($summary['total_metric_cards'] ?? 0) }}</div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="stat-card-ppmf">
            <div class="stat-icon-ppmf warning">
                <i class="bi bi-calendar-week"></i>
            </div>
            <div>
                <span>Active Period</span>
                <div class="kpi-summary-period">{{ $periodOptions[$activePeriod] ?? 'Last Week' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card-ppmf mb-4">
    <div class="card-ppmf-header">
        <div class="card-ppmf-title">
            <i class="bi bi-funnel"></i>
            Filters
        </div>
    </div>

    <div class="card-ppmf-body">
        <form id="districtWiseKpiScoreFilters" method="GET" action="{{ route('kpi.district-wise-kpi-score') }}">
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

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">Report Period</label>
                    <select name="period_type" class="form-select">
                        @foreach ($periodOptions as $value => $label)
                            <option value="{{ $value }}" {{ $activePeriod === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">District</label>
                    <select name="district_id" class="form-select">
                        <option value="">All Districts</option>
                        @foreach ($districts as $district)
                            <option value="{{ $district->id }}" {{ ($filters['district_id'] ?? '') == $district->id ? 'selected' : '' }}>
                                {{ $district->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">Per Page</label>
                    <select name="per_page" class="form-select">
                        @foreach ([10, 20, 25, 50] as $option)
                            <option value="{{ $option }}" {{ $perPage === $option ? 'selected' : '' }}>{{ $option }} districts</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-4 col-lg-6 col-md-12">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Search district or metric">
                </div>

                <div class="col-xl-4 col-lg-6 col-md-12">
                    <label class="form-label">From Date (Custom)</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
                </div>

                <div class="col-xl-4 col-lg-6 col-md-12">
                    <label class="form-label">To Date (Custom)</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
                </div>

                <div class="col-xl-12 d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn-gov btn-gov-primary">
                        <i class="bi bi-search"></i>
                        Apply Filters
                    </button>

                    <a href="{{ route('kpi.district-wise-kpi-score') }}" class="btn-gov btn-gov-outline">
                        <i class="bi bi-arrow-clockwise"></i>
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="districtWiseKpiScoreResults">
@if(($metricCards ?? collect())->count())
    @php
        $metricCount = ($metricCards ?? collect())->count();
        $metricCols = match (true) {
            $metricCount <= 1 => 1,
            $metricCount === 2 => 2,
            default => 3,
        };
    @endphp
    <div class="card-ppmf mb-4">
        <div class="card-ppmf-header">
            <div class="card-ppmf-title">
                <i class="bi bi-grid-3x3-gap"></i>
                KPI Metric Cards
            </div>
        </div>
        <div class="card-ppmf-body">
            <div class="kpi-metric-grid cols-{{ $metricCols }}">
                @foreach ($metricCards as $metric)
                    <div class="kpi-metric-card">
                        <h3 class="kpi-metric-value">{{ $metric->formatted_value }}</h3>
                        <span class="kpi-metric-unit">{{ $metric->unit_label }}</span>

                        <div class="kpi-metric-title">{{ $metric->metric_title }}</div>

                        @if ($metric->source)
                            <div class="mt-2 small text-muted fw-bold">
                                <i class="bi bi-person-badge"></i> {{ $metric->source }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif

<div class="card-ppmf">
    <div class="card-ppmf-header">
        <div>
            <div class="card-ppmf-title">
                <i class="bi bi-table"></i>
                District-wise KPI Score Table
            </div>
            <p class="card-subtitle mb-0">
                One row per district with dynamic metric columns for selected KPI category.
            </p>
        </div>
    </div>

    <div class="card-ppmf-body p-0">
        <div class="table-responsive">
            <table class="table-ppmf kpi-score-table">
                <thead>
                    <tr>
                        <th class="sticky-sr col-sr">Sr.</th>
                        <th class="sticky-district col-district">District</th>
                        @foreach ($metricTitles as $title)
                            <th class="metric-cell">{{ $title }}</th>
                        @endforeach
                        <th class="col-evidence">Evidence</th>
                    </tr>
                </thead>

                <tbody>
                    @php
                        $pageTotals = [];
                        foreach ($metricTitles as $t) {
                            $pageTotals[$t] = ['value' => 0.0, 'score_sum' => 0.0, 'score_count' => 0];
                        }
                    @endphp
                    @forelse ($reportData as $index => $row)
                        <tr>
                            <td class="sticky-sr">{{ method_exists($reportData, 'firstItem') ? $reportData->firstItem() + $index : $index + 1 }}</td>

                            <td class="sticky-district district-cell">{{ $row['district_name'] }}</td>

                            @foreach ($metricTitles as $title)
                                @php
                                    $cell = $row['metrics'][$title] ?? null;
                                    if (! empty($cell) && $cell['value'] !== null) {
                                        $pageTotals[$title]['value'] += (float) $cell['value'];
                                    }
                                    if (! empty($cell) && $cell['score'] !== null) {
                                        $pageTotals[$title]['score_sum'] += (float) $cell['score'];
                                        $pageTotals[$title]['score_count']++;
                                    }
                                @endphp
                                <td class="metric-cell">
                                    <div class="metric-score-label">Performance Score :</div>
                                    <div class="metric-score-value">
                                        @if(!empty($cell) && $cell['score'] !== null)
                                            {{ number_format((float) $cell['score'], 2) }}
                                        @else
                                            -
                                        @endif
                                    </div>
                                    <div class="metric-value-text">{{ $cell['formatted'] ?? '-' }}</div>
                                </td>
                            @endforeach

                            <td>
                                <a href="javascript:void(0)" class="btn btn-sm btn-outline-success fw-bold" title="Evidence (demo)">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 3 + count($metricTitles) }}" class="text-center py-5">
                                <div class="manual-box-ppmf">
                                    <i class="bi bi-inbox"></i>
                                    <h5>No District KPI Score Data</h5>
                                    <p>No district-wise KPI metric values found for selected filters.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse

                    @if(($reportData ?? collect())->count())
                        <tr class="fw-bold">
                            <td class="sticky-sr"></td>
                            <td class="sticky-district">Total</td>
                            @foreach ($metricTitles as $title)
                                @php
                                    $t = $pageTotals[$title];
                                    $avgScore = $t['score_count'] ? ($t['score_sum'] / $t['score_count']) : null;
                                @endphp
                                <td class="metric-cell">
                                    <div class="metric-score-label">Performance Score :</div>
                                    <div class="metric-score-value">{{ $avgScore !== null ? number_format((float) $avgScore, 2) : '-' }}</div>
                                    <div class="metric-value-text">{{ number_format((float) $t['value'], 2) }}</div>
                                </td>
                            @endforeach
                            <td></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    @if (method_exists($reportData, 'lastPage') && $reportData->lastPage() > 1)
        @php
            $reportData->appends(request()->query());

            $currentPage = $reportData->currentPage();
            $lastPage = $reportData->lastPage();
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

        <div class="kpi-pagination-bar">
            <div class="kpi-pagination-summary-group">
                <div class="kpi-pagination-summary">
                    Showing {{ number_format($reportData->firstItem() ?? 0) }} to {{ number_format($reportData->lastItem() ?? 0) }}
                    of {{ number_format($reportData->total() ?? 0) }} districts
                </div>

                <div class="kpi-pagination-controls">
                    <span class="kpi-per-page-label">Per page</span>
                    <form method="GET" action="{{ route('kpi.district-wise-kpi-score') }}">
                        <input type="hidden" name="kpi_category_id" value="{{ $filters['kpi_category_id'] ?? '' }}">
                        <input type="hidden" name="period_type" value="{{ $filters['period_type'] ?? 'last_week' }}">
                        <input type="hidden" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                        <input type="hidden" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
                        <input type="hidden" name="district_id" value="{{ $filters['district_id'] ?? '' }}">
                        <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">
                        <select name="per_page" class="kpi-per-page-select" onchange="this.form.submit()">
                            @foreach ([10, 20, 25, 50] as $option)
                                <option value="{{ $option }}" {{ $currentPerPage === $option ? 'selected' : '' }}>
                                    {{ $option }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>

            <nav class="kpi-pagination-nav" aria-label="District KPI score pagination">
                <a
                    href="{{ $reportData->previousPageUrl() ?: 'javascript:void(0)' }}"
                    class="kpi-page-link {{ $reportData->onFirstPage() ? 'disabled' : '' }}"
                >
                    <i class="bi bi-chevron-left"></i>
                    Previous
                </a>

                @if ($startPage > 1)
                    <a href="{{ $reportData->url(1) }}" class="kpi-page-number">1</a>
                    @if ($startPage > 2)
                        <span class="kpi-page-dots">...</span>
                    @endif
                @endif

                @for ($page = $startPage; $page <= $endPage; $page++)
                    <a
                        href="{{ $reportData->url($page) }}"
                        class="kpi-page-number {{ $page == $currentPage ? 'active' : '' }}"
                    >
                        {{ $page }}
                    </a>
                @endfor

                @if ($endPage < $lastPage)
                    @if ($endPage < $lastPage - 1)
                        <span class="kpi-page-dots">...</span>
                    @endif
                    <a href="{{ $reportData->url($lastPage) }}" class="kpi-page-number">{{ $lastPage }}</a>
                @endif

                <a
                    href="{{ $reportData->nextPageUrl() ?: 'javascript:void(0)' }}"
                    class="kpi-page-link {{ $reportData->hasMorePages() ? '' : 'disabled' }}"
                >
                    Next
                    <i class="bi bi-chevron-right"></i>
                </a>
            </nav>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    (function () {
        if (!window.jQuery) return;

        let searchTimer = null;

        function setLoading(isLoading) {
            const $c = jQuery('#districtWiseKpiScoreResults');
            if (!$c.length) return;
            $c.css('opacity', isLoading ? 0.6 : 1);
        }

        function updateQueryString(serialized) {
            const url = window.location.pathname + (serialized ? ('?' + serialized) : '');
            window.history.pushState({}, '', url);
        }

        function loadDistrictWiseResults(urlOverride) {
            const $form = jQuery('#districtWiseKpiScoreFilters');
            const url = urlOverride || $form.attr('action');
            const data = $form.serialize();

            setLoading(true);

            jQuery.get(url, data)
                .done(function (html) {
                    const $doc = jQuery('<div>').html(html);
                    const $newResults = $doc.find('#districtWiseKpiScoreResults');
                    if ($newResults.length) {
                        jQuery('#districtWiseKpiScoreResults').html($newResults.html());
                        updateQueryString(data);
                    }
                })
                .fail(function () {
                    jQuery('#districtWiseKpiScoreResults').html('<div class="p-4 text-danger fw-bold">Failed to load data. Please try again.</div>');
                })
                .always(function () {
                    setLoading(false);
                });
        }

        jQuery('#districtWiseKpiScoreFilters').on('change', 'select, input[type=\"date\"]', function () {
            loadDistrictWiseResults();
        });

        jQuery('#districtWiseKpiScoreFilters').on('keyup', 'input[name=\"search\"]', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                loadDistrictWiseResults();
            }, 400);
        });

        jQuery('#districtWiseKpiScoreFilters').on('submit', function (e) {
            e.preventDefault();
            loadDistrictWiseResults();
        });

        jQuery(document).on('click', '#districtWiseKpiScoreResults .kpi-pagination-nav a, #districtWiseKpiScoreResults .pagination a', function (e) {
            const href = jQuery(this).attr('href');
            if (!href || href === 'javascript:void(0)') return;
            e.preventDefault();
            loadDistrictWiseResults(href);
        });

        jQuery(document).on('change', '#districtWiseKpiScoreResults .kpi-per-page-select', function (e) {
            e.preventDefault();
            const $form = jQuery('#districtWiseKpiScoreFilters');
            $form.find('select[name=\"per_page\"]').val(jQuery(this).val());
            loadDistrictWiseResults();
        });
    })();
</script>
@endpush

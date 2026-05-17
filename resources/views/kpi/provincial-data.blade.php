@extends('layouts.app')

@section('title', 'Provincial KPI Wise Data')

@push('styles')
<style>
    .prov-kpi-hero {
        background: linear-gradient(135deg, #0f5132 0%, #198754 52%, #d6a84f 100%);
        border-radius: 22px;
        padding: 22px;
        color: #fff;
        box-shadow: 0 18px 45px rgba(15, 81, 50, 0.18);
        margin-bottom: 20px;
    }

    .prov-kpi-hero h1 {
        margin: 0;
        font-size: 24px;
        font-weight: 900;
        letter-spacing: -0.03em;
    }

    .prov-kpi-hero p {
        margin: 7px 0 0;
        max-width: 760px;
        color: rgba(255, 255, 255, 0.86);
        font-size: 13px;
        font-weight: 600;
    }

    .prov-kpi-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .prov-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 18px;
    }

    .prov-summary-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 13px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
    }

    .prov-summary-icon {
        width: 46px;
        height: 46px;
        border-radius: 15px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #e9f7ef;
        color: #146c43;
        font-size: 21px;
        flex-shrink: 0;
    }

    .prov-summary-card span {
        display: block;
        color: #64748b;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .prov-summary-card strong {
        display: block;
        color: #0f172a;
        font-size: 22px;
        font-weight: 900;
        line-height: 1.1;
        margin-top: 3px;
    }

    .prov-filter-card,
    .prov-category-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }

    .prov-card-header {
        padding: 16px 18px;
        background: linear-gradient(180deg, #f8fafc, #ffffff);
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }

    .prov-card-title {
        margin: 0;
        color: #14532d;
        font-size: 16px;
        font-weight: 900;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .prov-card-subtitle {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
    }

    .prov-filter-body {
        padding: 18px;
    }

    .prov-filter-body .form-label {
        font-size: 12px;
        font-weight: 800;
        color: #334155;
        margin-bottom: 6px;
    }

    .prov-filter-body .form-control,
    .prov-filter-body .form-select {
        border-radius: 12px;
        border-color: #cbd5e1;
        min-height: 42px;
        font-size: 13px;
        font-weight: 600;
    }

    .prov-category-card {
        margin-bottom: 18px;
    }

    .prov-category-top {
        padding: 16px 18px;
        background: linear-gradient(135deg, #14532d, #198754);
        color: #fff;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 14px;
    }

    .prov-category-name {
        margin: 0;
        font-size: 18px;
        font-weight: 900;
        letter-spacing: -0.02em;
        text-transform: uppercase;
    }

    .prov-category-meta {
        margin-top: 5px;
        color: rgba(255, 255, 255, 0.82);
        font-size: 12px;
        font-weight: 600;
    }

    .prov-category-count {
        background: rgba(255,255,255,0.16);
        border: 1px solid rgba(255,255,255,0.25);
        border-radius: 999px;
        padding: 7px 12px;
        font-size: 12px;
        font-weight: 900;
        white-space: nowrap;
    }

    .prov-metric-grid {
        padding: 18px;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
    }

    .prov-metric-card {
        position: relative;
        min-height: 176px;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: 16px;
        background: linear-gradient(180deg, #ffffff, #f8fafc);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        overflow: hidden;
    }

    .prov-metric-card::before {
        content: '';
        position: absolute;
        inset: 0 auto 0 0;
        width: 5px;
        background: linear-gradient(180deg, #198754, #d6a84f);
    }

    .prov-metric-value {
        margin: 0;
        color: #0f172a;
        font-size: 34px;
        line-height: 1;
        font-weight: 950;
        letter-spacing: -0.05em;
    }

    .prov-metric-unit {
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

    .prov-metric-title {
        margin: 12px 0 6px;
        color: #14532d;
        font-size: 14px;
        font-weight: 900;
    }

    .prov-metric-description {
        margin: 0;
        color: #475569;
        font-size: 12px;
        line-height: 1.55;
        font-weight: 600;
    }

    .prov-metric-source {
        margin-top: 12px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #64748b;
        font-size: 11px;
        font-weight: 800;
    }

    .prov-empty-state {
        background: #fff;
        border: 1px dashed #cbd5e1;
        border-radius: 18px;
        padding: 36px 18px;
        text-align: center;
        color: #64748b;
        font-weight: 700;
    }

    .prov-pagination-footer {
        margin-top: 18px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: 14px 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05);
    }

    .prov-pagination-footer .pagination {
        gap: 6px;
        margin: 0;
        flex-wrap: wrap;
    }

    .prov-pagination-footer .page-link {
        border-radius: 10px !important;
        min-width: 38px;
        min-height: 36px;
        color: #166534;
        font-size: 13px;
        font-weight: 800;
        border-color: #cbd5e1;
        box-shadow: none;
    }

    .prov-pagination-footer .page-item.active .page-link {
        background: #198754;
        border-color: #198754;
        color: #fff;
    }

    .prov-pagination-text {
        color: #64748b;
        font-size: 12px;
        font-weight: 800;
    }

    @media (max-width: 1199px) {
        .prov-summary-grid,
        .prov-metric-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767px) {
        .prov-summary-grid,
        .prov-metric-grid {
            grid-template-columns: 1fr;
        }

        .prov-kpi-hero,
        .prov-card-header,
        .prov-category-top,
        .prov-pagination-footer {
            align-items: flex-start;
            flex-direction: column;
        }

        .prov-kpi-actions {
            justify-content: flex-start;
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
@endphp

<div class="prov-kpi-hero">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div>
            <h1>Provincial KPI Wise Data</h1>
            <p>
                Old PPMF-style category-wise management metrics shown as clean KPI cards for senior review.
                This report focuses on actions, visits, inspections, fines, meetings and field outcomes instead of workflow statuses.
            </p>
        </div>

        <div class="prov-kpi-actions">
            @if (Route::has('kpi.reporting-status'))
                <a href="{{ route('kpi.reporting-status') }}" class="btn btn-light btn-sm fw-bold">
                    <i class="bi bi-list-check"></i> Reporting Status
                </a>
            @endif

            @if (Route::has('kpi.graphical-report'))
                <a href="{{ route('kpi.graphical-report') }}" class="btn btn-warning btn-sm fw-bold text-dark">
                    <i class="bi bi-bar-chart-line"></i> Graphical Report
                </a>
            @endif
        </div>
    </div>
</div>

<div class="prov-summary-grid">
    <div class="prov-summary-card">
        <div class="prov-summary-icon"><i class="bi bi-grid-3x3-gap"></i></div>
        <div>
            <span>Total Metric Cards</span>
            <strong>{{ number_format($summary['total_metrics'] ?? 0) }}</strong>
        </div>
    </div>

    <div class="prov-summary-card">
        <div class="prov-summary-icon"><i class="bi bi-diagram-3"></i></div>
        <div>
            <span>KPI Categories</span>
            <strong>{{ number_format($summary['total_categories'] ?? 0) }}</strong>
        </div>
    </div>

    <div class="prov-summary-card">
        <div class="prov-summary-icon"><i class="bi bi-calculator"></i></div>
        <div>
            <span>Total Reported Value</span>
            <strong>{{ number_format((float) ($summary['total_value'] ?? 0)) }}</strong>
        </div>
    </div>

    <div class="prov-summary-card">
        <div class="prov-summary-icon"><i class="bi bi-calendar-week"></i></div>
        <div>
            <span>Active Period</span>
            <strong style="font-size:18px;">{{ $periodOptions[$summary['active_period'] ?? $activePeriod] ?? 'Last Week' }}</strong>
        </div>
    </div>
</div>

<div class="prov-filter-card mb-4">
    <div class="prov-card-header">
        <div>
            <h5 class="prov-card-title"><i class="bi bi-funnel"></i> Filters</h5>
            <p class="prov-card-subtitle">Filter category-wise provincial KPI metric cards.</p>
        </div>
    </div>

    <div class="prov-filter-body">
        <form method="GET" action="{{ route('kpi.provincial-data') }}">
            <div class="row g-3 align-items-end">
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">Report Period</label>
                    <select name="period_type" class="form-select">
                        @foreach ($periodOptions as $value => $label)
                            <option value="{{ $value }}" {{ $activePeriod === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">KPI Category</label>
                    <select name="kpi_category_id" class="form-select">
                        <option value="">All KPI Categories</option>
                        @foreach ($kpiCategories as $category)
                            <option value="{{ $category->id }}" {{ ($filters['kpi_category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
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

                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label">Per Page</label>
                    <select name="per_page" class="form-select">
                        @foreach ([10, 20, 25, 50] as $option)
                            <option value="{{ $option }}" {{ $perPage === $option ? 'selected' : '' }}>{{ $option }} categories</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-4 col-lg-6 col-md-12">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Search category, metric, source or description">
                </div>

                <div class="col-xl-4 col-lg-6 col-md-12 d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn-gov btn-gov-primary">
                        <i class="bi bi-search"></i> Apply Filters
                    </button>

                    <a href="{{ route('kpi.provincial-data') }}" class="btn-gov btn-gov-outline">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

@if ($reportData->count())
    @foreach ($reportData as $category)
        @php
            $metrics = $category->provincialMetrics ?? collect();
        @endphp

        <div class="prov-category-card">
            <div class="prov-category-top">
                <div>
                    <h2 class="prov-category-name">{{ $category->name }}</h2>
                    <div class="prov-category-meta">
                        {{ $category->description ?: 'Category-wise provincial KPI metrics' }}
                    </div>
                </div>

                <div class="prov-category-count">
                    {{ $metrics->count() }} metric card{{ $metrics->count() === 1 ? '' : 's' }}
                </div>
            </div>

            <div class="prov-metric-grid">
                @forelse ($metrics as $metric)
                    <div class="prov-metric-card">
                        <h3 class="prov-metric-value">{{ $metric->formatted_value }}</h3>
                        <span class="prov-metric-unit">{{ $metric->unit_label }}</span>

                        <h4 class="prov-metric-title">{{ $metric->metric_title }}</h4>

                        @if ($metric->metric_description)
                            <p class="prov-metric-description">{{ $metric->metric_description }}</p>
                        @endif

                        @if ($metric->source)
                            <div class="prov-metric-source">
                                <i class="bi bi-person-badge"></i>
                                Source: {{ $metric->source }}
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="prov-empty-state">
                        No metric cards are available under this category for the selected filters.
                    </div>
                @endforelse
            </div>
        </div>
    @endforeach

    <div class="prov-pagination-footer">
        <div class="prov-pagination-text">
            Showing {{ $reportData->firstItem() }} to {{ $reportData->lastItem() }} of {{ $reportData->total() }} KPI categories
        </div>

        <div>
            {{ $reportData->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    </div>
@else
    <div class="prov-empty-state">
        <i class="bi bi-inbox fs-1 d-block mb-2 text-success"></i>
        No provincial KPI metric data found for selected filters.
    </div>
@endif

@endsection

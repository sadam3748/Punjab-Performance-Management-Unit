@extends('layouts.app')

@section('title', 'KPI Graphical Report')

@section('content')

<div class="page-title-bar">
    <div>
        <h1 class="page-title">KPI Graphical Report</h1>
        <p class="page-subtitle">
            Visual analysis of inspections, KPI categories, district performance and reporting trends.
        </p>
    </div>

    <div class="page-title-actions">
        <a href="{{ route('kpi.reporting-status') }}" class="btn-gov btn-gov-outline">
            <i class="bi bi-list-check"></i>
            Reporting Status
        </a>

        <a href="{{ route('kpi.provincial-data') }}" class="btn-gov btn-gov-primary">
            <i class="bi bi-table"></i>
            Provincial Data
        </a>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="summary-card-ppmf">
            <div class="summary-card-icon bg-soft-primary">
                <i class="bi bi-clipboard-data"></i>
            </div>
            <div>
                <span>Total Inspections</span>
                <strong>{{ number_format($summary['total_inspections'] ?? 0) }}</strong>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="summary-card-ppmf">
            <div class="summary-card-icon bg-soft-success">
                <i class="bi bi-check-circle"></i>
            </div>
            <div>
                <span>Approved</span>
                <strong>{{ number_format($summary['approved'] ?? $summary['approved_inspections'] ?? 0) }}</strong>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="summary-card-ppmf">
            <div class="summary-card-icon bg-soft-warning">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div>
                <span>Submitted</span>
                <strong>{{ number_format($summary['submitted'] ?? $summary['submitted_inspections'] ?? 0) }}</strong>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="summary-card-ppmf">
            <div class="summary-card-icon bg-soft-info">
                <i class="bi bi-percent"></i>
            </div>
            <div>
                <span>Approval Rate</span>
                <strong>{{ $summary['approval_rate'] ?? $summary['score_percentage'] ?? 0 }}%</strong>
            </div>
        </div>
    </div>

</div>

{{-- Filters --}}
<div class="card-ppmf mb-4">
    <div class="card-ppmf-header">
        <div class="card-ppmf-title">
            <i class="bi bi-funnel"></i>
            Filters
        </div>
    </div>

    <div class="card-ppmf-body">
        <form method="GET" action="{{ route('kpi.graphical-report') }}">
            <div class="row g-3 align-items-end">

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">District</label>
                    <select name="district_id" class="form-select">
                        <option value="">All Districts</option>
                        @foreach ($districts as $district)
                            <option value="{{ $district->id }}"
                                {{ ($filters['district_id'] ?? '') == $district->id ? 'selected' : '' }}>
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
                            <option value="{{ $tehsil->id }}"
                                {{ ($filters['tehsil_id'] ?? '') == $tehsil->id ? 'selected' : '' }}>
                                {{ $tehsil->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">KPI Category</label>
                    <select name="kpi_category_id" class="form-select">
                        <option value="">All KPI Categories</option>
                        @foreach ($kpiCategories as $category)
                            <option value="{{ $category->id }}"
                                {{ ($filters['kpi_category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">Period</label>
                    <select name="period" class="form-select">
                        <option value="">All Time</option>
                        <option value="today" {{ ($filters['period'] ?? '') === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="week" {{ ($filters['period'] ?? '') === 'week' ? 'selected' : '' }}>This Week</option>
                        <option value="month" {{ ($filters['period'] ?? '') === 'month' ? 'selected' : '' }}>This Month</option>
                        <option value="year" {{ ($filters['period'] ?? '') === 'year' ? 'selected' : '' }}>This Year</option>
                    </select>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">From Date</label>
                    <input
                        type="date"
                        name="date_from"
                        value="{{ $filters['date_from'] ?? '' }}"
                        class="form-control"
                    >
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">To Date</label>
                    <input
                        type="date"
                        name="date_to"
                        value="{{ $filters['date_to'] ?? '' }}"
                        class="form-control"
                    >
                </div>

                <div class="col-xl-6 col-lg-4 col-md-12">
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="submit" class="btn-gov btn-gov-primary">
                            <i class="bi bi-funnel"></i>
                            Apply Filter
                        </button>

                        <a href="{{ route('kpi.graphical-report') }}" class="btn-gov btn-gov-outline">
                            <i class="bi bi-arrow-clockwise"></i>
                            Reset
                        </a>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

{{-- Charts Row 1 --}}
<div class="row g-4 mb-4">

    <div class="col-xl-4 col-lg-6">
        <div class="card-ppmf h-100">
            <div class="card-ppmf-header">
                <div>
                    <div class="card-ppmf-title">
                        <i class="bi bi-pie-chart"></i>
                        Status Wise Report
                    </div>
                    <p class="card-subtitle mb-0">Inspection status distribution</p>
                </div>
            </div>

            <div class="card-ppmf-body">
                <div class="chart-box-ppmf">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-lg-6">
        <div class="card-ppmf h-100">
            <div class="card-ppmf-header">
                <div>
                    <div class="card-ppmf-title">
                        <i class="bi bi-bar-chart"></i>
                        KPI Category Wise
                    </div>
                    <p class="card-subtitle mb-0">Top KPI categories by total inspections</p>
                </div>
            </div>

            <div class="card-ppmf-body">
                <div class="chart-box-ppmf">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-lg-12">
        <div class="card-ppmf h-100">
            <div class="card-ppmf-header">
                <div>
                    <div class="card-ppmf-title">
                        <i class="bi bi-building"></i>
                        District Wise Report
                    </div>
                    <p class="card-subtitle mb-0">Top districts by inspection count</p>
                </div>
            </div>

            <div class="card-ppmf-body">
                <div class="chart-box-ppmf">
                    <canvas id="districtChart"></canvas>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- Monthly Trend --}}
<div class="card-ppmf mb-4">
    <div class="card-ppmf-header">
        <div>
            <div class="card-ppmf-title">
                <i class="bi bi-graph-up-arrow"></i>
                Monthly Inspection Trend
            </div>
            <p class="card-subtitle mb-0">
                Month-wise inspection reporting trend
            </p>
        </div>
    </div>

    <div class="card-ppmf-body">
        <div class="chart-box-ppmf chart-box-wide">
            <canvas id="monthlyTrendChart"></canvas>
        </div>
    </div>
</div>

{{-- Data Table Summary --}}
<div class="card-ppmf">
    <div class="card-ppmf-header">
        <div>
            <div class="card-ppmf-title">
                <i class="bi bi-table"></i>
                Category Data Summary
            </div>
            <p class="card-subtitle mb-0">
                KPI category-wise inspection count used in chart.
            </p>
        </div>
    </div>

    <div class="card-ppmf-body p-0">
        <div class="table-responsive">
            <table class="table-ppmf">
                <thead>
                    <tr>
                        <th style="width: 70px;">Sr.</th>
                        <th>KPI Category</th>
                        <th>Total Inspections</th>
                        <th>Approved</th>
                        <th>Rejected</th>
                        <th>Score / Approval %</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($categoryChart as $index => $row)
                        @php
                            $total = $row->total ?? $row->total_inspections ?? 0;
                            $approved = $row->approved_count ?? $row->approved ?? 0;
                            $rejected = $row->rejected_count ?? $row->rejected ?? 0;
                            $score = $row->score_percentage ?? ($total > 0 ? round(($approved / $total) * 100, 2) : 0);
                        @endphp

                        <tr>
                            <td>{{ $index + 1 }}</td>

                            <td>
                                <div class="fw-bold">
                                    {{ $row->kpiCategory->name ?? $row->kpi_category_name ?? 'N/A' }}
                                </div>
                            </td>

                            <td>
                                <strong>{{ number_format($total) }}</strong>
                            </td>

                            <td>
                                <span class="text-success fw-bold">{{ number_format($approved) }}</span>
                            </td>

                            <td>
                                <span class="text-danger fw-bold">{{ number_format($rejected) }}</span>
                            </td>

                            <td>
                                <span class="badge-ppmf
                                    @if($score >= 80) achieved
                                    @elseif($score >= 50) pending
                                    @else critical
                                    @endif
                                ">
                                    {{ $score }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="manual-box-ppmf">
                                    <i class="bi bi-bar-chart"></i>
                                    <h5>No Graphical Data Found</h5>
                                    <p>No KPI chart data is available for selected filters.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .summary-card-ppmf {
        background: #fff;
        border: 1px solid var(--border-light);
        border-radius: var(--radius-lg);
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 13px;
        box-shadow: var(--shadow-sm);
        min-height: 96px;
    }

    .summary-card-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: grid;
        place-items: center;
        font-size: 19px;
        flex-shrink: 0;
    }

    .summary-card-ppmf span {
        display: block;
        font-size: 12.5px;
        color: var(--text-muted);
        font-weight: 700;
        margin-bottom: 2px;
    }

    .summary-card-ppmf strong {
        display: block;
        font-size: 24px;
        font-weight: 800;
        color: var(--text-primary);
        line-height: 1.2;
    }

    .bg-soft-primary {
        background: rgba(27, 107, 70, 0.12);
        color: var(--gov-green);
    }

    .bg-soft-success {
        background: rgba(34, 197, 94, 0.12);
        color: #15803d;
    }

    .bg-soft-warning {
        background: rgba(245, 158, 11, 0.14);
        color: #b45309;
    }

    .bg-soft-info {
        background: rgba(14, 165, 233, 0.12);
        color: #0369a1;
    }

    .chart-box-ppmf {
        position: relative;
        height: 310px;
        width: 100%;
    }

    .chart-box-wide {
        height: 360px;
    }

    @media (max-width: 768px) {
        .chart-box-ppmf,
        .chart-box-wide {
            height: 300px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    const statusChartData = @json($statusChart ?? []);
    const categoryChartData = @json($categoryChart ?? []);
    const districtChartData = @json($districtChart ?? []);
    const monthlyTrendData = @json($monthlyTrend ?? []);

    function getValue(item, keys, fallback = 0) {
        for (const key of keys) {
            if (item[key] !== undefined && item[key] !== null) {
                return item[key];
            }
        }

        return fallback;
    }

    function makeChart(canvasId, type, labels, values, labelText) {
        const canvas = document.getElementById(canvasId);

        if (!canvas) {
            return;
        }

        new Chart(canvas, {
            type: type,
            data: {
                labels: labels,
                datasets: [{
                    label: labelText,
                    data: values,
                    borderWidth: 2,
                    tension: 0.35,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: type === 'doughnut' || type === 'line'
                    }
                },
                scales: type === 'doughnut' ? {} : {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }

    makeChart(
        'statusChart',
        'doughnut',
        statusChartData.map(item => String(getValue(item, ['status'], 'N/A')).replaceAll('_', ' ').toUpperCase()),
        statusChartData.map(item => Number(getValue(item, ['total', 'count'], 0))),
        'Total'
    );

    makeChart(
        'categoryChart',
        'bar',
        categoryChartData.map(item => item.kpi_category?.name ?? item.kpiCategory?.name ?? item.kpi_category_name ?? 'N/A'),
        categoryChartData.map(item => Number(getValue(item, ['total', 'total_inspections'], 0))),
        'Inspections'
    );

    makeChart(
        'districtChart',
        'bar',
        districtChartData.map(item => item.district?.name ?? item.district_name ?? 'N/A'),
        districtChartData.map(item => Number(getValue(item, ['total', 'total_inspections'], 0))),
        'Inspections'
    );

    makeChart(
        'monthlyTrendChart',
        'line',
        monthlyTrendData.map(item => item.month_name ?? item.month ?? item.label ?? 'N/A'),
        monthlyTrendData.map(item => Number(getValue(item, ['total', 'total_inspections', 'count'], 0))),
        'Monthly Inspections'
    );
</script>
@endpush

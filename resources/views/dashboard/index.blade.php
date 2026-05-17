@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

<div class="page-header">
    <div>
        <h1 class="page-title">Overview Dashboard</h1>
        <p class="page-subtitle">
            Punjab Performance Management Framework monitoring summary
        </p>
    </div>

    <div class="page-actions">
        <a href="{{ route('inspections.list') }}" class="btn-gov btn-gov-outline">
            <i class="bi bi-list-check"></i>
            View Inspections
        </a>

        <a href="{{ route('kpi.graphical-report') }}" class="btn-gov btn-gov-primary">
            <i class="bi bi-bar-chart"></i>
            KPI Graphical Report
        </a>
    </div>
</div>

{{-- Filters --}}
<div class="filter-card mb-4">
    <form method="GET" action="{{ route('dashboard') }}">
        <div class="row g-3 align-items-end">

            <div class="col-lg-3 col-md-6">
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

            <div class="col-lg-3 col-md-6">
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

            <div class="col-lg-3 col-md-6">
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

            <div class="col-lg-3 col-md-6">
                <label class="form-label">Period</label>
                <select name="period" class="form-select">
                    <option value="">All Time</option>
                    <option value="today" {{ ($filters['period'] ?? '') === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="week" {{ ($filters['period'] ?? '') === 'week' ? 'selected' : '' }}>This Week</option>
                    <option value="month" {{ ($filters['period'] ?? '') === 'month' ? 'selected' : '' }}>This Month</option>
                    <option value="year" {{ ($filters['period'] ?? '') === 'year' ? 'selected' : '' }}>This Year</option>
                </select>
            </div>

            <div class="col-lg-3 col-md-6">
                <label class="form-label">From Date</label>
                <input
                    type="date"
                    name="date_from"
                    value="{{ $filters['date_from'] ?? '' }}"
                    class="form-control"
                >
            </div>

            <div class="col-lg-3 col-md-6">
                <label class="form-label">To Date</label>
                <input
                    type="date"
                    name="date_to"
                    value="{{ $filters['date_to'] ?? '' }}"
                    class="form-control"
                >
            </div>

            <div class="col-lg-6 col-md-12">
                <div class="d-flex gap-2 justify-content-end">
                    <button type="submit" class="btn-gov btn-gov-primary">
                        <i class="bi bi-funnel"></i>
                        Apply Filter
                    </button>

                    <a href="{{ route('dashboard') }}" class="btn-gov btn-gov-outline">
                        <i class="bi bi-arrow-clockwise"></i>
                        Reset
                    </a>
                </div>
            </div>

        </div>
    </form>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="stat-card">
            <div class="stat-icon bg-soft-primary">
                <i class="bi bi-clipboard-data"></i>
            </div>
            <div>
                <p class="stat-label">Total Inspections</p>
                <h3 class="stat-value">{{ number_format($summary['total_inspections'] ?? 0) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="stat-card">
            <div class="stat-icon bg-soft-success">
                <i class="bi bi-check-circle"></i>
            </div>
            <div>
                <p class="stat-label">Approved</p>
                <h3 class="stat-value">{{ number_format($summary['approved_inspections'] ?? 0) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="stat-card">
            <div class="stat-icon bg-soft-warning">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div>
                <p class="stat-label">Submitted</p>
                <h3 class="stat-value">{{ number_format($summary['submitted_inspections'] ?? 0) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="stat-card">
            <div class="stat-icon bg-soft-danger">
                <i class="bi bi-x-circle"></i>
            </div>
            <div>
                <p class="stat-label">Rejected</p>
                <h3 class="stat-value">{{ number_format($summary['rejected_inspections'] ?? 0) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="stat-card">
            <div class="stat-icon bg-soft-info">
                <i class="bi bi-geo-alt"></i>
            </div>
            <div>
                <p class="stat-label">Geo Taggings</p>
                <h3 class="stat-value">{{ number_format($summary['total_geo_taggings'] ?? 0) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="stat-card">
            <div class="stat-icon bg-soft-success">
                <i class="bi bi-percent"></i>
            </div>
            <div>
                <p class="stat-label">Approval Rate</p>
                <h3 class="stat-value">{{ $summary['approval_rate'] ?? 0 }}%</h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="stat-card">
            <div class="stat-icon bg-soft-primary">
                <i class="bi bi-database"></i>
            </div>
            <div>
                <p class="stat-label">Baseline Records</p>
                <h3 class="stat-value">{{ number_format($baselineSummary['district_baseline_records'] ?? 0) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="stat-card">
            <div class="stat-icon bg-soft-info">
                <i class="bi bi-building"></i>
            </div>
            <div>
                <p class="stat-label">Baseline Assets</p>
                <h3 class="stat-value">{{ number_format($baselineSummary['baseline_assets'] ?? 0) }}</h3>
            </div>
        </div>
    </div>

</div>

{{-- Charts --}}
<div class="row g-4 mb-4">

    <div class="col-xl-4 col-lg-6">
        <div class="content-card h-100">
            <div class="card-header-row">
                <div>
                    <h5 class="card-title">Inspection Status</h5>
                    <p class="card-subtitle">Status-wise inspection breakdown</p>
                </div>
            </div>

            <div class="chart-container">
                <canvas id="inspectionStatusChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-lg-6">
        <div class="content-card h-100">
            <div class="card-header-row">
                <div>
                    <h5 class="card-title">KPI Category Wise</h5>
                    <p class="card-subtitle">Top KPI categories by inspections</p>
                </div>
            </div>

            <div class="chart-container">
                <canvas id="categoryWiseChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-lg-12">
        <div class="content-card h-100">
            <div class="card-header-row">
                <div>
                    <h5 class="card-title">District Wise</h5>
                    <p class="card-subtitle">Top districts by inspection count</p>
                </div>
            </div>

            <div class="chart-container">
                <canvas id="districtWiseChart"></canvas>
            </div>
        </div>
    </div>

</div>

{{-- Geo Tagging and Baseline Summary --}}
<div class="row g-4 mb-4">

    <div class="col-lg-6">
        <div class="content-card h-100">
            <div class="card-header-row">
                <div>
                    <h5 class="card-title">Geo Tagging Summary</h5>
                    <p class="card-subtitle">Verification and submission overview</p>
                </div>
            </div>

            <div class="mini-summary-list">
                <div class="mini-summary-item">
                    <span>Total Geo Taggings</span>
                    <strong>{{ number_format($geoTaggingSummary['total_geo_taggings'] ?? 0) }}</strong>
                </div>

                <div class="mini-summary-item">
                    <span>Verified</span>
                    <strong>{{ number_format($geoTaggingSummary['verified'] ?? 0) }}</strong>
                </div>

                <div class="mini-summary-item">
                    <span>Submitted</span>
                    <strong>{{ number_format($geoTaggingSummary['submitted'] ?? 0) }}</strong>
                </div>

                <div class="mini-summary-item">
                    <span>Rejected</span>
                    <strong>{{ number_format($geoTaggingSummary['rejected'] ?? 0) }}</strong>
                </div>

                <div class="mini-summary-item">
                    <span>Verified Rate</span>
                    <strong>{{ $geoTaggingSummary['verified_rate'] ?? 0 }}%</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="content-card h-100">
            <div class="card-header-row">
                <div>
                    <h5 class="card-title">Baseline Asset Summary</h5>
                    <p class="card-subtitle">Functional and non-functional assets</p>
                </div>
            </div>

            <div class="mini-summary-list">
                <div class="mini-summary-item">
                    <span>District Baseline Records</span>
                    <strong>{{ number_format($baselineSummary['district_baseline_records'] ?? 0) }}</strong>
                </div>

                <div class="mini-summary-item">
                    <span>Total Baseline Assets</span>
                    <strong>{{ number_format($baselineSummary['baseline_assets'] ?? 0) }}</strong>
                </div>

                <div class="mini-summary-item">
                    <span>Functional Assets</span>
                    <strong>{{ number_format($baselineSummary['functional_assets'] ?? 0) }}</strong>
                </div>

                <div class="mini-summary-item">
                    <span>Non-Functional Assets</span>
                    <strong>{{ number_format($baselineSummary['non_functional_assets'] ?? 0) }}</strong>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- Recent Inspections --}}
<div class="content-card">
    <div class="card-header-row">
        <div>
            <h5 class="card-title">Recent Inspections</h5>
            <p class="card-subtitle">Latest submitted inspection records</p>
        </div>

        <a href="{{ route('inspections.list') }}" class="btn-gov btn-gov-outline btn-gov-sm">
            View All
        </a>
    </div>

    <div class="table-responsive">
        <table class="table ppmf-table align-middle">
            <thead>
                <tr>
                    <th>Sr.</th>
                    <th>Inspection Type</th>
                    <th>Title</th>
                    <th>District</th>
                    <th>Tehsil</th>
                    <th>Performed By</th>
                    <th>Date & Time</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($recentInspections as $index => $inspection)
                    <tr>
                        <td>{{ $index + 1 }}</td>

                        <td>
                            {{ $inspection->kpiCategory->name ?? 'N/A' }}
                        </td>

                        <td>
                            <div class="fw-semibold">
                                {{ $inspection->main_title ?? 'N/A' }}
                            </div>
                            <small class="text-muted">
                                {{ $inspection->main_address ?? '' }}
                            </small>
                        </td>

                        <td>{{ $inspection->district->name ?? 'N/A' }}</td>

                        <td>{{ $inspection->tehsil->name ?? 'N/A' }}</td>

                        <td>
                            {{ $inspection->performer->username ?? $inspection->performer->name ?? 'N/A' }}
                        </td>

                        <td>
                            @if ($inspection->inspection_datetime)
                                {{ \Carbon\Carbon::parse($inspection->inspection_datetime)->format('d M, Y h:i A') }}
                            @else
                                N/A
                            @endif
                        </td>

                        <td>
                            <span class="status-badge status-{{ $inspection->status }}">
                                {{ ucfirst(str_replace('_', ' ', $inspection->status ?? 'N/A')) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            No inspection records found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const statusChartData = @json($statusChart ?? []);
    const categoryChartData = @json($categoryChart ?? []);
    const districtChartData = @json($districtChart ?? []);

    function createBasicChart(canvasId, labels, data, type = 'bar') {
        const canvas = document.getElementById(canvasId);

        if (!canvas) {
            return;
        }

        new Chart(canvas, {
            type: type,
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total',
                    data: data,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: type === 'doughnut'
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

    createBasicChart(
        'inspectionStatusChart',
        statusChartData.map(item => item.status ? item.status.replace('_', ' ').toUpperCase() : 'N/A'),
        statusChartData.map(item => Number(item.total ?? 0)),
        'doughnut'
    );

    createBasicChart(
        'categoryWiseChart',
        categoryChartData.map(item => item.kpi_category?.name ?? item.kpiCategory?.name ?? 'N/A'),
        categoryChartData.map(item => Number(item.total ?? 0)),
        'bar'
    );

    createBasicChart(
        'districtWiseChart',
        districtChartData.map(item => item.district?.name ?? 'N/A'),
        districtChartData.map(item => Number(item.total ?? 0)),
        'bar'
    );
</script>
@endpush

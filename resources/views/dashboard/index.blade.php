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
                    <h5 class="card-title"><i class="bi bi-pie-chart"></i> Inspection Status</h5>
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
                    <h5 class="card-title"><i class="bi bi-bar-chart"></i> KPI Category Wise</h5>
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
                    <h5 class="card-title"><i class="bi bi-geo-alt"></i> District Wise</h5>
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
                    <h5 class="card-title"><i class="bi bi-geo-alt"></i> Geo Tagging Summary</h5>
                    <p class="card-subtitle">Verification and submission overview</p>
                </div>
            </div>

            <div class="dash-metric-grid">
                <div class="dash-metric-card" style="--accent: var(--gov-green);">
                    <div class="dash-metric-icon"><i class="bi bi-pin-map"></i></div>
                    <div>
                        <span class="dash-metric-label">Total Geo Taggings</span>
                        <span class="dash-metric-value">{{ number_format($geoTaggingSummary['total_geo_taggings'] ?? 0) }}</span>
                        <span class="dash-metric-sub">All submitted coordinates</span>
                    </div>
                </div>

                <div class="dash-metric-card" style="--accent: rgba(37, 99, 235, .95);">
                    <div class="dash-metric-icon"><i class="bi bi-patch-check"></i></div>
                    <div>
                        <span class="dash-metric-label">Verified</span>
                        <span class="dash-metric-value">{{ number_format($geoTaggingSummary['verified'] ?? 0) }}</span>
                        <span class="dash-metric-sub">Approved/verified records</span>
                    </div>
                </div>

                <div class="dash-metric-card" style="--accent: rgba(245, 158, 11, .95);">
                    <div class="dash-metric-icon"><i class="bi bi-inbox-arrow-down"></i></div>
                    <div>
                        <span class="dash-metric-label">Submitted</span>
                        <span class="dash-metric-value">{{ number_format($geoTaggingSummary['submitted'] ?? 0) }}</span>
                        <span class="dash-metric-sub">Awaiting verification</span>
                    </div>
                </div>

                <div class="dash-metric-card" style="--accent: rgba(220, 38, 38, .95);">
                    <div class="dash-metric-icon"><i class="bi bi-x-octagon"></i></div>
                    <div>
                        <span class="dash-metric-label">Rejected</span>
                        <span class="dash-metric-value">{{ number_format($geoTaggingSummary['rejected'] ?? 0) }}</span>
                        <span class="dash-metric-sub">Rejected / invalid</span>
                    </div>
                </div>

                <div class="dash-metric-card" style="--accent: var(--gold); grid-column: 1 / -1;">
                    <div class="dash-metric-icon"><i class="bi bi-percent"></i></div>
                    <div>
                        <span class="dash-metric-label">Verified Rate</span>
                        <span class="dash-metric-value">{{ $geoTaggingSummary['verified_rate'] ?? 0 }}%</span>
                        <span class="dash-metric-sub">Verified ÷ total</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="content-card h-100">
            <div class="card-header-row">
                <div>
                    <h5 class="card-title"><i class="bi bi-database"></i> Baseline Asset Summary</h5>
                    <p class="card-subtitle">Functional and non-functional assets</p>
                </div>
            </div>

            <div class="dash-metric-grid">
                <div class="dash-metric-card" style="--accent: var(--gov-green);">
                    <div class="dash-metric-icon"><i class="bi bi-list-check"></i></div>
                    <div>
                        <span class="dash-metric-label">District Baseline Records</span>
                        <span class="dash-metric-value">{{ number_format($baselineSummary['district_baseline_records'] ?? 0) }}</span>
                        <span class="dash-metric-sub">District profiles</span>
                    </div>
                </div>

                <div class="dash-metric-card" style="--accent: rgba(37, 99, 235, .95);">
                    <div class="dash-metric-icon"><i class="bi bi-boxes"></i></div>
                    <div>
                        <span class="dash-metric-label">Total Baseline Assets</span>
                        <span class="dash-metric-value">{{ number_format($baselineSummary['baseline_assets'] ?? 0) }}</span>
                        <span class="dash-metric-sub">All assets</span>
                    </div>
                </div>

                <div class="dash-metric-card" style="--accent: rgba(22, 163, 74, .95);">
                    <div class="dash-metric-icon"><i class="bi bi-check-circle"></i></div>
                    <div>
                        <span class="dash-metric-label">Functional Assets</span>
                        <span class="dash-metric-value">{{ number_format($baselineSummary['functional_assets'] ?? 0) }}</span>
                        <span class="dash-metric-sub">Functional</span>
                    </div>
                </div>

                <div class="dash-metric-card" style="--accent: rgba(220, 38, 38, .95);">
                    <div class="dash-metric-icon"><i class="bi bi-x-circle"></i></div>
                    <div>
                        <span class="dash-metric-label">Non-Functional Assets</span>
                        <span class="dash-metric-value">{{ number_format($baselineSummary['non_functional_assets'] ?? 0) }}</span>
                        <span class="dash-metric-sub">Non-functional</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- Recent Inspections --}}
<div class="card-ppmf dash-card">
    <div class="card-ppmf-header dash-card-header">
        <div>
            <div class="card-ppmf-title">
                <i class="bi bi-list-check"></i>
                Recent Inspections
            </div>
            <p class="dash-card-subtitle">Latest submitted inspection records</p>
        </div>

        <div class="card-ppmf-actions">
            <a href="{{ route('inspections.list') }}" class="btn-gov btn-gov-outline btn-gov-sm">
                View All
            </a>
        </div>
    </div>

    <div class="card-ppmf-body p-0">
        <div class="table-responsive">
            <table class="table inspection-table inspection-table-compact align-middle mb-0">
            <thead>
                <tr>
                    <th>Sr. No.</th>
                    <th>Inspection Type</th>
                    <th>Primary Detail</th>
                    <th>Secondary Detail / Address</th>
                    <th>District</th>
                    <th>Performed By</th>
                    <th>Date & Time</th>
                    <th>Evidence / Actions</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($recentInspections as $index => $inspection)
                    <tr>
                        <td>
                            {{ $index + 1 }}
                        </td>

                        <td>
                            <div class="inspection-type-cell">
                                <span class="inspection-type-icon">
                                    <i class="bi bi-clipboard-check"></i>
                                </span>
                                <span>{{ $inspection->kpiCategory->name ?? 'N/A' }}</span>
                            </div>
                        </td>

                        <td>
                            <div class="inspection-title">{{ $inspection->main_title ?? 'N/A' }}</div>
                        </td>

                        <td>
                            <div class="inspection-address">{{ $inspection->main_address ?? 'N/A' }}</div>
                        </td>

                        <td>{{ $inspection->district->name ?? 'N/A' }}</td>

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
                            <span class="text-muted fw-semibold">&mdash;</span>
                        </td>

                        <td class="text-center">
                            <a href="{{ route('inspections.show', $inspection->id) }}" class="btn-gov btn-gov-outline btn-gov-sm" target="_blank" rel="noopener">
                                <i class="bi bi-eye"></i> View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            No inspection records found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
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

        const container = canvas.parentElement;
        const hasData = Array.isArray(labels) && labels.length > 0 && Array.isArray(data) && data.some(v => Number(v) > 0);
        if (!hasData) {
            if (container) {
                container.innerHTML = '<div class="chart-empty-state"><i class="bi bi-info-circle"></i><div>No chart data available.</div></div>';
            }
            return;
        }

        const css = getComputedStyle(document.documentElement);
        const GOV = (css.getPropertyValue('--gov-green') || '#006b3f').trim();
        const GOV_DARK = (css.getPropertyValue('--gov-green-dark') || '#064d31').trim();
        const GOLD = (css.getPropertyValue('--gold') || '#d6a21e').trim();

        const baseDataset = {
            label: 'Total',
            data: data,
            borderSkipped: false,
        };

        const dataset = (type === 'doughnut')
            ? {
                ...baseDataset,
                backgroundColor: [
                    GOV,
                    GOLD,
                    'rgba(37, 99, 235, .85)',
                    'rgba(220, 38, 38, .85)',
                    'rgba(245, 158, 11, .88)',
                    'rgba(100, 116, 139, .70)',
                ],
                borderColor: '#ffffff',
                borderWidth: 2,
                hoverOffset: 6,
            }
            : {
                ...baseDataset,
                backgroundColor: 'rgba(0, 107, 63, 0.68)',
                hoverBackgroundColor: 'rgba(0, 107, 63, 0.85)',
                borderWidth: 0,
                borderRadius: 10,
                maxBarThickness: 44,
            };

        new Chart(canvas, {
            type: type,
            data: {
                labels: labels,
                datasets: [dataset]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: type === 'doughnut',
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            boxHeight: 10,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            color: '#334155',
                            font: { size: 11, weight: '700' },
                        }
                    }
                },
                cutout: type === 'doughnut' ? '72%' : undefined,
                scales: type === 'doughnut' ? {} : {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            color: '#475569',
                            font: { size: 11, weight: '700' },
                        }
                    },
                    x: {
                        ticks: { color: '#334155', font: { size: 11, weight: '700' } },
                        grid: { display: false }
                    },
                    grid: { color: 'rgba(148,163,184,.18)' }
                }
            }
        });
    }

    createBasicChart(
        'inspectionStatusChart',
        statusChartData.map(item => {
            const s = item.status ?? item.key ?? item.name ?? '';
            return s ? String(s).replace(/_/g, ' ').toUpperCase() : 'N/A';
        }),
        statusChartData.map(item => Number(item.total ?? 0)),
        'doughnut'
    );

    createBasicChart(
        'categoryWiseChart',
        categoryChartData
            .slice(0, 10)
            .map(item => item.kpi_category?.name ?? item.kpiCategory?.name ?? item.category_name ?? item.name ?? 'N/A'),
        categoryChartData
            .slice(0, 10)
            .map(item => Number(item.total ?? item.count ?? 0)),
        'bar'
    );

    createBasicChart(
        'districtWiseChart',
        districtChartData
            .slice(0, 10)
            .map(item => item.district?.name ?? item.district_name ?? item.name ?? 'N/A'),
        districtChartData
            .slice(0, 10)
            .map(item => Number(item.total ?? item.count ?? 0)),
        'bar'
    );
</script>
@endpush

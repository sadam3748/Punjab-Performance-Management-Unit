@extends('layouts.app')

@section('title', 'Reports')

@section('content')

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Reports</h1>
        <p class="page-subtitle">
            View KPI inspection reports, district performance, division ranking and weekly progress.
        </p>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="stat-card-ppmf">
            <div class="stat-icon-ppmf primary">
                <i class="bi bi-clipboard-data"></i>
            </div>
            <div>
                <span>Total Inspections</span>
                <strong>{{ number_format($summary['total'] ?? 0) }}</strong>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="stat-card-ppmf">
            <div class="stat-icon-ppmf success">
                <i class="bi bi-check-circle"></i>
            </div>
            <div>
                <span>Approved</span>
                <strong>{{ number_format($summary['approved'] ?? 0) }}</strong>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="stat-card-ppmf">
            <div class="stat-icon-ppmf warning">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div>
                <span>Submitted</span>
                <strong>{{ number_format($summary['submitted'] ?? 0) }}</strong>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="stat-card-ppmf">
            <div class="stat-icon-ppmf info">
                <i class="bi bi-percent"></i>
            </div>
            <div>
                <span>Approval Rate</span>
                <strong>{{ $summary['approval_rate'] ?? 0 }}%</strong>
            </div>
        </div>
    </div>

</div>

{{-- Filters --}}
<div class="card-ppmf mb-4">
    <div class="card-ppmf-header">
        <div class="card-ppmf-title">
            <i class="bi bi-funnel"></i>
            Report Filters
        </div>
    </div>

    <div class="card-ppmf-body">
        <form method="GET" action="{{ route('reports.index') }}">
            <div class="row g-3 align-items-end">

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">District</label>
                    <select name="district_id" class="form-select">
                        <option value="">All Districts</option>
                        @foreach($districts as $district)
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
                        @foreach($tehsils as $tehsil)
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
                        @foreach($kpiCategories as $category)
                            <option value="{{ $category->id }}"
                                {{ ($filters['kpi_category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="submitted" {{ ($filters['status'] ?? '') === 'submitted' ? 'selected' : '' }}>Submitted</option>
                        <option value="reviewed" {{ ($filters['status'] ?? '') === 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                        <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">Tier</label>
                    <select name="tier" class="form-select">
                        <option value="">All Tiers</option>
                        <option value="1" {{ ($filters['tier'] ?? '') == '1' ? 'selected' : '' }}>Tier 1</option>
                        <option value="2" {{ ($filters['tier'] ?? '') == '2' ? 'selected' : '' }}>Tier 2</option>
                        <option value="3" {{ ($filters['tier'] ?? '') == '3' ? 'selected' : '' }}>Tier 3</option>
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

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn-gov btn-gov-primary flex-fill">
                            <i class="bi bi-search"></i>
                            Apply
                        </button>

                        <a href="{{ route('reports.index') }}" class="btn-gov btn-gov-outline flex-fill">
                            <i class="bi bi-arrow-clockwise"></i>
                            Reset
                        </a>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

{{-- Report Menu --}}
<div class="row g-4">

    @php
        $reportLinks = [
            [
                'title' => 'Category Wise District Score',
                'desc' => 'District performance grouped by KPI category.',
                'icon' => 'bi bi-grid-3x3-gap',
                'route' => 'reports.category-wise-district-score',
            ],
            [
                'title' => 'District Comparison',
                'desc' => 'Compare districts by inspections, approvals and rejection count.',
                'icon' => 'bi bi-building',
                'route' => 'reports.district-comparison',
            ],
            [
                'title' => 'District Accumulative Report',
                'desc' => 'District-wise accumulated inspection status summary.',
                'icon' => 'bi bi-bar-chart-line',
                'route' => 'reports.district-accumulative',
            ],
            [
                'title' => 'Division Score',
                'desc' => 'Division-wise inspection and approval performance.',
                'icon' => 'bi bi-diagram-3',
                'route' => 'reports.division-score',
            ],
            [
                'title' => 'District Wise KPI Score',
                'desc' => 'Score percentage by district and KPI category.',
                'icon' => 'bi bi-speedometer2',
                'route' => 'reports.district-wise-kpi-score',
            ],
            [
                'title' => 'Division KPI Ranking',
                'desc' => 'Ranking of divisions by KPI category performance.',
                'icon' => 'bi bi-trophy',
                'route' => 'reports.division-kpi-ranking',
            ],
            [
                'title' => 'District Weekly KPI Inspection',
                'desc' => 'Weekly KPI inspection count district-wise.',
                'icon' => 'bi bi-calendar-week',
                'route' => 'reports.district-weekly-kpi-inspection',
            ],
            [
                'title' => 'District Week Rank Changelog',
                'desc' => 'Weekly ranking movement based on approved inspections.',
                'icon' => 'bi bi-arrow-left-right',
                'route' => 'reports.district-week-rank-changelog',
            ],
            [
                'title' => 'District SFN Victim Tier',
                'desc' => 'District tier-wise report summary.',
                'icon' => 'bi bi-layers',
                'route' => 'reports.district-sfn-victim-tier',
            ],
            [
                'title' => 'District SFN Comparison',
                'desc' => 'SFN comparison report using district performance data.',
                'icon' => 'bi bi-columns-gap',
                'route' => 'reports.district-sfn-comparison',
            ],
        ];
    @endphp

    @foreach($reportLinks as $report)
        <div class="col-xl-4 col-lg-6 col-md-6">
            <a
                href="{{ route($report['route'], request()->query()) }}"
                class="report-card-ppmf"
            >
                <div class="report-icon-ppmf">
                    <i class="{{ $report['icon'] }}"></i>
                </div>

                <div>
                    <h5>{{ $report['title'] }}</h5>
                    <p>{{ $report['desc'] }}</p>
                </div>

                <span class="report-arrow">
                    <i class="bi bi-arrow-right"></i>
                </span>
            </a>
        </div>
    @endforeach

</div>

@endsection

@push('styles')
<style>
    .report-card-ppmf {
        min-height: 145px;
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 18px;
        border-radius: var(--radius-lg);
        background: #fff;
        border: 1px solid var(--border-light);
        box-shadow: var(--shadow-sm);
        text-decoration: none;
        color: inherit;
        position: relative;
        transition: 0.2s ease;
    }

    .report-card-ppmf:hover {
        transform: translateY(-2px);
        border-color: rgba(27, 107, 70, 0.35);
        box-shadow: var(--shadow-md);
    }

    .report-icon-ppmf {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        background: rgba(27, 107, 70, 0.10);
        color: var(--gov-green);
        display: grid;
        place-items: center;
        font-size: 21px;
        flex-shrink: 0;
    }

    .report-card-ppmf h5 {
        margin: 0 0 6px;
        font-size: 15px;
        font-weight: 800;
        color: var(--text-primary);
    }

    .report-card-ppmf p {
        margin: 0;
        font-size: 12.5px;
        color: var(--text-secondary);
        line-height: 1.5;
        padding-right: 22px;
    }

    .report-arrow {
        position: absolute;
        right: 16px;
        bottom: 14px;
        color: var(--gov-green);
        font-size: 17px;
    }
</style>
@endpush

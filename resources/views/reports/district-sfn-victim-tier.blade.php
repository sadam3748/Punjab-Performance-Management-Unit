@extends('layouts.app')

@section('title', 'District SFN Victim Tier')

@section('content')

<div class="page-title-bar">
    <div>
        <h1 class="page-title">District SFN Victim Tier</h1>
        <p class="page-subtitle">
            Tier-wise district performance report based on KPI inspections, approvals and score percentage.
        </p>
    </div>

    <div class="page-title-actions">
        <a href="{{ route('reports.index') }}" class="btn-gov btn-gov-outline">
            <i class="bi bi-arrow-left"></i>
            Back to Reports
        </a>

        <a href="{{ route('reports.district-sfn-comparison') }}" class="btn-gov btn-gov-primary">
            <i class="bi bi-columns-gap"></i>
            SFN Comparison
        </a>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="stat-card-ppmf">
            <div class="stat-icon-ppmf primary">
                <i class="bi bi-layers"></i>
            </div>
            <div>
                <span>Total Tier Records</span>
                <strong>{{ number_format($summary['total_records'] ?? $summary['total'] ?? 0) }}</strong>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="stat-card-ppmf">
            <div class="stat-icon-ppmf info">
                <i class="bi bi-building"></i>
            </div>
            <div>
                <span>Districts Covered</span>
                <strong>{{ number_format($summary['districts_count'] ?? $summary['districts_covered'] ?? 0) }}</strong>
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
                <i class="bi bi-percent"></i>
            </div>
            <div>
                <span>Average Score</span>
                <strong>{{ $summary['average_score'] ?? $summary['approval_rate'] ?? 0 }}%</strong>
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
        <form method="GET" action="{{ route('reports.district-sfn-victim-tier') }}">
            <div class="row g-3 align-items-end">

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

                        <a href="{{ route('reports.district-sfn-victim-tier') }}" class="btn-gov btn-gov-outline flex-fill">
                            <i class="bi bi-arrow-clockwise"></i>
                            Reset
                        </a>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

{{-- Report Table --}}
<div class="card-ppmf">
    <div class="card-ppmf-header">
        <div>
            <div class="card-ppmf-title">
                <i class="bi bi-layers"></i>
                District SFN Victim Tier Data
            </div>
            <p class="card-subtitle mb-0">
                Total records:
                {{ method_exists($reportData, 'total') ? number_format($reportData->total()) : number_format($reportData->count()) }}
            </p>
        </div>
    </div>

    <div class="card-ppmf-body p-0">
        <div class="table-responsive">
            <table class="table-ppmf">
                <thead>
                    <tr>
                        <th style="width:70px;">Sr.</th>
                        <th>Tier</th>
                        <th>District</th>
                        <th>Division</th>
                        <th>KPI Category</th>
                        <th>Total Inspections</th>
                        <th>Approved</th>
                        <th>Rejected</th>
                        <th>Score %</th>
                        <th>Performance</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($reportData as $index => $row)
                        @php
                            $tier = $row->tier ?? $row->district_tier ?? $row->sfn_tier ?? 'N/A';

                            $total = $row->total_inspections ?? $row->total ?? 0;
                            $approved = $row->approved_count ?? $row->approved ?? 0;
                            $rejected = $row->rejected_count ?? $row->rejected ?? 0;

                            $score = $row->score_percentage
                                ?? $row->sfn_score
                                ?? $row->approval_rate
                                ?? ($total > 0 ? round(($approved / $total) * 100, 2) : 0);

                            if ($score >= 80) {
                                $badgeClass = 'achieved';
                                $performanceText = 'Excellent';
                            } elseif ($score >= 60) {
                                $badgeClass = 'info';
                                $performanceText = 'Good';
                            } elseif ($score >= 40) {
                                $badgeClass = 'pending';
                                $performanceText = 'Average';
                            } else {
                                $badgeClass = 'critical';
                                $performanceText = 'Low';
                            }
                        @endphp

                        <tr>
                            <td>
                                {{ method_exists($reportData, 'firstItem') ? $reportData->firstItem() + $index : $index + 1 }}
                            </td>

                            <td>
                                <span class="tier-chip tier-{{ $tier }}">
                                    Tier {{ $tier }}
                                </span>
                            </td>

                            <td>
                                <strong>{{ $row->district->name ?? $row->district_name ?? 'N/A' }}</strong>
                            </td>

                            <td>
                                {{ $row->division->name ?? $row->division_name ?? 'N/A' }}
                            </td>

                            <td>
                                <span class="category-chip">
                                    {{ $row->kpiCategory->name ?? $row->kpi_category_name ?? 'N/A' }}
                                </span>
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
                                <div class="score-progress">
                                    <div class="score-text">
                                        <strong>{{ $score }}%</strong>
                                    </div>

                                    <div class="progress progress-ppmf">
                                        <div
                                            class="progress-bar"
                                            style="width: {{ min($score, 100) }}%;"
                                            role="progressbar"
                                            aria-valuenow="{{ $score }}"
                                            aria-valuemin="0"
                                            aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="badge-ppmf {{ $badgeClass }}">
                                    {{ $performanceText }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <div class="manual-box-ppmf">
                                    <i class="bi bi-layers"></i>
                                    <h5>No SFN Victim Tier Data Found</h5>
                                    <p>No district SFN victim tier report data is available for selected filters.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>

    @if(method_exists($reportData, 'links'))
        <div class="card-ppmf-body border-top">
            {{ $reportData->links() }}
        </div>
    @endif
</div>

@endsection

@push('styles')
<style>
    .tier-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 62px;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 900;
        background: rgba(27, 107, 70, 0.10);
        color: var(--gov-green);
    }

    .tier-chip.tier-1 {
        background: rgba(22, 163, 74, 0.12);
        color: #15803d;
    }

    .tier-chip.tier-2 {
        background: rgba(245, 158, 11, 0.15);
        color: #b45309;
    }

    .tier-chip.tier-3 {
        background: rgba(220, 38, 38, 0.12);
        color: #b91c1c;
    }

    .category-chip {
        display: inline-flex;
        align-items: center;
        padding: 5px 10px;
        border-radius: 999px;
        background: rgba(27, 107, 70, 0.10);
        color: var(--gov-green);
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .score-progress {
        min-width: 130px;
    }

    .score-text {
        font-size: 12px;
        color: var(--text-secondary);
        margin-bottom: 5px;
    }

    .progress-ppmf {
        height: 7px;
        border-radius: 999px;
        background: #e5e7eb;
        overflow: hidden;
    }

    .progress-ppmf .progress-bar {
        background: var(--gov-green);
        border-radius: 999px;
    }
</style>
@endpush
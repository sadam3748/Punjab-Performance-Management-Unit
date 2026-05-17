@extends('layouts.app')

@section('title', 'Division Score Report')

@section('content')

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Division Score Report</h1>
        <p class="page-subtitle">
            Division-wise inspection performance, approval rate and overall score summary.
        </p>
    </div>

    <div class="page-title-actions">
        <a href="{{ route('reports.index') }}" class="btn-gov btn-gov-outline">
            <i class="bi bi-arrow-left"></i>
            Back to Reports
        </a>

        <a href="{{ route('reports.district-comparison') }}" class="btn-gov btn-gov-primary">
            <i class="bi bi-building"></i>
            District Comparison
        </a>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="stat-card-ppmf">
            <div class="stat-icon-ppmf primary">
                <i class="bi bi-diagram-3"></i>
            </div>
            <div>
                <span>Total Divisions</span>
                <strong>{{ number_format($summary['divisions_count'] ?? $summary['total_divisions'] ?? 0) }}</strong>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="stat-card-ppmf">
            <div class="stat-icon-ppmf info">
                <i class="bi bi-clipboard-data"></i>
            </div>
            <div>
                <span>Total Inspections</span>
                <strong>{{ number_format($summary['total'] ?? $summary['total_inspections'] ?? 0) }}</strong>
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
        <form method="GET" action="{{ route('reports.division-score') }}">
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
                    <label class="form-label">Search</label>
                    <input
                        type="text"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        class="form-control"
                        placeholder="Search division/district"
                    >
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn-gov btn-gov-primary flex-fill">
                            <i class="bi bi-search"></i>
                            Apply
                        </button>

                        <a href="{{ route('reports.division-score') }}" class="btn-gov btn-gov-outline flex-fill">
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
                <i class="bi bi-diagram-3"></i>
                Division Score Data
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
                        <th style="width:70px;">Rank</th>
                        <th>Division</th>
                        <th>Districts</th>
                        <th>Total Inspections</th>
                        <th>Submitted</th>
                        <th>Reviewed</th>
                        <th>Approved</th>
                        <th>Rejected</th>
                        <th>Score %</th>
                        <th>Performance</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($reportData as $index => $row)
                        @php
                            $total = $row->total_inspections ?? $row->total ?? 0;
                            $submitted = $row->submitted_count ?? $row->submitted ?? 0;
                            $reviewed = $row->reviewed_count ?? $row->reviewed ?? 0;
                            $approved = $row->approved_count ?? $row->approved ?? 0;
                            $rejected = $row->rejected_count ?? $row->rejected ?? 0;
                            $districtCount = $row->districts_count ?? $row->total_districts ?? 0;

                            $score = $row->score_percentage
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
                                <span class="rank-chip">
                                    #{{ method_exists($reportData, 'firstItem') ? $reportData->firstItem() + $index : $index + 1 }}
                                </span>
                            </td>

                            <td>
                                <strong>{{ $row->division->name ?? $row->division_name ?? 'N/A' }}</strong>
                            </td>

                            <td>
                                <strong>{{ number_format($districtCount) }}</strong>
                            </td>

                            <td>
                                <strong>{{ number_format($total) }}</strong>
                            </td>

                            <td>
                                <span class="text-primary fw-bold">{{ number_format($submitted) }}</span>
                            </td>

                            <td>
                                <span class="text-info fw-bold">{{ number_format($reviewed) }}</span>
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
                                    <i class="bi bi-diagram-3"></i>
                                    <h5>No Division Score Data Found</h5>
                                    <p>No division score report data is available for selected filters.</p>
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
    .rank-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 44px;
        padding: 5px 9px;
        border-radius: 999px;
        background: rgba(27, 107, 70, 0.10);
        color: var(--gov-green);
        font-size: 12px;
        font-weight: 800;
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
@extends('layouts.app')

@section('title', 'District Week Rank Changelog')

@section('content')

<div class="page-title-bar">
    <div>
        <h1 class="page-title">District Week Rank Changelog</h1>
        <p class="page-subtitle">
            Weekly district rank movement based on KPI inspection performance and approval score.
        </p>
    </div>

    <div class="page-title-actions">
        <a href="{{ route('reports.index') }}" class="btn-gov btn-gov-outline">
            <i class="bi bi-arrow-left"></i>
            Back to Reports
        </a>

        <a href="{{ route('reports.district-weekly-kpi-inspection') }}" class="btn-gov btn-gov-primary">
            <i class="bi bi-calendar-week"></i>
            Weekly KPI Inspection
        </a>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="stat-card-ppmf">
            <div class="stat-icon-ppmf primary">
                <i class="bi bi-arrow-left-right"></i>
            </div>
            <div>
                <span>Total Rank Records</span>
                <strong>{{ number_format($summary['total_records'] ?? $summary['total'] ?? 0) }}</strong>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="stat-card-ppmf">
            <div class="stat-icon-ppmf success">
                <i class="bi bi-arrow-up-circle"></i>
            </div>
            <div>
                <span>Improved Districts</span>
                <strong>{{ number_format($summary['improved'] ?? $summary['improved_districts'] ?? 0) }}</strong>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="stat-card-ppmf">
            <div class="stat-icon-ppmf danger">
                <i class="bi bi-arrow-down-circle"></i>
            </div>
            <div>
                <span>Dropped Districts</span>
                <strong>{{ number_format($summary['dropped'] ?? $summary['dropped_districts'] ?? 0) }}</strong>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="stat-card-ppmf">
            <div class="stat-icon-ppmf info">
                <i class="bi bi-dash-circle"></i>
            </div>
            <div>
                <span>No Change</span>
                <strong>{{ number_format($summary['no_change'] ?? $summary['same_rank'] ?? 0) }}</strong>
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
        <form method="GET" action="{{ route('reports.district-week-rank-changelog') }}">
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
                    <label class="form-label">Rank Change</label>
                    <select name="rank_change" class="form-select">
                        <option value="">All Changes</option>
                        <option value="improved" {{ ($filters['rank_change'] ?? '') === 'improved' ? 'selected' : '' }}>Improved</option>
                        <option value="dropped" {{ ($filters['rank_change'] ?? '') === 'dropped' ? 'selected' : '' }}>Dropped</option>
                        <option value="same" {{ ($filters['rank_change'] ?? '') === 'same' ? 'selected' : '' }}>No Change</option>
                    </select>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">Week Start Date</label>
                    <input
                        type="date"
                        name="week_start"
                        value="{{ $filters['week_start'] ?? '' }}"
                        class="form-control"
                    >
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">Week End Date</label>
                    <input
                        type="date"
                        name="week_end"
                        value="{{ $filters['week_end'] ?? '' }}"
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
                        placeholder="Search district"
                    >
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn-gov btn-gov-primary flex-fill">
                            <i class="bi bi-search"></i>
                            Apply
                        </button>

                        <a href="{{ route('reports.district-week-rank-changelog') }}" class="btn-gov btn-gov-outline flex-fill">
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
                <i class="bi bi-arrow-left-right"></i>
                Weekly Rank Changelog Data
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
                        <th>Week</th>
                        <th>District</th>
                        <th>Division</th>
                        <th>KPI Category</th>
                        <th>Previous Rank</th>
                        <th>Current Rank</th>
                        <th>Change</th>
                        <th>Current Score</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($reportData as $index => $row)
                        @php
                            $previousRank = $row->previous_rank ?? $row->old_rank ?? null;
                            $currentRank = $row->current_rank ?? $row->new_rank ?? null;

                            if ($previousRank !== null && $currentRank !== null) {
                                $rankDifference = $previousRank - $currentRank;
                            } else {
                                $rankDifference = $row->rank_difference ?? $row->rank_change_value ?? 0;
                            }

                            if ($rankDifference > 0) {
                                $changeClass = 'rank-up';
                                $changeIcon = 'bi-arrow-up';
                                $changeText = '+' . $rankDifference;
                                $statusClass = 'achieved';
                                $statusText = 'Improved';
                            } elseif ($rankDifference < 0) {
                                $changeClass = 'rank-down';
                                $changeIcon = 'bi-arrow-down';
                                $changeText = (string) $rankDifference;
                                $statusClass = 'critical';
                                $statusText = 'Dropped';
                            } else {
                                $changeClass = 'rank-same';
                                $changeIcon = 'bi-dash';
                                $changeText = '0';
                                $statusClass = 'info';
                                $statusText = 'No Change';
                            }

                            $score = $row->score_percentage
                                ?? $row->current_score
                                ?? $row->approval_rate
                                ?? 0;

                            $weekStart = $row->week_start ?? $row->week_start_date ?? $filters['week_start'] ?? null;
                            $weekEnd = $row->week_end ?? $row->week_end_date ?? $filters['week_end'] ?? null;
                        @endphp

                        <tr>
                            <td>
                                {{ method_exists($reportData, 'firstItem') ? $reportData->firstItem() + $index : $index + 1 }}
                            </td>

                            <td>
                                <strong>
                                    @if($weekStart && $weekEnd)
                                        {{ \Carbon\Carbon::parse($weekStart)->format('d M') }}
                                        -
                                        {{ \Carbon\Carbon::parse($weekEnd)->format('d M, Y') }}
                                    @elseif($weekStart)
                                        {{ \Carbon\Carbon::parse($weekStart)->format('d M, Y') }}
                                    @else
                                        N/A
                                    @endif
                                </strong>
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
                                <span class="rank-chip muted">
                                    {{ $previousRank ? '#' . $previousRank : 'N/A' }}
                                </span>
                            </td>

                            <td>
                                <span class="rank-chip current">
                                    {{ $currentRank ? '#' . $currentRank : 'N/A' }}
                                </span>
                            </td>

                            <td>
                                <span class="rank-change {{ $changeClass }}">
                                    <i class="bi {{ $changeIcon }}"></i>
                                    {{ $changeText }}
                                </span>
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
                                <span class="badge-ppmf {{ $statusClass }}">
                                    {{ $statusText }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <div class="manual-box-ppmf">
                                    <i class="bi bi-arrow-left-right"></i>
                                    <h5>No Rank Changelog Data Found</h5>
                                    <p>No weekly district rank changelog data is available for selected filters.</p>
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

    .rank-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 46px;
        padding: 5px 9px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 900;
    }

    .rank-chip.muted {
        background: #f1f5f9;
        color: #64748b;
    }

    .rank-chip.current {
        background: rgba(27, 107, 70, 0.10);
        color: var(--gov-green);
    }

    .rank-change {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 5px 9px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 900;
    }

    .rank-change.rank-up {
        background: rgba(22, 163, 74, 0.12);
        color: #15803d;
    }

    .rank-change.rank-down {
        background: rgba(220, 38, 38, 0.12);
        color: #b91c1c;
    }

    .rank-change.rank-same {
        background: rgba(37, 99, 235, 0.10);
        color: #1d4ed8;
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
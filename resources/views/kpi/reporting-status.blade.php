@extends('layouts.app')

@section('title', 'KPI Reporting Status')

@section('content')

<div class="page-title-bar">
    <div>
        <h1 class="page-title">KPI Reporting Status</h1>
        <p class="page-subtitle">
            Monitor district-wise KPI submission progress for the selected reporting period.
        </p>
    </div>

    <div class="page-title-actions">
        <a href="{{ route('kpi.provincial-data') }}" class="btn-gov btn-gov-outline">
            <i class="bi bi-table"></i>
            Provincial KPI Data
        </a>

        <a href="{{ route('kpi.graphical-report') }}" class="btn-gov btn-gov-primary">
            <i class="bi bi-bar-chart-line"></i>
            Graphical Report
        </a>
    </div>
</div>

{{-- Old PPMF-style: no large summary cards here --}}

{{-- Filters --}}
<div class="card-ppmf mb-4">
    <div class="card-ppmf-header">
        <div class="card-ppmf-title">
            <i class="bi bi-funnel"></i>
            Filters
        </div>
    </div>

    <div class="card-ppmf-body">
        <form method="GET" action="{{ route('kpi.reporting-status') }}">
            <div class="row g-3 align-items-end">
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">Period Type</label>
                    <select name="period_type" class="form-select">
                        @foreach (($periodOptions ?? []) as $value => $label)
                            <option value="{{ $value }}" {{ ($filters['period_type'] ?? 'weekly') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">Week</label>
                    <select name="week_no" class="form-select" {{ ($filters['period_type'] ?? 'weekly') === 'weekly' ? '' : 'disabled' }}>
                        <option value="">Select Week</option>
                        @foreach(($weekOptions ?? []) as $weekNo => $label)
                            <option value="{{ $weekNo }}" {{ (string) ($filters['week_no'] ?? '') === (string) $weekNo ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">Month</label>
                    <select name="month" class="form-select" {{ ($filters['period_type'] ?? 'weekly') === 'yearly' ? 'disabled' : '' }}>
                        <option value="">Select Month</option>
                        @foreach(($months ?? []) as $m)
                            <option value="{{ $m['value'] }}" {{ (int) ($filters['month'] ?? 0) === (int) $m['value'] ? 'selected' : '' }}>
                                {{ $m['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">Year</label>
                    <select name="year" class="form-select">
                        @foreach(($years ?? []) as $y)
                            <option value="{{ $y }}" {{ (int) ($filters['year'] ?? 0) === (int) $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

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
                        @foreach (($tehsils ?? []) as $tehsil)
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
                    <label class="form-label">Per Page</label>
                    @php
                        $currentPerPage = (int) ($filters['per_page'] ?? request('per_page', 10));
                    @endphp
                    <select name="per_page" class="form-select">
                        @foreach ([10, 20, 25, 50] as $option)
                            <option value="{{ $option }}" {{ $currentPerPage === $option ? 'selected' : '' }}>
                                {{ $option }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn-gov btn-gov-primary flex-fill">
                            <i class="bi bi-funnel"></i>
                            Apply
                        </button>

                        <a href="{{ route('kpi.reporting-status') }}" class="btn-gov btn-gov-outline flex-fill">
                            <i class="bi bi-arrow-clockwise"></i>
                            Reset
                        </a>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

{{-- Reporting Status Table --}}
<div class="card-ppmf">
    <div class="card-ppmf-header">
        <div>
            <div class="card-ppmf-title">
                <i class="bi bi-list-check"></i>
                KPI Reporting Status
            </div>
        </div>
    </div>

    <div class="card-ppmf-body p-0">
        @php
            $reportingPeriod = $filters['period_label'] ?? ($filters['period_type'] ?? 'weekly');
            $totalDistricts = $summary['total_districts'] ?? (method_exists($districts, 'count') ? $districts->count() : null);
            $defaultExpected = $summary['expected_count'] ?? 27;
        @endphp

        <div class="report-info-strip">
            <div class="report-info-item">
                <span class="report-info-label">Reporting Period</span>
                <span class="report-info-value">{{ $reportingPeriod }}</span>
            </div>
            <div class="report-info-item">
                <span class="report-info-label">Expected KPIs</span>
                <span class="report-info-value">{{ number_format((int) $defaultExpected) }}</span>
            </div>
            @if($totalDistricts !== null)
                <div class="report-info-item">
                    <span class="report-info-label">Total Districts</span>
                    <span class="report-info-value">{{ number_format((int) $totalDistricts) }}</span>
                </div>
            @endif
        </div>

        <div class="helper-strip">
            <div class="helper-text">
                KPI Submission shows how many required KPI reports have been submitted by each district.
            </div>
            <div class="helper-guide">
                <span><span class="guide-dot complete"></span> Complete = all KPIs submitted</span>
                <span><span class="guide-dot partial"></span> Partial = some KPIs submitted</span>
                <span><span class="guide-dot pending"></span> Pending = no KPI submitted</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table-ppmf">
                <thead>
                    <tr>
                        <th>District</th>
                        <th style="width: 180px;">Reporting Status</th>
                        <th style="width: 140px; text-align:center;">KPI Submission</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($reportingStatus as $index => $row)
                        @php
                            $totalExpected = $row->expected_count
                                ?? $row->required_count
                                ?? ($summary['expected_count'] ?? null)
                                ?? 27;

                            $submitted = $row->submitted_count
                                ?? $row->reported_count
                                ?? $row->reported
                                ?? $row->approved_count
                                ?? $row->total_records
                                ?? 0;

                            if ($totalExpected > 0 && $submitted >= $totalExpected) {
                                $status = 'Complete';
                                $statusClass = 'complete';
                            } elseif ($submitted > 0) {
                                $status = 'Partial';
                                $statusClass = 'partial';
                            } else {
                                $status = 'Not Submitted';
                                $statusClass = 'not-submitted';
                            }
                        @endphp

                        <tr>
                            <td class="district-name-cell">
                                {{ $row->district->name ?? $row->district_name ?? 'N/A' }}
                            </td>

                            <td>
                                <span class="reporting-status-badge {{ $statusClass }}">
                                    {{ $status }}
                                </span>
                            </td>

                            <td style="text-align:center;">
                                <span class="submitted-count {{ $statusClass }}">
                                    {{ number_format((int) $submitted) }} / {{ number_format((int) $totalExpected) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-5">
                                <div class="manual-box-ppmf">
                                    <i class="bi bi-clipboard-x"></i>
                                    <h5>No KPI Reporting Status Found</h5>
                                    <p>No reporting records are available for the selected filters.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if (method_exists($reportingStatus, 'links'))
        {{-- Custom Stable Pagination (Inspection List style) --}}
        @if (method_exists($reportingStatus, 'lastPage') && $reportingStatus->lastPage() > 1)
            @php
                $reportingStatus->appends(request()->query());

                $currentPage = $reportingStatus->currentPage();
                $lastPage = $reportingStatus->lastPage();
                $startPage = max(1, $currentPage - 2);
                $endPage = min($lastPage, $currentPage + 2);

                if ($currentPage <= 3) {
                    $endPage = min($lastPage, 5);
                }

                if ($currentPage >= $lastPage - 2) {
                    $startPage = max(1, $lastPage - 4);
                }
            @endphp

            <div class="card-ppmf-body border-top p-0">
                <div class="inspection-pagination-bar">
                    <div class="inspection-pagination-summary-group">
                        <div class="inspection-pagination-summary">
                            Showing {{ number_format($reportingStatus->firstItem() ?? 0) }} to {{ number_format($reportingStatus->lastItem() ?? 0) }}
                            of {{ number_format($reportingStatus->total() ?? 0) }} records
                        </div>
                        <div class="inspection-pagination-per-page">
                            {{ (int) ($filters['per_page'] ?? request('per_page', 10)) }} per page
                        </div>
                    </div>

                    <nav class="inspection-pagination-nav" aria-label="KPI reporting status pagination">
                        <a
                            href="{{ $reportingStatus->previousPageUrl() ?: 'javascript:void(0)' }}"
                            class="inspection-page-link {{ $reportingStatus->onFirstPage() ? 'disabled' : '' }}"
                        >
                            <i class="bi bi-chevron-left"></i>
                            Previous
                        </a>

                        @if ($startPage > 1)
                            <a href="{{ $reportingStatus->url(1) }}" class="inspection-page-number">1</a>
                            @if ($startPage > 2)
                                <span class="inspection-page-dots">...</span>
                            @endif
                        @endif

                        @for ($page = $startPage; $page <= $endPage; $page++)
                            <a
                                href="{{ $reportingStatus->url($page) }}"
                                class="inspection-page-number {{ $page == $currentPage ? 'active' : '' }}"
                            >
                                {{ $page }}
                            </a>
                        @endfor

                        @if ($endPage < $lastPage)
                            @if ($endPage < $lastPage - 1)
                                <span class="inspection-page-dots">...</span>
                            @endif
                            <a href="{{ $reportingStatus->url($lastPage) }}" class="inspection-page-number">{{ $lastPage }}</a>
                        @endif

                        <a
                            href="{{ $reportingStatus->nextPageUrl() ?: 'javascript:void(0)' }}"
                            class="inspection-page-link {{ $reportingStatus->hasMorePages() ? '' : 'disabled' }}"
                        >
                            Next
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </nav>
                </div>
            </div>
        @endif
    @endif
</div>

@endsection

@push('styles')
<style>
    .report-info-strip {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        padding: 14px 18px;
        border-bottom: 1px solid var(--border-light);
        background: #ffffff;
    }

    .helper-strip {
        padding: 10px 18px 14px;
        border-bottom: 1px solid var(--border-light);
        background: #ffffff;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px;
        flex-wrap: wrap;
    }

    .helper-text {
        font-size: 12px;
        font-weight: 700;
        color: var(--text-secondary);
    }

    .helper-guide {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        font-size: 12px;
        font-weight: 800;
        color: var(--text-secondary);
        white-space: nowrap;
    }

    .guide-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 999px;
        margin-right: 6px;
        position: relative;
        top: 1px;
    }

    .guide-dot.complete { background: #16a34a; }
    .guide-dot.partial { background: #f59e0b; }
    .guide-dot.pending { background: #ef4444; }

    .report-info-item {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 180px;
    }

    .report-info-label {
        font-size: 11px;
        font-weight: 800;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .report-info-value {
        font-size: 13px;
        font-weight: 900;
        color: var(--text-primary);
    }

    .reporting-status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 120px;
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .reporting-status-badge.complete {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .reporting-status-badge.partial {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }

    .reporting-status-badge.not-submitted {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .submitted-count {
        font-weight: 900;
        color: #0f172a;
        white-space: nowrap;
    }

    .submitted-count.complete { color: #166534; }
    .submitted-count.partial { color: #92400e; }
    .submitted-count.not-submitted { color: #991b1b; }

    .district-name-cell {
        font-weight: 900;
        text-transform: uppercase;
    }

    .kpi-status-card {
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

    .kpi-status-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: grid;
        place-items: center;
        font-size: 19px;
        flex-shrink: 0;
    }

    .kpi-status-card span {
        display: block;
        font-size: 12.5px;
        color: var(--text-muted);
        font-weight: 700;
        margin-bottom: 2px;
    }

    .kpi-status-card strong {
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

    /* Pagination (match Inspection List) */
    .inspection-pagination-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 14px 18px;
        border-top: 1px solid var(--border-light);
        background: #ffffff;
    }

    .inspection-pagination-summary-group {
        display: flex;
        flex-direction: column;
        gap: 3px;
        min-width: 210px;
    }

    .inspection-pagination-summary {
        color: #334155;
        font-size: 13px;
        font-weight: 850;
        white-space: nowrap;
    }

    .inspection-pagination-per-page {
        color: #64748b;
        font-size: 12px;
        font-weight: 750;
        white-space: nowrap;
    }

    .inspection-pagination-nav {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 6px;
        flex-wrap: wrap;
    }

    .inspection-page-link,
    .inspection-page-number,
    .inspection-page-dots {
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

    .inspection-page-link {
        gap: 6px;
    }

    .inspection-page-number.active {
        background: #166534;
        color: #ffffff;
        border-color: #166534;
        box-shadow: 0 8px 18px rgba(22, 101, 52, 0.22);
    }

    .inspection-page-link:hover,
    .inspection-page-number:hover {
        background: #ecfdf3;
        color: #14532d;
        border-color: #86efac;
    }

    .inspection-page-link.disabled {
        pointer-events: none;
        color: #94a3b8;
        background: #f1f5f9;
        border-color: #e2e8f0;
        box-shadow: none;
    }

    .inspection-page-dots {
        border-color: transparent;
        background: transparent;
        color: #94a3b8;
        min-width: 26px;
        padding: 0 4px;
    }

    @media (max-width: 991px) {
        .inspection-pagination-bar {
            align-items: flex-start;
            flex-direction: column;
        }

        .inspection-pagination-summary,
        .inspection-pagination-per-page {
            white-space: normal;
        }

        .inspection-pagination-summary-group {
            min-width: 0;
        }

        .inspection-pagination-nav {
            justify-content: flex-start;
            width: 100%;
        }
    }

    @media (max-width: 767px) {
        .inspection-pagination-bar {
            padding: 14px;
        }

        .inspection-page-link,
        .inspection-page-number,
        .inspection-page-dots {
            min-width: 34px;
            height: 34px;
            padding: 0 10px;
            font-size: 12px;
        }
    }
</style>
@endpush

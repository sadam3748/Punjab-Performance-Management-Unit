@extends('layouts.app')

@section('title', 'KPI Reporting Status')

@section('content')

<div class="page-title-bar">
    <div>
        <h1 class="page-title">KPI Reporting Status</h1>
        <p class="page-subtitle">
            Monitor district, tehsil and category-wise KPI reporting progress across Punjab.
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

{{-- Summary Cards --}}
<div class="row g-3 mb-4">

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="kpi-status-card">
            <div class="kpi-status-icon bg-soft-primary">
                <i class="bi bi-clipboard-data"></i>
            </div>
            <div>
                <span>Total Records</span>
                <strong>{{ number_format($summary['total_records'] ?? 0) }}</strong>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="kpi-status-card">
            <div class="kpi-status-icon bg-soft-success">
                <i class="bi bi-check-circle"></i>
            </div>
            <div>
                <span>Reported</span>
                <strong>{{ number_format($summary['reported'] ?? 0) }}</strong>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="kpi-status-card">
            <div class="kpi-status-icon bg-soft-warning">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div>
                <span>Pending</span>
                <strong>{{ number_format($summary['pending'] ?? 0) }}</strong>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="kpi-status-card">
            <div class="kpi-status-icon bg-soft-info">
                <i class="bi bi-percent"></i>
            </div>
            <div>
                <span>Reporting Rate</span>
                <strong>{{ $summary['reporting_rate'] ?? 0 }}%</strong>
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
        <form method="GET" action="{{ route('kpi.reporting-status') }}">
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
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        @foreach (['reported', 'pending', 'submitted', 'approved', 'rejected'] as $status)
                            <option value="{{ $status }}"
                                {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
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
                    <label class="form-label">Search</label>
                    <input
                        type="text"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        class="form-control"
                        placeholder="Search district/category"
                    >
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

            <p class="card-subtitle mb-0">
                Total records:
                {{ method_exists($reportingStatus, 'total') ? number_format($reportingStatus->total()) : number_format($reportingStatus->count()) }}
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
                        <th>District</th>
                        <th>Tehsil</th>
                        <th>Total Inspections</th>
                        <th>Reported</th>
                        <th>Pending</th>
                        <th>Reporting %</th>
                        <th>Status</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($reportingStatus as $index => $row)
                        @php
                            $total = $row->total_inspections ?? $row->total_records ?? 0;
                            $reported = $row->reported_count ?? $row->reported ?? $row->approved_count ?? 0;
                            $pending = $row->pending_count ?? $row->pending ?? max($total - $reported, 0);
                            $rate = $total > 0 ? round(($reported / $total) * 100, 2) : 0;

                            $statusText = $row->status ?? ($rate >= 100 ? 'reported' : ($rate > 0 ? 'partial' : 'pending'));
                        @endphp

                        <tr>
                            <td>
                                {{ method_exists($reportingStatus, 'firstItem') ? $reportingStatus->firstItem() + $index : $index + 1 }}
                            </td>

                            <td>
                                <div class="fw-bold">
                                    {{ $row->kpiCategory->name ?? $row->kpi_category_name ?? 'N/A' }}
                                </div>
                            </td>

                            <td>
                                {{ $row->district->name ?? $row->district_name ?? 'N/A' }}
                            </td>

                            <td>
                                {{ $row->tehsil->name ?? $row->tehsil_name ?? 'All Tehsils' }}
                            </td>

                            <td>
                                <strong>{{ number_format($total) }}</strong>
                            </td>

                            <td>
                                <span class="text-success fw-bold">
                                    {{ number_format($reported) }}
                                </span>
                            </td>

                            <td>
                                <span class="text-warning fw-bold">
                                    {{ number_format($pending) }}
                                </span>
                            </td>

                            <td>
                                <div class="progress-cell">
                                    <div class="progress-text">
                                        <strong>{{ $rate }}%</strong>
                                    </div>

                                    <div class="progress progress-ppmf">
                                        <div
                                            class="progress-bar"
                                            style="width: {{ min($rate, 100) }}%;"
                                            role="progressbar"
                                            aria-valuenow="{{ $rate }}"
                                            aria-valuemin="0"
                                            aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="badge-ppmf
                                    @if($statusText === 'reported' || $statusText === 'approved') achieved
                                    @elseif($statusText === 'partial' || $statusText === 'submitted') info
                                    @elseif($statusText === 'rejected') critical
                                    @else pending
                                    @endif
                                ">
                                    {{ ucfirst(str_replace('_', ' ', $statusText)) }}
                                </span>
                            </td>

                            <td>
                                @if(!empty($row->updated_at))
                                    {{ \Carbon\Carbon::parse($row->updated_at)->format('d M, Y h:i A') }}
                                @elseif(!empty($row->last_reported_at))
                                    {{ \Carbon\Carbon::parse($row->last_reported_at)->format('d M, Y h:i A') }}
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5">
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

    .progress-cell {
        min-width: 130px;
    }

    .progress-text {
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

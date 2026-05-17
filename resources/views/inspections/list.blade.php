@extends('layouts.app')

@section('title', 'Inspection List')

@section('content')

<div class="inspection-page">

    {{-- Page Title --}}
    <div class="page-title-bar inspection-title-bar">
        <div>
            <h1 class="page-title mb-1">Inspection List</h1>
            <p class="page-subtitle mb-0">
                Review field inspection records with district, tehsil, KPI category, status and location details.
            </p>
        </div>

        <div class="inspection-title-meta">
            <span class="inspection-total-chip">
                <i class="bi bi-clipboard-data"></i>
                {{ method_exists($inspections, 'total') ? number_format($inspections->total()) : number_format($inspections->count()) }}
                Records
            </span>
        </div>
    </div>

    {{-- Filters --}}
    <div class="inspection-filter-card mb-4">
        <div class="inspection-filter-header">
            <div>
                <h5 class="inspection-section-title mb-1">
                    <i class="bi bi-funnel"></i>
                    Search & Filters
                </h5>
                <p class="inspection-section-subtitle mb-0">
                    Apply filters to refine inspection records. Pagination will keep selected filters active.
                </p>
            </div>
        </div>

        <form method="GET" action="{{ route('inspections.list') }}">
            <input type="hidden" name="per_page" value="{{ $filters['per_page'] ?? request('per_page', 10) }}">
            <div class="row g-3 align-items-end">

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label inspection-label">District</label>
                    <select name="district_id" class="form-select inspection-control">
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
                    <label class="form-label inspection-label">Tehsil</label>
                    <select name="tehsil_id" class="form-select inspection-control">
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
                    <label class="form-label inspection-label">KPI Category</label>
                    <select name="kpi_category_id" class="form-select inspection-control">
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
                    <label class="form-label inspection-label">Status</label>
                    <select name="status" class="form-select inspection-control">
                        <option value="">All Status</option>
                        @foreach (['submitted', 'reviewed', 'approved', 'rejected'] as $status)
                            <option value="{{ $status }}"
                                {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label inspection-label">From Date</label>
                    <input
                        type="date"
                        name="date_from"
                        value="{{ $filters['date_from'] ?? '' }}"
                        class="form-control inspection-control"
                    >
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label inspection-label">To Date</label>
                    <input
                        type="date"
                        name="date_to"
                        value="{{ $filters['date_to'] ?? '' }}"
                        class="form-control inspection-control"
                    >
                </div>

                <div class="col-xl-4 col-lg-4 col-md-6">
                    <div class="inspection-filter-actions">
                        <button type="submit" class="btn-gov btn-gov-primary inspection-action-btn">
                            <i class="bi bi-funnel"></i>
                            Apply Filters
                        </button>

                        <a href="{{ route('inspections.list') }}" class="btn-gov btn-gov-outline inspection-action-btn">
                            <i class="bi bi-arrow-clockwise"></i>
                            Reset
                        </a>
                    </div>
                </div>

            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="inspection-table-card">
        <div class="inspection-card-header">
            <div>
                <h5 class="inspection-section-title mb-1">
                    <i class="bi bi-table"></i>
                    Inspection Records
                </h5>
                <p class="inspection-section-subtitle mb-0">
                    @if (method_exists($inspections, 'firstItem') && $inspections->total() > 0)
                        Showing {{ number_format($inspections->firstItem()) }} to {{ number_format($inspections->lastItem()) }}
                        of {{ number_format($inspections->total()) }} records
                    @else
                        Total records: {{ method_exists($inspections, 'total') ? number_format($inspections->total()) : number_format($inspections->count()) }}
                    @endif
                </p>
            </div>

            <form class="inspection-per-page-form" method="GET" action="{{ route('inspections.list') }}">
                @foreach(request()->except('per_page', 'page') as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach
                <label for="inspection-per-page" class="inspection-per-page-label">Per page</label>
                <select id="inspection-per-page" name="per_page" class="form-select inspection-control inspection-per-page-select" onchange="this.form.submit()">
                    @foreach ([10, 20, 25, 50] as $perPageOption)
                        <option value="{{ $perPageOption }}"
                            {{ (int) ($filters['per_page'] ?? request('per_page', 10)) === $perPageOption ? 'selected' : '' }}>
                            {{ $perPageOption }}
                        </option>
                    @endforeach
                </select>
            </form>

            <div class="inspection-table-legend">
                <span><i class="legend-dot submitted"></i>Submitted</span>
                <span><i class="legend-dot reviewed"></i>Reviewed</span>
                <span><i class="legend-dot approved"></i>Approved</span>
                <span><i class="legend-dot rejected"></i>Rejected</span>
            </div>
        </div>

        <div class="inspection-table-wrap">
            <table class="table inspection-table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="inspection-col-sr">Sr.</th>
                        <th>Inspection Type</th>
                        <th>Main Detail</th>
                        <th>District</th>
                        <th>Tehsil</th>
                        <th>Performed By</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                        <th class="text-center inspection-col-action">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($inspections as $index => $inspection)
                        @php
                            $status = $inspection->status ?? 'submitted';
                            $statusClass = match ($status) {
                                'approved' => 'row-approved',
                                'reviewed' => 'row-reviewed',
                                'rejected' => 'row-rejected',
                                default => 'row-submitted',
                            };
                        @endphp

                        <tr class="{{ $statusClass }}">
                            <td class="fw-bold text-muted">
                                {{ method_exists($inspections, 'firstItem') ? $inspections->firstItem() + $index : $index + 1 }}
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
                                <div class="inspection-title">
                                    {{ $inspection->main_title ?? 'N/A' }}
                                </div>

                                <div class="inspection-address">
                                    {{ $inspection->main_address ?? 'No address available' }}
                                </div>

                                @if (!empty($inspection->main_identifier))
                                    <div class="inspection-meta">
                                        ID/CNIC: {{ $inspection->main_identifier }}
                                    </div>
                                @endif
                            </td>

                            <td>
                                <span class="area-chip district-chip">
                                    {{ $inspection->district->name ?? 'N/A' }}
                                </span>
                            </td>

                            <td>
                                <span class="area-chip tehsil-chip">
                                    {{ $inspection->tehsil->name ?? 'N/A' }}
                                </span>
                            </td>

                            <td>
                                <div class="inspection-user">
                                    <div class="inspection-user-avatar">
                                        {{ strtoupper(substr($inspection->performer->username ?? $inspection->performer->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="inspection-user-name">
                                            {{ $inspection->performer->username ?? 'N/A' }}
                                        </div>
                                        <small class="inspection-user-role">
                                            {{ $inspection->performer->designation ?? $inspection->performer->name ?? '' }}
                                        </small>
                                    </div>
                                </div>
                            </td>

                            <td>
                                @if ($inspection->inspection_datetime)
                                    <div class="inspection-date">
                                        {{ \Carbon\Carbon::parse($inspection->inspection_datetime)->format('d M, Y') }}
                                    </div>
                                    <div class="inspection-time">
                                        {{ \Carbon\Carbon::parse($inspection->inspection_datetime)->format('h:i A') }}
                                    </div>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>

                            <td>
                                <span class="inspection-status-badge status-{{ $status }}">
                                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                                </span>
                            </td>

                            <td class="text-center">
                                <a href="{{ route('inspections.show', $inspection->id) }}"
                                   class="inspection-view-btn"
                                   title="View Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <div class="inspection-empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <h6>No inspection records found</h6>
                                    <p>Try changing filters or add inspection dummy data.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Custom Stable Pagination --}}
        @if (method_exists($inspections, 'lastPage') && $inspections->lastPage() > 1)
            @php
                $inspections->appends(request()->query());

                $currentPage = $inspections->currentPage();
                $lastPage = $inspections->lastPage();
                $startPage = max(1, $currentPage - 2);
                $endPage = min($lastPage, $currentPage + 2);

                if ($currentPage <= 3) {
                    $endPage = min($lastPage, 5);
                }

                if ($currentPage >= $lastPage - 2) {
                    $startPage = max(1, $lastPage - 4);
                }
            @endphp

            <div class="inspection-pagination-bar">
                <div class="inspection-pagination-summary-group">
                    <div class="inspection-pagination-summary">
                        Showing {{ number_format($inspections->firstItem()) }} to {{ number_format($inspections->lastItem()) }}
                        of {{ number_format($inspections->total()) }} records
                    </div>
                    <div class="inspection-pagination-per-page">
                        {{ (int) ($filters['per_page'] ?? request('per_page', 10)) }} per page
                    </div>
                </div>

                <nav class="inspection-pagination-nav" aria-label="Inspection pagination">
                    <a
                        href="{{ $inspections->previousPageUrl() ?: 'javascript:void(0)' }}"
                        class="inspection-page-link {{ $inspections->onFirstPage() ? 'disabled' : '' }}"
                    >
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </a>

                    @if ($startPage > 1)
                        <a href="{{ $inspections->url(1) }}" class="inspection-page-number">1</a>
                        @if ($startPage > 2)
                            <span class="inspection-page-dots">...</span>
                        @endif
                    @endif

                    @for ($page = $startPage; $page <= $endPage; $page++)
                        <a
                            href="{{ $inspections->url($page) }}"
                            class="inspection-page-number {{ $page == $currentPage ? 'active' : '' }}"
                        >
                            {{ $page }}
                        </a>
                    @endfor

                    @if ($endPage < $lastPage)
                        @if ($endPage < $lastPage - 1)
                            <span class="inspection-page-dots">...</span>
                        @endif
                        <a href="{{ $inspections->url($lastPage) }}" class="inspection-page-number">{{ $lastPage }}</a>
                    @endif

                    <a
                        href="{{ $inspections->nextPageUrl() ?: 'javascript:void(0)' }}"
                        class="inspection-page-link {{ $inspections->hasMorePages() ? '' : 'disabled' }}"
                    >
                        Next
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </nav>
            </div>
        @endif
    </div>

</div>

@endsection

@push('styles')
<style>
    .inspection-page {
        --inspection-green: #166534;
        --inspection-green-dark: #14532d;
        --inspection-green-soft: #ecfdf3;
        --inspection-border: #e2e8f0;
        --inspection-text: #0f172a;
        --inspection-muted: #64748b;
        --inspection-bg: #f8fafc;
    }

    .inspection-title-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 20px;
    }

    .inspection-title-meta {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .inspection-total-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        border-radius: 999px;
        background: linear-gradient(135deg, #ecfdf3, #ffffff);
        border: 1px solid #bbf7d0;
        color: var(--inspection-green-dark);
        font-size: 13px;
        font-weight: 800;
        white-space: nowrap;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    }

    .inspection-filter-card,
    .inspection-table-card {
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 20px;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }

    .inspection-filter-card {
        padding: 18px;
    }

    .inspection-filter-header,
    .inspection-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 16px;
    }

    .inspection-section-title {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--inspection-text);
        font-size: 16px;
        font-weight: 900;
        letter-spacing: -0.01em;
    }

    .inspection-section-title i {
        color: var(--inspection-green);
    }

    .inspection-section-subtitle {
        color: var(--inspection-muted);
        font-size: 13px;
        font-weight: 600;
    }

    .inspection-label {
        color: #334155;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.045em;
        margin-bottom: 7px;
    }

    .inspection-control {
        min-height: 42px;
        border-radius: 12px;
        border: 1px solid #cbd5e1;
        color: #0f172a;
        font-size: 13px;
        font-weight: 650;
        box-shadow: none;
    }

    .inspection-control:focus {
        border-color: #22c55e;
        box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.12);
    }

    .inspection-filter-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .inspection-per-page-form {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-width: 220px;
    }

    .inspection-per-page-label {
        color: #334155;
        font-size: 13px;
        font-weight: 700;
        white-space: nowrap;
    }

    .inspection-per-page-select {
        font-weight: 850;
        color: #14532d;
        background-color: #f8fafc;
        min-width: 120px;
    }

    .inspection-action-btn {
        min-height: 42px;
        justify-content: center;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 850;
        white-space: nowrap;
    }

    .inspection-table-card {
        padding: 0;
    }

    .inspection-card-header {
        padding: 18px 18px 14px;
        margin-bottom: 0;
        border-bottom: 1px solid var(--inspection-border);
        background: linear-gradient(135deg, #ffffff, #f8fafc);
    }

    .inspection-table-legend {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 10px;
        color: #475569;
        font-size: 12px;
        font-weight: 800;
    }

    .inspection-table-legend span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .legend-dot {
        width: 9px;
        height: 9px;
        border-radius: 999px;
        display: inline-block;
    }

    .legend-dot.submitted { background: #64748b; }
    .legend-dot.reviewed { background: #0284c7; }
    .legend-dot.approved { background: #16a34a; }
    .legend-dot.rejected { background: #dc2626; }

    .inspection-table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .inspection-table {
        min-width: 1180px;
        border-collapse: separate;
        border-spacing: 0;
    }

    .inspection-table thead th {
        background: linear-gradient(180deg, #14532d, #166534);
        color: #ffffff;
        font-size: 11.5px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.055em;
        padding: 13px 14px;
        border: 0;
        white-space: nowrap;
        vertical-align: middle;
    }

    .inspection-table thead th:first-child {
        border-top-left-radius: 0;
    }

    .inspection-table thead th:last-child {
        border-top-right-radius: 0;
    }

    .inspection-table tbody td {
        padding: 14px;
        border-bottom: 1px solid #e5e7eb;
        color: #0f172a;
        font-size: 13px;
        vertical-align: middle;
    }

    .inspection-table tbody tr {
        transition: 0.18s ease;
    }

    .inspection-table tbody tr:hover {
        transform: translateY(-1px);
        box-shadow: inset 4px 0 0 #16a34a;
        filter: brightness(0.995);
    }

    .inspection-table tbody tr.row-submitted { background: #f8fafc; }
    .inspection-table tbody tr.row-reviewed { background: #f0f9ff; }
    .inspection-table tbody tr.row-approved { background: #f0fdf4; }
    .inspection-table tbody tr.row-rejected { background: #fef2f2; }

    .inspection-col-sr {
        width: 70px;
    }

    .inspection-col-action {
        width: 90px;
    }

    .inspection-type-cell {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        font-weight: 850;
        color: #0f172a;
        min-width: 155px;
    }

    .inspection-type-icon {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 11px;
        color: #166534;
        background: #dcfce7;
        flex-shrink: 0;
    }

    .inspection-title {
        font-weight: 900;
        color: #0f172a;
        margin-bottom: 3px;
        max-width: 360px;
    }

    .inspection-address {
        font-size: 12.5px;
        color: #475569;
        max-width: 380px;
        line-height: 1.45;
    }

    .inspection-meta {
        font-size: 11.5px;
        color: #64748b;
        margin-top: 4px;
        font-weight: 700;
    }

    .area-chip {
        display: inline-flex;
        align-items: center;
        padding: 6px 9px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 850;
        white-space: nowrap;
    }

    .district-chip {
        color: #14532d;
        background: #dcfce7;
        border: 1px solid #bbf7d0;
    }

    .tehsil-chip {
        color: #075985;
        background: #e0f2fe;
        border: 1px solid #bae6fd;
    }

    .inspection-user {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 155px;
    }

    .inspection-user-avatar {
        width: 34px;
        height: 34px;
        border-radius: 12px;
        background: linear-gradient(135deg, #14532d, #16a34a);
        color: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 900;
        flex-shrink: 0;
    }

    .inspection-user-name {
        color: #0f172a;
        font-size: 13px;
        font-weight: 900;
        line-height: 1.2;
    }

    .inspection-user-role {
        color: #64748b;
        font-size: 11.5px;
        font-weight: 650;
    }

    .inspection-date {
        color: #0f172a;
        font-size: 13px;
        font-weight: 850;
        white-space: nowrap;
    }

    .inspection-time {
        color: #64748b;
        font-size: 12px;
        font-weight: 750;
        white-space: nowrap;
    }

    .location-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 9px;
        border-radius: 999px;
        background: rgba(14, 165, 233, 0.10);
        color: #0369a1;
        border: 1px solid rgba(14, 165, 233, 0.22);
        font-size: 11.5px;
        font-weight: 800;
        white-space: nowrap;
    }

    .inspection-status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 92px;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 11.5px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.035em;
        white-space: nowrap;
    }

    .inspection-status-badge.status-submitted {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
    }

    .inspection-status-badge.status-reviewed {
        background: #e0f2fe;
        color: #0369a1;
        border: 1px solid #bae6fd;
    }

    .inspection-status-badge.status-approved {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .inspection-status-badge.status-rejected {
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }

    .inspection-view-btn {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #ecfdf3;
        color: #166534;
        border: 1px solid #bbf7d0;
        text-decoration: none;
        transition: 0.18s ease;
    }

    .inspection-view-btn:hover {
        background: #166534;
        color: #ffffff;
        border-color: #166534;
        transform: translateY(-1px);
    }

    .inspection-empty-state {
        display: grid;
        place-items: center;
        gap: 7px;
        color: #64748b;
    }

    .inspection-empty-state i {
        font-size: 40px;
        color: #94a3b8;
    }

    .inspection-empty-state h6 {
        margin: 0;
        font-weight: 900;
        color: #334155;
    }

    .inspection-empty-state p {
        margin: 0;
        font-size: 13px;
        font-weight: 600;
    }

    .inspection-pagination-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 14px 18px;
        border-top: 1px solid var(--inspection-border);
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
        .inspection-title-bar,
        .inspection-card-header,
        .inspection-filter-header,
        .inspection-pagination-bar {
            align-items: flex-start;
            flex-direction: column;
        }

        .inspection-table-legend {
            justify-content: flex-start;
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
        .inspection-filter-card {
            padding: 14px;
            border-radius: 16px;
        }

        .inspection-table-card {
            border-radius: 16px;
        }

        .inspection-filter-actions {
            grid-template-columns: 1fr;
        }

        .inspection-card-header {
            padding: 15px;
        }

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

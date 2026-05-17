@extends('layouts.app')

@section('title', 'Provincial KPI Wise Data')

@section('content')
@php
    $summaryData = $summary ?? [];
    $filters = $filters ?? [];
    $selectedPeriod = $filters['period'] ?? 'last_week';
    $perPage = (int) ($filters['per_page'] ?? 10);
    $allowedPerPage = [10, 20, 25, 50];
    if (! in_array($perPage, $allowedPerPage, true)) {
        $perPage = 10;
    }

    $statusMeta = function ($status) {
        return match ($status) {
            'approved' => ['label' => 'Approved', 'class' => 'status-approved', 'icon' => 'bi-check2-circle'],
            'reviewed' => ['label' => 'Reviewed', 'class' => 'status-reviewed', 'icon' => 'bi-eye'],
            'rejected' => ['label' => 'Rejected', 'class' => 'status-rejected', 'icon' => 'bi-x-circle'],
            default => ['label' => 'Submitted', 'class' => 'status-submitted', 'icon' => 'bi-send-check'],
        };
    };

    $categoryName = function ($row) {
        return $row->kpiCategory->name ?? $row->kpi_category_name ?? 'KPI Category';
    };

    $categoryIcon = function ($name) {
        $name = strtolower($name ?? '');
        return match (true) {
            str_contains($name, 'roti') || str_contains($name, 'bread') || str_contains($name, 'price') => 'bi-cash-coin',
            str_contains($name, 'school') || str_contains($name, 'education') => 'bi-mortarboard',
            str_contains($name, 'health') || str_contains($name, 'hospital') => 'bi-hospital',
            str_contains($name, 'water') || str_contains($name, 'filtration') => 'bi-droplet',
            str_contains($name, 'manhole') || str_contains($name, 'sewer') || str_contains($name, 'drain') => 'bi-cone-striped',
            str_contains($name, 'street') || str_contains($name, 'light') => 'bi-lightbulb',
            str_contains($name, 'dog') => 'bi-shield-exclamation',
            str_contains($name, 'marriage') => 'bi-building-check',
            str_contains($name, 'park') || str_contains($name, 'green') => 'bi-tree',
            str_contains($name, 'bus') => 'bi-bus-front',
            default => 'bi-grid-3x3-gap',
        };
    };

    $metricCards = function ($row) {
        return [
            [
                'label' => 'Total Inspections',
                'value' => (int) ($row->total_inspections ?? $row->total_records ?? 0),
                'class' => 'metric-total',
                'icon' => 'bi-clipboard-data',
                'help' => 'All reported records under this KPI.',
            ],
            [
                'label' => 'Submitted',
                'value' => (int) ($row->submitted_count ?? 0),
                'class' => 'metric-submitted',
                'icon' => 'bi-send-check',
                'help' => 'Records submitted by field offices.',
            ],
            [
                'label' => 'Reviewed',
                'value' => (int) ($row->reviewed_count ?? 0),
                'class' => 'metric-reviewed',
                'icon' => 'bi-eye',
                'help' => 'Records reviewed by officer.',
            ],
            [
                'label' => 'Approved',
                'value' => (int) ($row->approved_count ?? 0),
                'class' => 'metric-approved',
                'icon' => 'bi-check2-circle',
                'help' => 'Verified and approved records.',
            ],
            [
                'label' => 'Rejected',
                'value' => (int) ($row->rejected_count ?? 0),
                'class' => 'metric-rejected',
                'icon' => 'bi-x-circle',
                'help' => 'Returned or rejected records.',
            ],
        ];
    };

    $approvalRate = function ($row) {
        $total = (int) ($row->total_inspections ?? 0);
        $approved = (int) ($row->approved_count ?? 0);
        return $total > 0 ? round(($approved / $total) * 100, 2) : 0;
    };
@endphp

<div class="page-title-bar provincial-title-bar">
    <div>
        <div class="eyebrow-title">
            <i class="bi bi-grid-1x2"></i>
            Provincial Report
        </div>
        <h1 class="page-title">Provincial KPI Wise Data</h1>
        <p class="page-subtitle">
            Punjab-wide KPI performance summary in old PPMF-style category blocks with improved filters and dashboard cards.
        </p>
    </div>

    <div class="page-title-actions">
        @if(\Illuminate\Support\Facades\Route::has('kpi.reporting-status'))
            <a href="{{ route('kpi.reporting-status') }}" class="btn-gov btn-gov-outline">
                <i class="bi bi-list-check"></i>
                Reporting Status
            </a>
        @endif

        @if(\Illuminate\Support\Facades\Route::has('kpi.graphical-report'))
            <a href="{{ route('kpi.graphical-report') }}" class="btn-gov btn-gov-primary">
                <i class="bi bi-bar-chart-line"></i>
                Graphical Report
            </a>
        @endif
    </div>
</div>

<div class="provincial-summary-grid">
    <div class="provincial-summary-card">
        <div class="summary-icon summary-green"><i class="bi bi-database-check"></i></div>
        <div>
            <span>Total KPI Records</span>
            <strong>{{ number_format($summaryData['total_records'] ?? 0) }}</strong>
        </div>
    </div>

    <div class="provincial-summary-card">
        <div class="summary-icon summary-blue"><i class="bi bi-buildings"></i></div>
        <div>
            <span>Districts Covered</span>
            <strong>{{ number_format($summaryData['districts_covered'] ?? 0) }}</strong>
        </div>
    </div>

    <div class="provincial-summary-card">
        <div class="summary-icon summary-cyan"><i class="bi bi-grid-3x3-gap"></i></div>
        <div>
            <span>KPI Categories</span>
            <strong>{{ number_format($summaryData['categories_covered'] ?? 0) }}</strong>
        </div>
    </div>

    <div class="provincial-summary-card">
        <div class="summary-icon summary-amber"><i class="bi bi-check2-circle"></i></div>
        <div>
            <span>Approval Rate</span>
            <strong>{{ number_format($summaryData['approval_rate'] ?? 0, 2) }}%</strong>
        </div>
    </div>
</div>

<div class="card-ppmf provincial-filter-card mb-4">
    <div class="card-ppmf-header">
        <div>
            <div class="card-ppmf-title">
                <i class="bi bi-funnel"></i>
                Report Filters
            </div>
            <p class="card-subtitle mb-0">Filter the provincial KPI report by period, district, tehsil, category, status, or custom date.</p>
        </div>
    </div>

    <div class="card-ppmf-body">
        <form method="GET" action="{{ route('kpi.provincial-data') }}" id="provincialFilterForm">
            <div class="period-tabs" role="group" aria-label="Report period">
                @foreach([
                    'current_week' => 'Current Week',
                    'last_week' => 'Last Week',
                    'last_four_weeks' => 'Last Four Weeks',
                    'custom' => 'Custom Date',
                ] as $periodKey => $periodLabel)
                    <label class="period-tab {{ $selectedPeriod === $periodKey ? 'active' : '' }}">
                        <input type="radio" name="period" value="{{ $periodKey }}" {{ $selectedPeriod === $periodKey ? 'checked' : '' }}>
                        <span>{{ $periodLabel }}</span>
                    </label>
                @endforeach
            </div>

            <div class="row g-3 align-items-end mt-1">
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">District</label>
                    <select name="district_id" class="form-select auto-submit-filter">
                        <option value="">All Districts</option>
                        @foreach ($districts as $district)
                            <option value="{{ $district->id }}" {{ ($filters['district_id'] ?? '') == $district->id ? 'selected' : '' }}>
                                {{ $district->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">Tehsil</label>
                    <select name="tehsil_id" class="form-select auto-submit-filter">
                        <option value="">All Tehsils</option>
                        @foreach ($tehsils as $tehsil)
                            <option value="{{ $tehsil->id }}" {{ ($filters['tehsil_id'] ?? '') == $tehsil->id ? 'selected' : '' }}>
                                {{ $tehsil->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">KPI Category</label>
                    <select name="kpi_category_id" class="form-select auto-submit-filter">
                        <option value="">All KPI Categories</option>
                        @foreach ($kpiCategories as $category)
                            <option value="{{ $category->id }}" {{ ($filters['kpi_category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select auto-submit-filter">
                        <option value="">All Statuses</option>
                        @foreach(['submitted' => 'Submitted', 'reviewed' => 'Reviewed', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $statusKey => $statusLabel)
                            <option value="{{ $statusKey }}" {{ ($filters['status'] ?? '') === $statusKey ? 'selected' : '' }}>
                                {{ $statusLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label">Year</label>
                    <select name="year" class="form-select auto-submit-filter">
                        <option value="">All Years</option>
                        @for ($year = now()->year + 1; $year >= 2020; $year--)
                            <option value="{{ $year }}" {{ ($filters['year'] ?? '') == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endfor
                    </select>
                </div>

                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
                </div>

                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
                </div>

                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label">Per Page</label>
                    <select name="per_page" class="form-select auto-submit-filter">
                        @foreach($allowedPerPage as $pageOption)
                            <option value="{{ $pageOption }}" {{ $perPage === $pageOption ? 'selected' : '' }}>{{ $pageOption }} records</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-4 col-lg-8 col-md-12">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Search title, address, remarks">
                </div>

                <div class="col-12">
                    <div class="filter-actions-row">
                        <button type="submit" class="btn-gov btn-gov-primary">
                            <i class="bi bi-funnel"></i>
                            Apply Filter
                        </button>
                        <a href="{{ route('kpi.provincial-data') }}" class="btn-gov btn-gov-outline">
                            <i class="bi bi-arrow-clockwise"></i>
                            Reset
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="provincial-section-head">
    <div>
        <h2>Provincial KPI Wise Data</h2>
        <p>Category-wise cards similar to old PPMF report, with live status counts from inspection records.</p>
    </div>
    <div class="record-count-pill">
        {{ method_exists($provincialData, 'total') ? number_format($provincialData->total()) : number_format($provincialData->count()) }} KPI groups
    </div>
</div>

<div class="provincial-kpi-stack">
    @forelse($provincialData as $index => $row)
        @php
            $name = $categoryName($row);
            $rate = $approvalRate($row);
            $lastReported = $row->last_reported_at ?? null;
        @endphp

        <section class="provincial-kpi-section">
            <div class="kpi-section-header">
                <div class="kpi-section-title-wrap">
                    <div class="kpi-section-icon"><i class="bi {{ $categoryIcon($name) }}"></i></div>
                    <div>
                        <h3>{{ $name }}</h3>
                        <p>
                            Approval rate: <strong>{{ number_format($rate, 2) }}%</strong>
                            @if($lastReported)
                                <span>• Last reported {{ \Carbon\Carbon::parse($lastReported)->format('d M, Y h:i A') }}</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="approval-meter" title="Approved / Total">
                    <div class="approval-meter-bar" style="width: {{ min(100, max(0, $rate)) }}%;"></div>
                </div>
            </div>

            <div class="kpi-metric-grid">
                @foreach($metricCards($row) as $metric)
                    <div class="kpi-metric-card {{ $metric['class'] }}">
                        <div class="metric-icon"><i class="bi {{ $metric['icon'] }}"></i></div>
                        <div>
                            <strong>{{ number_format($metric['value']) }}</strong>
                            <span>{{ $metric['label'] }}</span>
                            <small>{{ $metric['help'] }}</small>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @empty
        <div class="card-ppmf empty-provincial-card">
            <i class="bi bi-database-x"></i>
            <h5>No Provincial KPI Data Found</h5>
            <p>No KPI data is available for the selected filters.</p>
        </div>
    @endforelse
</div>

@if(method_exists($provincialData, 'links'))
    <div class="card-ppmf provincial-pagination-card">
        <div class="pagination-info">
            Showing
            <strong>{{ $provincialData->firstItem() ?? 0 }}</strong>
            to
            <strong>{{ $provincialData->lastItem() ?? 0 }}</strong>
            of
            <strong>{{ number_format($provincialData->total()) }}</strong>
            KPI groups
        </div>

        <div class="pagination-controls">
            {{ $provincialData->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endif

@endsection

@push('styles')
<style>
    .provincial-title-bar {
        align-items: flex-start;
    }

    .eyebrow-title {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 8px;
        color: #057a55;
        font-size: 12px;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .provincial-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .provincial-summary-card {
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        gap: 16px;
        min-height: 112px;
        padding: 20px;
        background: #ffffff;
        border: 1px solid #dbe7df;
        border-radius: 22px;
        box-shadow: 0 16px 34px rgba(15, 23, 42, .07);
    }

    .provincial-summary-card::before {
        content: "";
        position: absolute;
        inset: 0 0 auto 0;
        height: 4px;
        background: linear-gradient(90deg, #065f46, #16a34a);
    }

    .summary-icon {
        width: 54px;
        height: 54px;
        border-radius: 18px;
        display: grid;
        place-items: center;
        font-size: 23px;
        flex-shrink: 0;
    }

    .summary-green { background: #dcfce7; color: #047857; }
    .summary-blue { background: #dbeafe; color: #1d4ed8; }
    .summary-cyan { background: #e0f2fe; color: #0369a1; }
    .summary-amber { background: #fef3c7; color: #b45309; }

    .provincial-summary-card span {
        display: block;
        color: #718096;
        font-size: 13px;
        font-weight: 800;
        margin-bottom: 4px;
    }

    .provincial-summary-card strong {
        display: block;
        color: #07152b;
        font-size: 31px;
        font-weight: 950;
        line-height: 1;
        letter-spacing: -.04em;
    }

    .provincial-filter-card .form-label {
        font-weight: 800;
        color: #0f172a;
        font-size: 13px;
    }

    .period-tabs {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 10px;
    }

    .period-tab {
        cursor: pointer;
        border: 1px solid #d7e3dc;
        border-radius: 16px;
        background: #f8fafc;
        color: #475569;
        padding: 13px 15px;
        font-weight: 850;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: .2s ease;
    }

    .period-tab input { display: none; }

    .period-tab.active,
    .period-tab:hover {
        background: #065f46;
        border-color: #065f46;
        color: #ffffff;
        box-shadow: 0 12px 24px rgba(6, 95, 70, .18);
    }

    .filter-actions-row {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        padding-top: 4px;
    }

    .provincial-section-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 18px;
        margin: 10px 0 16px;
    }

    .provincial-section-head h2 {
        margin: 0;
        color: #07152b;
        font-size: 23px;
        font-weight: 950;
    }

    .provincial-section-head p {
        margin: 5px 0 0;
        color: #64748b;
        font-weight: 650;
    }

    .record-count-pill {
        padding: 9px 14px;
        border-radius: 999px;
        background: #ecfdf5;
        border: 1px solid #bbf7d0;
        color: #047857;
        font-weight: 900;
        white-space: nowrap;
    }

    .provincial-kpi-stack {
        display: grid;
        gap: 18px;
    }

    .provincial-kpi-section {
        background: #ffffff;
        border: 1px solid #dbe7df;
        border-radius: 24px;
        padding: 20px;
        box-shadow: 0 18px 38px rgba(15, 23, 42, .07);
    }

    .kpi-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding-bottom: 16px;
        margin-bottom: 16px;
        border-bottom: 1px solid #e2e8f0;
    }

    .kpi-section-title-wrap {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
    }

    .kpi-section-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        display: grid;
        place-items: center;
        flex-shrink: 0;
        background: linear-gradient(135deg, #065f46, #16a34a);
        color: #ffffff;
        font-size: 22px;
        box-shadow: 0 12px 24px rgba(6, 95, 70, .2);
    }

    .kpi-section-header h3 {
        margin: 0;
        color: #064e3b;
        font-size: 17px;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: .035em;
    }

    .kpi-section-header p {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 13px;
        font-weight: 650;
    }

    .approval-meter {
        width: 230px;
        height: 11px;
        border-radius: 999px;
        overflow: hidden;
        background: #e2e8f0;
        flex-shrink: 0;
    }

    .approval-meter-bar {
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #16a34a, #22c55e);
    }

    .kpi-metric-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 14px;
    }

    .kpi-metric-card {
        position: relative;
        overflow: hidden;
        min-height: 128px;
        padding: 16px;
        border-radius: 18px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        display: flex;
        gap: 12px;
        align-items: flex-start;
        box-shadow: 0 10px 24px rgba(15, 23, 42, .05);
    }

    .kpi-metric-card::before {
        content: "";
        position: absolute;
        inset: 0 0 auto 0;
        height: 4px;
        background: #0f766e;
    }

    .metric-icon {
        width: 38px;
        height: 38px;
        border-radius: 14px;
        display: grid;
        place-items: center;
        flex-shrink: 0;
        background: #ecfdf5;
        color: #047857;
        font-size: 18px;
    }

    .kpi-metric-card strong {
        display: block;
        color: #07152b;
        font-size: 28px;
        line-height: 1;
        font-weight: 950;
        letter-spacing: -.04em;
        margin-bottom: 6px;
    }

    .kpi-metric-card span {
        display: block;
        color: #0f172a;
        font-size: 13px;
        font-weight: 900;
        margin-bottom: 4px;
    }

    .kpi-metric-card small {
        display: block;
        color: #64748b;
        font-size: 11.5px;
        line-height: 1.35;
        font-weight: 600;
    }

    .metric-approved::before { background: #16a34a; }
    .metric-reviewed::before { background: #0284c7; }
    .metric-submitted::before { background: #f59e0b; }
    .metric-rejected::before { background: #dc2626; }
    .metric-total::before { background: #065f46; }

    .metric-approved .metric-icon { background: #dcfce7; color: #15803d; }
    .metric-reviewed .metric-icon { background: #e0f2fe; color: #0369a1; }
    .metric-submitted .metric-icon { background: #fef3c7; color: #b45309; }
    .metric-rejected .metric-icon { background: #fee2e2; color: #b91c1c; }

    .empty-provincial-card {
        padding: 48px 20px;
        text-align: center;
        color: #64748b;
    }

    .empty-provincial-card i {
        display: block;
        font-size: 44px;
        color: #94a3b8;
        margin-bottom: 10px;
    }

    .provincial-pagination-card {
        margin-top: 18px;
        padding: 16px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }

    .pagination-info {
        color: #64748b;
        font-size: 13px;
        font-weight: 750;
    }

    .pagination-controls nav { margin: 0; }
    .pagination-controls .pagination {
        margin: 0;
        gap: 6px;
        flex-wrap: wrap;
    }

    .pagination-controls .page-link {
        border-radius: 10px !important;
        border: 1px solid #cbd5e1;
        color: #047857;
        min-width: 38px;
        height: 36px;
        line-height: 22px;
        font-size: 13px;
        font-weight: 850;
        box-shadow: none !important;
    }

    .pagination-controls .page-item.active .page-link {
        background: #065f46;
        border-color: #065f46;
        color: #ffffff;
    }

    .pagination-controls .page-item.disabled .page-link {
        color: #94a3b8;
        background: #f8fafc;
    }

    @media (max-width: 1399px) {
        .provincial-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .kpi-metric-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }

    @media (max-width: 991px) {
        .period-tabs { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .kpi-section-header { align-items: flex-start; flex-direction: column; }
        .approval-meter { width: 100%; }
        .provincial-pagination-card { align-items: flex-start; flex-direction: column; }
    }

    @media (max-width: 767px) {
        .provincial-summary-grid,
        .kpi-metric-grid,
        .period-tabs { grid-template-columns: 1fr; }
        .filter-actions-row { flex-direction: column; }
        .filter-actions-row .btn-gov { width: 100%; justify-content: center; }
        .provincial-section-head { align-items: flex-start; flex-direction: column; }
        .provincial-kpi-section { padding: 16px; }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('provincialFilterForm');
        if (!form) return;

        form.querySelectorAll('.auto-submit-filter').forEach(function (element) {
            element.addEventListener('change', function () {
                form.submit();
            });
        });

        form.querySelectorAll('input[name="period"]').forEach(function (element) {
            element.addEventListener('change', function () {
                form.submit();
            });
        });
    });
</script>
@endpush

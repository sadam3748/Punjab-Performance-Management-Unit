@extends('layouts.app')

@section('title', 'District Baseline Data Report')

@section('content')

@php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;

    $currentPerPage = (int) request('per_page', $filters['per_page'] ?? 10);
    if (! in_array($currentPerPage, [10, 20, 25, 50], true)) {
        $currentPerPage = 10;
    }

    $normalizeBaselineData = function ($rawData) {
        if (is_string($rawData)) {
            $decoded = json_decode($rawData, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($rawData) ? $rawData : [];
    };

    $readableLabel = function ($key) {
        $key = (string) $key;
        $key = str_replace(['total_number_of_', 'total_no_of_', 'total_no_', 'number_of_', 'no_of_'], '', $key);
        $key = str_replace(['_in_the_district', '_district'], '', $key);
        return Str::headline($key);
    };

    $isUsefulField = function ($key, $value) {
        if (is_int($key) || ctype_digit((string) $key)) {
            return false;
        }

        if (is_null($value) || $value === '') {
            return false;
        }

        if (is_array($value) && count($value) === 0) {
            return false;
        }

        $keyText = strtolower((string) $key);
        $valueText = strtolower(is_scalar($value) ? (string) $value : '');

        if ($keyText === 'notes' && str_contains($valueText, 'dummy baseline')) {
            return false;
        }

        return true;
    };

    $formatBaselineValue = function ($value) {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_numeric($value)) {
            $number = (float) $value;
            return number_format($number, floor($number) == $number ? 0 : 2);
        }

        if (is_array($value)) {
            $items = collect($value)
                ->filter(fn ($item) => is_scalar($item) && trim((string) $item) !== '')
                ->take(3)
                ->map(fn ($item) => (string) $item)
                ->values()
                ->all();

            return count($items) ? implode(', ', $items) : 'Multiple values';
        }

        return Str::limit((string) $value, 45);
    };

    $baselineDetailRoute = function ($baseline) {
        if (Route::has('baseline.show')) {
            return route('baseline.show', $baseline->id);
        }

        if (Route::has('baseline.index')) {
            return route('baseline.index');
        }

        return '#';
    };
@endphp

<div class="page-title-bar baseline-title-bar">
    <div>
        <span class="baseline-eyebrow">PPMF Baseline Monitoring</span>
        <h1 class="page-title">District Baseline Data Report</h1>
        <p class="page-subtitle">
            District-wise baseline indicators by KPI category, year and administrative scope.
        </p>
    </div>

    <div class="page-title-actions">
        @if(Route::has('kpi.provincial-data'))
            <a href="{{ route('kpi.provincial-data') }}" class="btn-gov btn-gov-outline">
                <i class="bi bi-table"></i>
                Provincial KPI Data
            </a>
        @endif

        @if(Route::has('kpi.graphical-report'))
            <a href="{{ route('kpi.graphical-report') }}" class="btn-gov btn-gov-primary">
                <i class="bi bi-bar-chart-line"></i>
                Graphical Report
            </a>
        @endif
    </div>
</div>

<div class="baseline-stat-grid">
    <div class="baseline-stat-card stat-green">
        <div class="baseline-stat-icon"><i class="bi bi-database-check"></i></div>
        <div>
            <span>Total Records</span>
            <strong>{{ number_format($summary['total_baseline_records'] ?? (method_exists($baselineData, 'total') ? $baselineData->total() : $baselineData->count())) }}</strong>
            <small>Baseline entries</small>
        </div>
    </div>

    <div class="baseline-stat-card stat-blue">
        <div class="baseline-stat-icon"><i class="bi bi-geo-alt"></i></div>
        <div>
            <span>Districts Covered</span>
            <strong>{{ number_format($summary['total_districts_covered'] ?? $summary['districts_covered'] ?? 0) }}</strong>
            <small>Administrative coverage</small>
        </div>
    </div>

    <div class="baseline-stat-card stat-amber">
        <div class="baseline-stat-icon"><i class="bi bi-grid-3x3-gap"></i></div>
        <div>
            <span>KPI Categories</span>
            <strong>{{ number_format($summary['total_categories_covered'] ?? $summary['categories_covered'] ?? 0) }}</strong>
            <small>Baseline sectors</small>
        </div>
    </div>

    <div class="baseline-stat-card stat-purple">
        <div class="baseline-stat-icon"><i class="bi bi-calendar2-check"></i></div>
        <div>
            <span>Selected Year</span>
            <strong>{{ $filters['year'] ?? request('year') ?? date('Y') }}</strong>
            <small>Reporting year</small>
        </div>
    </div>
</div>

<div class="baseline-filter-card">
    <div class="baseline-filter-head">
        <div>
            <h5><i class="bi bi-funnel"></i> Filter Baseline Records</h5>
            <p>Use filters to review district, KPI category and year-wise baseline information.</p>
        </div>
    </div>

    <form method="GET" action="{{ Route::has('kpi.district-baseline') ? route('kpi.district-baseline') : url()->current() }}">
        <div class="row g-3 align-items-end">
            <div class="col-xl-2 col-lg-4 col-md-6">
                <label class="form-label">District</label>
                <select name="district_id" class="form-select baseline-filter-input">
                    <option value="">All Districts</option>
                    @foreach($districts ?? [] as $district)
                        <option value="{{ $district->id }}" {{ (string)($filters['district_id'] ?? request('district_id')) === (string)$district->id ? 'selected' : '' }}>
                            {{ $district->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-xl-3 col-lg-4 col-md-6">
                <label class="form-label">KPI Category</label>
                <select name="kpi_category_id" class="form-select baseline-filter-input">
                    <option value="">All KPI Categories</option>
                    @foreach($kpiCategories ?? [] as $category)
                        <option value="{{ $category->id }}" {{ (string)($filters['kpi_category_id'] ?? request('kpi_category_id')) === (string)$category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-xl-2 col-lg-4 col-md-6">
                <label class="form-label">Year</label>
                <select name="year" class="form-select baseline-filter-input">
                    <option value="">All Years</option>
                    @for ($year = date('Y') + 1; $year >= 2020; $year--)
                        <option value="{{ $year }}" {{ (string)($filters['year'] ?? request('year')) === (string)$year ? 'selected' : '' }}>
                            {{ $year }}
                        </option>
                    @endfor
                </select>
            </div>

            <div class="col-xl-2 col-lg-4 col-md-6">
                <label class="form-label">Per Page</label>
                <select name="per_page" class="form-select baseline-filter-input" onchange="this.form.submit()">
                    @foreach([10, 20, 25, 50] as $size)
                        <option value="{{ $size }}" {{ $currentPerPage === $size ? 'selected' : '' }}>{{ $size }} records</option>
                    @endforeach
                </select>
            </div>

            <div class="col-xl-3 col-lg-8 col-md-12">
                <label class="form-label">Search</label>
                <div class="baseline-search-wrap">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search" value="{{ $filters['search'] ?? request('search') }}" class="form-control baseline-filter-input" placeholder="Search district or KPI category">
                </div>
            </div>

            <div class="col-12">
                <div class="baseline-filter-actions">
                    <button type="submit" class="btn-gov btn-gov-primary">
                        <i class="bi bi-check2-circle"></i>
                        Apply Filter
                    </button>

                    <a href="{{ Route::has('kpi.district-baseline') ? route('kpi.district-baseline') : url()->current() }}" class="btn-gov btn-gov-outline">
                        <i class="bi bi-arrow-clockwise"></i>
                        Reset
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="baseline-table-card">
    <div class="baseline-table-head">
        <div>
            <h5><i class="bi bi-list-check"></i> District Baseline Records</h5>
            <p>
                Showing concise baseline summary. Click Detail to view all KPI-specific fields.
            </p>
        </div>

        <div class="baseline-record-count">
            {{ method_exists($baselineData, 'total') ? number_format($baselineData->total()) : number_format($baselineData->count()) }} records
        </div>
    </div>

    <div class="table-responsive">
        <table class="baseline-table">
            <thead>
                <tr>
                    <th style="width: 70px;">Sr.</th>
                    <th>District</th>
                    <th>KPI Category</th>
                    <th style="width: 110px;">Year</th>
                    <th>Baseline Summary</th>
                    <th style="width: 170px;">Last Updated</th>
                    <th class="text-center" style="width: 95px;">Detail</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($baselineData as $index => $baseline)
                    @php
                        $data = $normalizeBaselineData($baseline->baseline_data ?? $baseline->data ?? []);
                        $readableData = collect($data)->filter(fn ($value, $key) => $isUsefulField($key, $value));
                        $previewData = $readableData->take(3);
                        $remainingCount = max($readableData->count() - $previewData->count(), 0);
                    @endphp
                    <tr>
                        <td>
                            <span class="baseline-sr-pill">{{ method_exists($baselineData, 'firstItem') ? $baselineData->firstItem() + $index : $index + 1 }}</span>
                        </td>
                        <td>
                            <div class="district-cell">
                                <span class="district-avatar">{{ Str::substr($baseline->district->name ?? 'N', 0, 1) }}</span>
                                <div>
                                    <strong>{{ $baseline->district->name ?? 'N/A' }}</strong>
                                    <small>District baseline profile</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="kpi-chip">{{ $baseline->kpiCategory->name ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span class="year-chip">{{ $baseline->year ?? 'N/A' }}</span>
                        </td>
                        <td>
                            @if($previewData->count() > 0)
                                <div class="summary-preview-grid">
                                    @foreach($previewData as $key => $value)
                                        <div class="summary-preview-item">
                                            <span>{{ $readableLabel($key) }}</span>
                                            <strong>{{ $formatBaselineValue($value) }}</strong>
                                        </div>
                                    @endforeach

                                    @if($remainingCount > 0)
                                        <div class="summary-more-pill">+ {{ $remainingCount }} more field(s) in detail</div>
                                    @endif
                                </div>
                            @else
                                <span class="baseline-empty-text">No readable baseline fields</span>
                            @endif
                        </td>
                        <td>
                            <div class="updated-cell">
                                <i class="bi bi-clock-history"></i>
                                <span>{{ $baseline->updated_at ? $baseline->updated_at->format('d M, Y') : 'N/A' }}</span>
                                <small>{{ $baseline->updated_at ? $baseline->updated_at->format('h:i A') : '' }}</small>
                            </div>
                        </td>
                        <td class="text-center">
                            <a href="{{ $baselineDetailRoute($baseline) }}" class="baseline-detail-btn" title="View complete baseline detail">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="baseline-empty-state">
                                <i class="bi bi-database-x"></i>
                                <h5>No District Baseline Data Found</h5>
                                <p>No baseline records are available for the selected filters.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($baselineData, 'links'))
        <div class="baseline-pagination-bar">
            <div class="pagination-meta">
                @if($baselineData->total() > 0)
                    Showing {{ number_format($baselineData->firstItem()) }} to {{ number_format($baselineData->lastItem()) }} of {{ number_format($baselineData->total()) }} records
                @else
                    Showing 0 records
                @endif
            </div>
            <div class="baseline-pagination-wrap">
                {{ $baselineData->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>

@endsection

@push('styles')
<style>
    .baseline-eyebrow {
        display: inline-flex;
        align-items: center;
        margin-bottom: 4px;
        color: #166534;
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .08em;
    }

    .baseline-stat-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .baseline-stat-card {
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        gap: 14px;
        min-height: 112px;
        padding: 18px;
        border-radius: 20px;
        background: #fff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .07);
    }

    .baseline-stat-card::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        height: 4px;
        background: #166534;
    }

    .baseline-stat-card.stat-blue::before { background: #0369a1; }
    .baseline-stat-card.stat-amber::before { background: #b45309; }
    .baseline-stat-card.stat-purple::before { background: #6d28d9; }

    .baseline-stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 16px;
        display: grid;
        place-items: center;
        color: #fff;
        font-size: 22px;
        background: linear-gradient(135deg, #14532d, #16a34a);
        flex-shrink: 0;
    }

    .stat-blue .baseline-stat-icon { background: linear-gradient(135deg, #075985, #0ea5e9); }
    .stat-amber .baseline-stat-icon { background: linear-gradient(135deg, #92400e, #f59e0b); }
    .stat-purple .baseline-stat-icon { background: linear-gradient(135deg, #4c1d95, #8b5cf6); }

    .baseline-stat-card span,
    .baseline-stat-card small {
        display: block;
        color: #64748b;
        font-size: 12px;
        font-weight: 800;
    }

    .baseline-stat-card strong {
        display: block;
        color: #0f172a;
        font-size: 28px;
        line-height: 1.1;
        font-weight: 900;
        letter-spacing: -.03em;
        margin: 2px 0;
    }

    .baseline-filter-card,
    .baseline-table-card {
        margin-bottom: 20px;
        border-radius: 22px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 14px 34px rgba(15, 23, 42, .07);
    }

    .baseline-filter-card { padding: 20px; }

    .baseline-filter-head,
    .baseline-table-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 16px;
    }

    .baseline-filter-head h5,
    .baseline-table-head h5 {
        margin: 0;
        color: #0f172a;
        font-size: 17px;
        font-weight: 900;
    }

    .baseline-filter-head h5 i,
    .baseline-table-head h5 i { color: #166534; }

    .baseline-filter-head p,
    .baseline-table-head p {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 13px;
        font-weight: 600;
    }

    .baseline-filter-input {
        border-radius: 12px;
        border-color: #cbd5e1;
        font-size: 13px;
        font-weight: 700;
        min-height: 42px;
    }

    .baseline-search-wrap { position: relative; }
    .baseline-search-wrap i {
        position: absolute;
        left: 13px;
        top: 50%;
        transform: translateY(-50%);
        color: #64748b;
        z-index: 2;
    }
    .baseline-search-wrap input { padding-left: 38px; }

    .baseline-filter-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        flex-wrap: wrap;
    }

    .baseline-table-head {
        padding: 18px 20px 0;
    }

    .baseline-record-count {
        padding: 7px 12px;
        border-radius: 999px;
        color: #166534;
        background: #dcfce7;
        font-size: 12px;
        font-weight: 900;
        white-space: nowrap;
    }

    .baseline-table {
        width: 100%;
        margin: 0;
        border-collapse: separate;
        border-spacing: 0;
    }

    .baseline-table thead th {
        padding: 13px 16px;
        background: linear-gradient(135deg, #14532d, #166534);
        color: #ffffff;
        font-size: 12px;
        font-weight: 900;
        letter-spacing: .03em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .baseline-table tbody td {
        padding: 15px 16px;
        border-bottom: 1px solid #e2e8f0;
        color: #334155;
        font-size: 13px;
        vertical-align: middle;
    }

    .baseline-table tbody tr:hover { background: #f8fafc; }

    .baseline-sr-pill,
    .year-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 30px;
        min-width: 38px;
        padding: 0 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 900;
        background: #f1f5f9;
        color: #334155;
    }

    .year-chip { background: #e0f2fe; color: #0369a1; }

    .district-cell {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 180px;
    }

    .district-avatar {
        width: 38px;
        height: 38px;
        border-radius: 14px;
        display: grid;
        place-items: center;
        background: linear-gradient(135deg, #14532d, #16a34a);
        color: #fff;
        font-size: 16px;
        font-weight: 900;
        flex-shrink: 0;
    }

    .district-cell strong { display: block; color: #0f172a; font-size: 14px; }
    .district-cell small { display: block; color: #64748b; font-size: 11.5px; font-weight: 700; }

    .kpi-chip {
        display: inline-flex;
        max-width: 270px;
        padding: 7px 10px;
        border-radius: 999px;
        background: #ecfdf5;
        color: #166534;
        font-size: 12px;
        font-weight: 900;
        white-space: normal;
        line-height: 1.25;
    }

    .summary-preview-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(120px, 1fr));
        gap: 8px;
        min-width: 420px;
    }

    .summary-preview-item {
        padding: 9px 10px;
        border-radius: 14px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }

    .summary-preview-item span {
        display: block;
        color: #64748b;
        font-size: 11px;
        font-weight: 800;
        line-height: 1.25;
    }

    .summary-preview-item strong {
        display: block;
        margin-top: 3px;
        color: #0f172a;
        font-size: 13px;
        font-weight: 900;
        line-height: 1.25;
    }

    .summary-more-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 8px 10px;
        border-radius: 14px;
        color: #92400e;
        background: #fef3c7;
        border: 1px solid #fde68a;
        font-size: 12px;
        font-weight: 900;
    }

    .updated-cell {
        display: grid;
        grid-template-columns: 18px 1fr;
        column-gap: 7px;
        align-items: center;
        color: #334155;
        min-width: 130px;
    }

    .updated-cell i { color: #166534; }
    .updated-cell span { font-weight: 900; }
    .updated-cell small { grid-column: 2; color: #64748b; font-size: 11.5px; font-weight: 700; }

    .baseline-detail-btn {
        width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 13px;
        color: #ffffff;
        background: linear-gradient(135deg, #14532d, #16a34a);
        box-shadow: 0 8px 18px rgba(22, 101, 52, .20);
        text-decoration: none;
    }

    .baseline-detail-btn:hover { color: #fff; transform: translateY(-1px); }

    .baseline-empty-text { color: #94a3b8; font-weight: 800; }

    .baseline-empty-state {
        padding: 46px 16px;
        text-align: center;
        color: #64748b;
    }

    .baseline-empty-state i { font-size: 38px; color: #94a3b8; }
    .baseline-empty-state h5 { margin: 10px 0 4px; color: #0f172a; font-weight: 900; }
    .baseline-empty-state p { margin: 0; }

    .baseline-pagination-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 14px 18px;
        border-top: 1px solid #e2e8f0;
        background: #f8fafc;
        border-radius: 0 0 22px 22px;
    }

    .pagination-meta {
        color: #64748b;
        font-size: 12.5px;
        font-weight: 800;
    }

    .baseline-pagination-wrap nav { margin: 0; }
    .baseline-pagination-wrap .pagination {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
        margin: 0;
    }

    .baseline-pagination-wrap .page-link {
        min-width: 38px;
        height: 36px;
        padding: 0 12px;
        border-radius: 11px !important;
        border: 1px solid #cbd5e1;
        color: #166534;
        background: #ffffff;
        font-size: 13px;
        font-weight: 900;
        line-height: 34px;
        text-align: center;
        box-shadow: none;
    }

    .baseline-pagination-wrap .page-item.active .page-link {
        background: #166534;
        border-color: #166534;
        color: #ffffff;
    }

    .baseline-pagination-wrap .page-item.disabled .page-link {
        color: #94a3b8;
        background: #f1f5f9;
        border-color: #e2e8f0;
    }

    @media (max-width: 1199px) {
        .baseline-stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .summary-preview-grid { grid-template-columns: repeat(2, minmax(120px, 1fr)); min-width: 340px; }
    }

    @media (max-width: 767px) {
        .baseline-stat-grid { grid-template-columns: 1fr; }
        .baseline-pagination-bar { align-items: flex-start; flex-direction: column; }
        .baseline-pagination-wrap .pagination { justify-content: flex-start; }
        .summary-preview-grid { grid-template-columns: 1fr; min-width: 260px; }
        .baseline-filter-actions { justify-content: flex-start; }
    }
</style>
@endpush

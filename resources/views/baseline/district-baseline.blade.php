@extends('layouts.app')

@section('title', 'District Baseline Data Report')

@section('content')

@php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;

    $filters = $filters ?? request()->all();

    $currentPerPage = (int) request('per_page', $filters['per_page'] ?? 10);
    if (! in_array($currentPerPage, [10, 20, 25, 50], true)) {
        $currentPerPage = 10;
    }

    if (method_exists($baselineData, 'appends')) {
        $baselineData->appends(request()->query());
    }

    $baselineRoute = Route::has('kpi.district-baseline')
        ? route('kpi.district-baseline')
        : url()->current();

    $normaliseBaselineData = function ($rawData) {
        if (is_string($rawData)) {
            $decoded = json_decode($rawData, true);
            return is_array($decoded) ? $decoded : [];
        }

        if ($rawData instanceof \Illuminate\Support\Collection) {
            return $rawData->toArray();
        }

        return is_array($rawData) ? $rawData : [];
    };

    $readableLabel = function ($key) {
        $key = (string) $key;

        $replace = [
            'total_number_of_' => 'total_',
            'total_no_of_' => 'total_',
            'total_no_' => 'total_',
            'number_of_' => '',
            'no_of_' => '',
            '_in_the_district' => '',
            '_district' => '',
            'uc' => 'UC',
            'ucs' => 'UCs',
            'bhu' => 'BHU',
            'bhus' => 'BHUs',
            'rhc' => 'RHC',
            'rhcs' => 'RHCs',
        ];

        $label = str_replace(array_keys($replace), array_values($replace), strtolower($key));
        $label = Str::headline($label);

        return str_replace([
            'Uc ', 'Ucs', 'Bhus', 'Rhcs', 'Bhu ', 'Rhc ', 'Ppmf'
        ], [
            'UC ', 'UCs', 'BHUs', 'RHCs', 'BHU ', 'RHC ', 'PPMF'
        ], $label);
    };

    $isReadableField = function ($key, $value) {
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

        return Str::limit((string) $value, 42);
    };

    $getUsefulFields = function ($rawData) use ($normaliseBaselineData, $isReadableField) {
        return collect($normaliseBaselineData($rawData))
            ->filter(fn ($value, $key) => $isReadableField($key, $value))
            ->all();
    };

    $getImportantPreviewFields = function ($rawData) use ($getUsefulFields) {
        $fields = collect($getUsefulFields($rawData));

        $priorityWords = [
            'total',
            'population',
            'uc',
            'compliant',
            'non_compliant',
            'functional',
            'school',
            'health',
            'road',
            'manhole',
            'plant',
            'parks',
            'street',
        ];

        $priority = $fields->filter(function ($value, $key) use ($priorityWords) {
            $keyText = strtolower((string) $key);

            foreach ($priorityWords as $word) {
                if (str_contains($keyText, $word)) {
                    return true;
                }
            }

            return false;
        });

        return ($priority->count() ? $priority : $fields)->take(4)->all();
    };

    $detailUrl = function ($baseline) {
        return Route::has('baseline.show')
            ? route('baseline.show', $baseline->id)
            : '#';
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
            <small>District baseline entries</small>
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
            <small>Reporting period</small>
        </div>
    </div>
</div>

<div class="baseline-filter-card">
    <div class="baseline-filter-head">
        <div>
            <h5><i class="bi bi-funnel"></i> Filter Baseline Records</h5>
            <p>Review district baseline information by district, KPI category, year and search keyword.</p>
        </div>

        <div class="baseline-perpage-top">
            <span>Per Page</span>
            <strong>{{ $currentPerPage }}</strong>
        </div>
    </div>

    <form method="GET" action="{{ $baselineRoute }}" id="baselineFilterForm">
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
                <select name="per_page" class="form-select baseline-filter-input per-page-select" onchange="this.form.submit()">
                    @foreach([10, 20, 25, 50] as $size)
                        <option value="{{ $size }}" {{ $currentPerPage === $size ? 'selected' : '' }}>
                            {{ $size }} records
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-xl-3 col-lg-8 col-md-12">
                <label class="form-label">Search</label>
                <div class="baseline-search-wrap">
                    <i class="bi bi-search"></i>
                    <input
                        type="text"
                        name="search"
                        value="{{ $filters['search'] ?? request('search') }}"
                        class="form-control baseline-filter-input"
                        placeholder="Search district or KPI category">
                </div>
            </div>

            <div class="col-12">
                <div class="baseline-filter-actions">
                    <button type="submit" class="btn-gov btn-gov-primary">
                        <i class="bi bi-check2-circle"></i>
                        Apply Filter
                    </button>

                    <a href="{{ $baselineRoute }}" class="btn-gov btn-gov-outline">
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
            <p>Concise baseline summary. Open detail to view all KPI-specific field values.</p>
        </div>

        <div class="baseline-record-count">
            {{ method_exists($baselineData, 'total') ? number_format($baselineData->total()) : number_format($baselineData->count()) }} records
        </div>
    </div>

    <div class="table-responsive baseline-table-responsive">
        <table class="baseline-table">
            <thead>
                <tr>
                    <th style="width: 72px;">Sr.</th>
                    <th style="min-width: 165px;">District</th>
                    <th style="min-width: 250px;">KPI Category</th>
                    <th style="width: 105px;">Year</th>
                    <th style="min-width: 360px;">Baseline Summary</th>
                    <th style="width: 165px;">Last Updated</th>
                    <th class="text-center" style="width: 95px;">Detail</th>
                </tr>
            </thead>

            <tbody>
                @forelse($baselineData as $index => $baseline)
                    @php
                        $fields = $getUsefulFields($baseline->baseline_data ?? []);
                        $previewFields = $getImportantPreviewFields($baseline->baseline_data ?? []);
                        $hiddenCount = max(count($fields) - count($previewFields), 0);
                    @endphp

                    <tr>
                        <td>
                            <span class="serial-badge">
                                {{ method_exists($baselineData, 'firstItem') && $baselineData->firstItem() ? $baselineData->firstItem() + $index : $index + 1 }}
                            </span>
                        </td>

                        <td>
                            <div class="district-cell">
                                <div class="district-icon"><i class="bi bi-geo-alt"></i></div>
                                <div>
                                    <strong>{{ $baseline->district->name ?? 'N/A' }}</strong>
                                    <small>District</small>
                                </div>
                            </div>
                        </td>

                        <td>
                            <span class="type-chip">
                                <i class="bi bi-tag"></i>
                                {{ $baseline->kpiCategory->name ?? 'N/A' }}
                            </span>
                        </td>

                        <td>
                            <span class="year-chip">
                                <i class="bi bi-calendar3"></i>
                                {{ $baseline->year ?? 'N/A' }}
                            </span>
                        </td>

                        <td>
                            @if(count($previewFields))
                                <div class="baseline-preview">
                                    @foreach($previewFields as $key => $value)
                                        <div class="baseline-preview-item">
                                            <span>{{ $readableLabel($key) }}</span>
                                            <strong>{{ $formatBaselineValue($value) }}</strong>
                                        </div>
                                    @endforeach

                                    @if($hiddenCount > 0)
                                        <small>
                                            <i class="bi bi-plus-circle"></i>
                                            {{ $hiddenCount }} more field{{ $hiddenCount > 1 ? 's' : '' }} available in detail
                                        </small>
                                    @endif
                                </div>
                            @else
                                <span class="empty-text">No readable baseline fields</span>
                            @endif
                        </td>

                        <td>
                            @if($baseline->updated_at)
                                <div class="date-cell">
                                    <strong>{{ $baseline->updated_at->format('d M, Y') }}</strong>
                                    <small>{{ $baseline->updated_at->format('h:i A') }}</small>
                                </div>
                            @else
                                <span class="empty-text">N/A</span>
                            @endif
                        </td>

                        <td class="text-center">
                            @if(Route::has('baseline.show'))
                                <a href="{{ $detailUrl($baseline) }}" class="btn-icon-action" title="View Baseline Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                            @else
                                <span class="empty-text">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
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

    @if(method_exists($baselineData, 'links') && $baselineData->total() > 0)
        <div class="baseline-pagination-bar">
            <div class="pagination-info">
                Showing
                <strong>{{ number_format($baselineData->firstItem() ?? 0) }}</strong>
                to
                <strong>{{ number_format($baselineData->lastItem() ?? 0) }}</strong>
                of
                <strong>{{ number_format($baselineData->total()) }}</strong>
                records
            </div>

            <div class="pagination-actions">
                <div class="pagination-per-page">
                    <form method="GET" action="{{ $baselineRoute }}">
                        @foreach(request()->except(['per_page', 'page']) as $key => $value)
                            @if(is_array($value))
                                @foreach($value as $childValue)
                                    <input type="hidden" name="{{ $key }}[]" value="{{ $childValue }}">
                                @endforeach
                            @else
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach

                        <label>Rows</label>
                        <select name="per_page" onchange="this.form.submit()">
                            @foreach([10, 20, 25, 50] as $size)
                                <option value="{{ $size }}" {{ $currentPerPage === $size ? 'selected' : '' }}>
                                    {{ $size }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>

                <nav class="custom-pagination" aria-label="Baseline pagination">
                    <a
                        class="page-btn {{ $baselineData->onFirstPage() ? 'disabled' : '' }}"
                        href="{{ $baselineData->onFirstPage() ? '#' : $baselineData->previousPageUrl() }}"
                    >
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </a>

                    @php
                        $currentPage = $baselineData->currentPage();
                        $lastPage = $baselineData->lastPage();
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($lastPage, $currentPage + 2);
                    @endphp

                    @if($startPage > 1)
                        <a class="page-number" href="{{ $baselineData->url(1) }}">1</a>
                        @if($startPage > 2)
                            <span class="page-ellipsis">...</span>
                        @endif
                    @endif

                    @for($page = $startPage; $page <= $endPage; $page++)
                        <a
                            class="page-number {{ $page === $currentPage ? 'active' : '' }}"
                            href="{{ $baselineData->url($page) }}"
                        >
                            {{ $page }}
                        </a>
                    @endfor

                    @if($endPage < $lastPage)
                        @if($endPage < $lastPage - 1)
                            <span class="page-ellipsis">...</span>
                        @endif
                        <a class="page-number" href="{{ $baselineData->url($lastPage) }}">{{ $lastPage }}</a>
                    @endif

                    <a
                        class="page-btn {{ $baselineData->hasMorePages() ? '' : 'disabled' }}"
                        href="{{ $baselineData->hasMorePages() ? $baselineData->nextPageUrl() : '#' }}"
                    >
                        Next
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </nav>
            </div>
        </div>
    @endif
</div>

@endsection

@push('styles')
<style>
    :root {
        --baseline-green: #14532d;
        --baseline-green-2: #166534;
        --baseline-green-3: #dcfce7;
        --baseline-blue: #075985;
        --baseline-amber: #92400e;
        --baseline-purple: #5b21b6;
        --baseline-red: #991b1b;
        --baseline-slate: #0f172a;
        --baseline-muted: #64748b;
        --baseline-border: #dbe4dc;
        --baseline-soft: #f8fafc;
    }

    .baseline-title-bar {
        background:
            linear-gradient(135deg, rgba(20, 83, 45, 0.08), rgba(255, 255, 255, 0.96)),
            #ffffff;
        border: 1px solid var(--baseline-border);
        border-radius: 22px;
        padding: 18px 20px;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.07);
        margin-bottom: 18px;
    }

    .baseline-eyebrow {
        display: inline-flex;
        align-items: center;
        margin-bottom: 4px;
        padding: 4px 10px;
        border-radius: 999px;
        background: var(--baseline-green-3);
        color: var(--baseline-green);
        font-size: 11px;
        font-weight: 900;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .baseline-stat-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 18px;
    }

    .baseline-stat-card {
        display: flex;
        gap: 14px;
        align-items: center;
        min-height: 112px;
        padding: 18px;
        border-radius: 22px;
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.07);
        position: relative;
        overflow: hidden;
    }

    .baseline-stat-card::before {
        content: "";
        position: absolute;
        inset: 0 0 auto 0;
        height: 4px;
        background: var(--baseline-green);
    }

    .baseline-stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        display: grid;
        place-items: center;
        color: #ffffff;
        font-size: 22px;
        flex-shrink: 0;
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.12);
    }

    .baseline-stat-card span {
        display: block;
        color: var(--baseline-muted);
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.055em;
    }

    .baseline-stat-card strong {
        display: block;
        margin-top: 3px;
        color: var(--baseline-slate);
        font-size: 28px;
        font-weight: 950;
        line-height: 1;
        letter-spacing: -0.04em;
    }

    .baseline-stat-card small {
        display: block;
        margin-top: 6px;
        color: #64748b;
        font-weight: 700;
        font-size: 12px;
    }

    .stat-green .baseline-stat-icon,
    .stat-green::before {
        background: linear-gradient(135deg, #14532d, #22c55e);
    }

    .stat-blue .baseline-stat-icon,
    .stat-blue::before {
        background: linear-gradient(135deg, #075985, #38bdf8);
    }

    .stat-amber .baseline-stat-icon,
    .stat-amber::before {
        background: linear-gradient(135deg, #92400e, #f59e0b);
    }

    .stat-purple .baseline-stat-icon,
    .stat-purple::before {
        background: linear-gradient(135deg, #5b21b6, #a78bfa);
    }

    .baseline-filter-card,
    .baseline-table-card {
        background: #ffffff;
        border: 1px solid var(--baseline-border);
        border-radius: 22px;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.07);
        margin-bottom: 18px;
        overflow: hidden;
    }

    .baseline-filter-card {
        padding: 18px;
    }

    .baseline-filter-head,
    .baseline-table-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 16px;
    }

    .baseline-table-head {
        padding: 18px 20px;
        margin-bottom: 0;
        border-bottom: 1px solid var(--baseline-border);
        background:
            linear-gradient(135deg, rgba(20, 83, 45, 0.06), rgba(248, 250, 252, 0.95));
    }

    .baseline-filter-head h5,
    .baseline-table-head h5 {
        margin: 0;
        color: var(--baseline-slate);
        font-size: 16px;
        font-weight: 900;
    }

    .baseline-filter-head h5 i,
    .baseline-table-head h5 i {
        color: var(--baseline-green);
        margin-right: 6px;
    }

    .baseline-filter-head p,
    .baseline-table-head p {
        margin: 4px 0 0;
        color: var(--baseline-muted);
        font-size: 13px;
        font-weight: 600;
    }

    .baseline-perpage-top,
    .baseline-record-count {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
        border-radius: 999px;
        background: var(--baseline-green-3);
        color: var(--baseline-green);
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 900;
    }

    .baseline-perpage-top span {
        color: #166534;
    }

    .baseline-perpage-top strong {
        font-size: 14px;
    }

    .baseline-filter-card .form-label {
        margin-bottom: 6px;
        color: #334155;
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.045em;
    }

    .baseline-filter-input {
        min-height: 42px;
        border-radius: 13px;
        border-color: #cbd5e1;
        color: var(--baseline-slate);
        font-size: 13px;
        font-weight: 700;
        box-shadow: none;
    }

    .baseline-filter-input:focus {
        border-color: #22c55e;
        box-shadow: 0 0 0 0.2rem rgba(34, 197, 94, 0.12);
    }

    .per-page-select {
        color: var(--baseline-green);
        font-weight: 900;
        background-color: #f0fdf4;
        border-color: #bbf7d0;
    }

    .baseline-search-wrap {
        position: relative;
    }

    .baseline-search-wrap i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #64748b;
    }

    .baseline-search-wrap input {
        padding-left: 40px;
    }

    .baseline-filter-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .baseline-table-responsive {
        min-height: 240px;
    }

    .baseline-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin: 0;
    }

    .baseline-table thead th {
        position: sticky;
        top: 0;
        z-index: 1;
        background: linear-gradient(180deg, var(--gov-green-dark) 0%, var(--gov-green) 100%);
        color: #ffffff;
        padding: 13px 14px;
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.045em;
        border: 0;
        border-bottom: 2px solid var(--gold);
        white-space: nowrap;
    }

    .baseline-table tbody td {
        padding: 14px;
        border-bottom: 1px solid #e2e8f0;
        color: #334155;
        font-size: 13px;
        vertical-align: middle;
        background: #ffffff;
    }

    .baseline-table tbody tr:nth-child(even) td {
        background: #fbfdff;
    }

    .baseline-table tbody tr:hover td {
        background: #f0fdf4;
    }

    .serial-badge {
        display: inline-grid;
        place-items: center;
        width: 34px;
        height: 34px;
        border-radius: 12px;
        background: #f1f5f9;
        color: var(--baseline-slate);
        font-weight: 900;
    }

    .district-cell {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 150px;
    }

    .district-icon {
        width: 38px;
        height: 38px;
        border-radius: 13px;
        display: grid;
        place-items: center;
        background: #dcfce7;
        color: var(--baseline-green);
        font-size: 18px;
        flex-shrink: 0;
    }

    .district-cell strong {
        display: block;
        color: var(--baseline-slate);
        font-size: 14px;
        font-weight: 900;
    }

    .district-cell small,
    .date-cell small {
        display: block;
        color: var(--baseline-muted);
        font-size: 11.5px;
        font-weight: 700;
        margin-top: 2px;
    }

    .type-chip,
    .year-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 900;
        line-height: 1.35;
    }

    .type-chip {
        padding: 7px 10px;
        background: rgba(20, 83, 45, 0.09);
        color: var(--baseline-green);
        max-width: 330px;
        white-space: normal;
    }

    .year-chip {
        padding: 7px 11px;
        background: rgba(7, 89, 133, 0.10);
        color: var(--baseline-blue);
        white-space: nowrap;
    }

    .baseline-preview {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 7px;
        min-width: 330px;
    }

    .baseline-preview-item {
        padding: 9px 10px;
        border: 1px solid #e2e8f0;
        border-radius: 13px;
        background: #f8fafc;
    }

    .baseline-preview-item span {
        display: block;
        color: var(--baseline-muted);
        font-size: 11.5px;
        font-weight: 800;
        line-height: 1.25;
    }

    .baseline-preview-item strong {
        display: block;
        margin-top: 3px;
        color: var(--baseline-slate);
        font-size: 14px;
        font-weight: 950;
        line-height: 1.25;
        word-break: break-word;
    }

    .baseline-preview small {
        grid-column: 1 / -1;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        color: var(--baseline-green);
        font-size: 12px;
        font-weight: 900;
        margin-top: 2px;
    }

    .date-cell strong {
        display: block;
        color: var(--baseline-slate);
        font-weight: 900;
        white-space: nowrap;
    }

    .btn-icon-action {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--baseline-green-3);
        color: var(--baseline-green);
        text-decoration: none;
        transition: 0.2s ease;
        font-size: 17px;
    }

    .btn-icon-action:hover {
        background: var(--baseline-green);
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 8px 20px rgba(20, 83, 45, 0.22);
    }

    .empty-text {
        color: #94a3b8;
        font-weight: 700;
    }

    .baseline-empty-state {
        display: inline-grid;
        justify-items: center;
        gap: 8px;
        color: #64748b;
    }

    .baseline-empty-state i {
        width: 56px;
        height: 56px;
        border-radius: 18px;
        display: grid;
        place-items: center;
        background: #f1f5f9;
        color: #64748b;
        font-size: 28px;
    }

    .baseline-empty-state h5 {
        margin: 0;
        color: var(--baseline-slate);
        font-weight: 900;
    }

    .baseline-empty-state p {
        margin: 0;
        font-weight: 600;
    }

    .baseline-pagination-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 15px 18px;
        border-top: 1px solid var(--baseline-border);
        background: #ffffff;
    }

    .pagination-info {
        color: var(--baseline-muted);
        font-size: 13px;
        font-weight: 700;
        white-space: nowrap;
    }

    .pagination-info strong {
        color: var(--baseline-slate);
        font-weight: 950;
    }

    .pagination-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .pagination-per-page form {
        display: flex;
        align-items: center;
        gap: 7px;
        margin: 0;
    }

    .pagination-per-page label {
        margin: 0;
        color: var(--baseline-muted);
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .pagination-per-page select {
        height: 36px;
        border-radius: 11px;
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        color: var(--baseline-green);
        font-size: 13px;
        font-weight: 900;
        padding: 0 28px 0 10px;
        outline: none;
    }

    .custom-pagination {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
        justify-content: flex-end;
        margin: 0;
    }

    .page-btn,
    .page-number,
    .page-ellipsis {
        min-width: 36px;
        height: 36px;
        padding: 0 11px;
        border-radius: 11px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: var(--baseline-green);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 900;
        line-height: 1;
    }

    .page-btn {
        min-width: 92px;
    }

    .page-number.active {
        background: var(--baseline-green);
        border-color: var(--baseline-green);
        color: #ffffff;
        box-shadow: 0 8px 18px rgba(20, 83, 45, 0.18);
    }

    .page-btn:hover,
    .page-number:hover {
        background: #ecfdf5;
        border-color: #86efac;
        color: var(--baseline-green);
    }

    .page-btn.disabled,
    .page-btn.disabled:hover {
        pointer-events: none;
        color: #94a3b8;
        background: #f1f5f9;
        border-color: #e2e8f0;
        box-shadow: none;
    }

    .page-ellipsis {
        border-color: transparent;
        background: transparent;
        color: #94a3b8;
        padding: 0 2px;
        min-width: 22px;
    }

    @media (max-width: 1199px) {
        .baseline-stat-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .baseline-pagination-bar {
            align-items: flex-start;
            flex-direction: column;
        }

        .pagination-actions {
            width: 100%;
            justify-content: space-between;
        }
    }

    @media (max-width: 767px) {
        .baseline-title-bar,
        .baseline-filter-head,
        .baseline-table-head {
            flex-direction: column;
            align-items: stretch;
        }

        .baseline-stat-grid {
            grid-template-columns: 1fr;
        }

        .baseline-preview {
            grid-template-columns: 1fr;
            min-width: 260px;
        }

        .pagination-actions {
            align-items: flex-start;
            flex-direction: column;
        }

        .custom-pagination {
            width: 100%;
            justify-content: flex-start;
        }

        .page-btn {
            min-width: 84px;
        }
    }
</style>
@endpush

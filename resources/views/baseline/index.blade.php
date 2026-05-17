@extends('layouts.app')

@section('title', 'District Baseline Data')

@section('content')

<div class="page-title-bar">
    <div>
        <h1 class="page-title">District Baseline Data</h1>
        <p class="page-subtitle">
            District-wise baseline summary by KPI category and year.
        </p>
    </div>

    <div class="page-title-actions">
        <a href="{{ route('baseline.assets') }}" class="btn-gov btn-gov-outline">
            <i class="bi bi-box-seam"></i>
            Baseline Assets
        </a>

        <a href="{{ route('baseline.create') }}" class="btn-gov btn-gov-primary">
            <i class="bi bi-plus-circle"></i>
            Add Baseline Data
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill me-1"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-3 mb-4">

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="stat-card-ppmf">
            <div class="stat-icon-ppmf primary">
                <i class="bi bi-database"></i>
            </div>
            <div>
                <span>Total Records</span>
                <strong>{{ number_format($summary['total_baseline_records'] ?? 0) }}</strong>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="stat-card-ppmf">
            <div class="stat-icon-ppmf success">
                <i class="bi bi-building"></i>
            </div>
            <div>
                <span>Districts Covered</span>
                <strong>{{ number_format($summary['districts_covered'] ?? 0) }}</strong>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="stat-card-ppmf">
            <div class="stat-icon-ppmf info">
                <i class="bi bi-grid"></i>
            </div>
            <div>
                <span>KPI Categories</span>
                <strong>{{ number_format($summary['categories_covered'] ?? 0) }}</strong>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="stat-card-ppmf">
            <div class="stat-icon-ppmf warning">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div>
                <span>Selected Year</span>
                <strong>{{ $filters['year'] ?? date('Y') }}</strong>
            </div>
        </div>
    </div>

</div>

<div class="card-ppmf mb-4">
    <div class="card-ppmf-header">
        <div class="card-ppmf-title">
            <i class="bi bi-funnel"></i>
            Filters
        </div>
    </div>

    <div class="card-ppmf-body">
        <form method="GET" action="{{ route('baseline.index') }}">
            <input type="hidden" name="per_page" value="{{ $filters['per_page'] ?? request('per_page', 10) }}">
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

                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label">Year</label>
                    <select name="year" class="form-select">
                        <option value="">All Years</option>
                        @for($year = date('Y'); $year >= 2020; $year--)
                            <option value="{{ $year }}"
                                {{ ($filters['year'] ?? '') == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label">Search</label>
                    <input
                        type="text"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        class="form-control"
                        placeholder="Search..."
                    >
                </div>

                <div class="col-xl-2 col-lg-4 col-md-6">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn-gov btn-gov-primary flex-fill">
                            <i class="bi bi-search"></i>
                            Apply
                        </button>

                        <a href="{{ route('baseline.index') }}" class="btn-gov btn-gov-outline flex-fill">
                            Reset
                        </a>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<div class="card-ppmf">
    <div class="card-ppmf-header">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 w-100">
            <div>
            <div class="card-ppmf-title">
                <i class="bi bi-table"></i>
                District Baseline Records
            </div>
            <p class="card-subtitle mb-0">
                Total records:
                {{ method_exists($baselineData, 'total') ? number_format($baselineData->total()) : number_format($baselineData->count()) }}
            </p>
            </div>

            @if(method_exists($baselineData, 'links'))
                <form method="GET" action="{{ route('baseline.index') }}" class="d-flex align-items-center gap-2 ms-auto">
                    <input type="hidden" name="district_id" value="{{ $filters['district_id'] ?? '' }}">
                    <input type="hidden" name="kpi_category_id" value="{{ $filters['kpi_category_id'] ?? '' }}">
                    <input type="hidden" name="year" value="{{ $filters['year'] ?? '' }}">
                    <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">

                    <label class="form-label mb-0 text-nowrap" for="perPageSelect">Per Page</label>
                    <select
                        id="perPageSelect"
                        name="per_page"
                        class="form-select"
                        style="width: 110px;"
                        onchange="this.form.submit()"
                    >
                        @php
                            $currentPerPage = (int) ($filters['per_page'] ?? request('per_page', 10));
                        @endphp
                        @foreach([10, 20, 25, 50] as $size)
                            <option value="{{ $size }}" {{ $currentPerPage === $size ? 'selected' : '' }}>
                                {{ $size }}
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>
    </div>

    <div class="card-ppmf-body p-0">
        <div class="table-responsive">
            <table class="table-ppmf">
                <thead>
                    <tr>
                        <th>Sr.</th>
                        <th>District</th>
                        <th>KPI Category</th>
                        <th>Year</th>
                        <th>Baseline Summary</th>
                        <th>Created By</th>
                        <th>Updated By</th>
                        <th>Updated At</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($baselineData as $index => $baseline)
                        @php
                            $data = $baseline->baseline_data ?? [];
                        @endphp

                        <tr>
                            <td>
                                {{ method_exists($baselineData, 'firstItem') ? $baselineData->firstItem() + $index : $index + 1 }}
                            </td>

                            <td>
                                <strong>{{ $baseline->district->name ?? 'N/A' }}</strong>
                            </td>

                            <td>
                                <span class="badge-ppmf info">
                                    {{ $baseline->kpiCategory->name ?? 'N/A' }}
                                </span>
                            </td>

                            <td>
                                <strong>{{ $baseline->year ?? 'N/A' }}</strong>
                            </td>

                            <td>
                                @if(is_array($data) && count($data) > 0)
                                    <div class="baseline-preview">
                                        @foreach(array_slice($data, 0, 3) as $key => $value)
                                            <div>
                                                <span>{{ ucwords(str_replace('_', ' ', $key)) }}:</span>
                                                <strong>
                                                    @if(is_array($value))
                                                        {{ implode(', ', array_slice($value, 0, 2)) }}
                                                    @elseif(is_bool($value))
                                                        {{ $value ? 'Yes' : 'No' }}
                                                    @else
                                                        {{ \Illuminate\Support\Str::limit((string) $value, 35) }}
                                                    @endif
                                                </strong>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted">No JSON data</span>
                                @endif
                            </td>

                            <td>{{ $baseline->creator->username ?? $baseline->creator->name ?? 'N/A' }}</td>

                            <td>{{ $baseline->updater->username ?? $baseline->updater->name ?? 'N/A' }}</td>

                            <td>
                                {{ $baseline->updated_at ? $baseline->updated_at->format('d M, Y h:i A') : 'N/A' }}
                            </td>

                            <td class="text-center">
                                <a href="{{ route('baseline.show', $baseline->id) }}" class="btn-icon-action" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>

                                <a href="{{ route('baseline.edit', $baseline->id) }}" class="btn-icon-action" title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <div class="manual-box-ppmf">
                                    <i class="bi bi-database-x"></i>
                                    <h5>No Baseline Data Found</h5>
                                    <p>No district baseline data is available for selected filters.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>

    @if(method_exists($baselineData, 'links'))
        <div class="card-ppmf-body border-top">
            {{ $baselineData->appends(request()->query())->links() }}
        </div>
    @endif
</div>

@endsection

@push('styles')
<style>
    .baseline-preview {
        display: grid;
        gap: 3px;
        min-width: 260px;
    }

    .baseline-preview div {
        font-size: 12.5px;
        color: var(--text-secondary);
    }

    .baseline-preview span {
        font-weight: 700;
        color: var(--text-muted);
    }

    .baseline-preview strong {
        font-weight: 800;
        color: var(--text-primary);
    }

    .btn-icon-action {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(27, 107, 70, 0.10);
        color: var(--gov-green);
        text-decoration: none;
        margin: 0 2px;
    }

    .btn-icon-action:hover {
        background: var(--gov-green);
        color: #fff;
    }
</style>
@endpush

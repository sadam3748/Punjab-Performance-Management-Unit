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
        <form method="GET" action="{{ route('baseline.index') }}" id="baselineFilters">
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

<div id="baselineDynamic">
    @include('baseline.partials._baseline-table', ['baselineData' => $baselineData, 'filters' => $filters])
</div>

@endsection

@push('scripts')
<script>
    (function () {
        const $form = $('#baselineFilters');
        const $dynamic = $('#baselineDynamic');
        const dataUrl = @json(route('baseline.index.data'));

        let searchTimer = null;

        function loadBaseline(extraParams) {
            let params = $form.serialize();
            if (extraParams) params += (params.length ? '&' : '') + extraParams;

            $dynamic.css('opacity', '0.55');
            $.ajax({
                url: dataUrl,
                method: 'GET',
                data: params,
                success: function (resp) {
                    if (resp && resp.status === 'success') {
                        $dynamic.html(resp.html);
                        const url = new URL(window.location.href);
                        url.search = params;
                        window.history.pushState({}, '', url.toString());
                    }
                },
                complete: function () { $dynamic.css('opacity', '1'); }
            });
        }

        $form.on('change', 'select,input[type=\"date\"]', function () {
            loadBaseline('page=1');
        });

        $form.on('input', 'input[name=\"search\"]', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () { loadBaseline('page=1'); }, 350);
        });

        $form.on('submit', function (e) {
            e.preventDefault();
            loadBaseline('page=1');
        });

        $(document).on('click', '#baselineDynamic .pagination a', function (e) {
            const href = $(this).attr('href');
            if (!href) return;
            e.preventDefault();
            const url = new URL(href, window.location.origin);
            const page = url.searchParams.get('page') || '1';
            loadBaseline('page=' + encodeURIComponent(page));
        });

        $(document).on('change', '#baselineDynamic .baseline-per-page-form select[name=\"per_page\"]', function () {
            $form.find('input[name=\"per_page\"]').val($(this).val());
            loadBaseline('page=1');
        });
    })();
</script>
@endpush

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

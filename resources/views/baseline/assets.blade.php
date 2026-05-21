@extends('layouts.app')

@section('title', 'Baseline Assets')

@section('content')

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Baseline Assets</h1>
        <p class="page-subtitle">
            Asset-level baseline records such as filtration plants, schools, facilities and other KPI assets.
        </p>
    </div>

    <div class="page-title-actions">
        <a href="{{ route('baseline.index') }}" class="btn-gov btn-gov-outline">
            <i class="bi bi-database"></i>
            District Baseline
        </a>
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
        <form method="GET" action="{{ route('baseline.assets') }}" id="baselineAssetsFilters">
            <div class="row g-3 align-items-end">

                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label">Division</label>
                    <select name="division_id" class="form-select">
                        <option value="">All Divisions</option>
                        @foreach($divisions as $division)
                            <option value="{{ $division->id }}"
                                {{ ($filters['division_id'] ?? '') == $division->id ? 'selected' : '' }}>
                                {{ $division->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-2 col-lg-4 col-md-6">
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

                <div class="col-xl-2 col-lg-4 col-md-6">
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

                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label">KPI Category</label>
                    <select name="kpi_category_id" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($kpiCategories as $category)
                            <option value="{{ $category->id }}"
                                {{ ($filters['kpi_category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="functional" {{ ($filters['status'] ?? '') === 'functional' ? 'selected' : '' }}>Functional</option>
                        <option value="non_functional" {{ ($filters['status'] ?? '') === 'non_functional' ? 'selected' : '' }}>Non Functional</option>
                        <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label">Search</label>
                    <input
                        type="text"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        class="form-control"
                        placeholder="Search asset..."
                    >
                </div>

                <div class="col-12">
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="submit" class="btn-gov btn-gov-primary">
                            <i class="bi bi-search"></i>
                            Apply
                        </button>

                        <a href="{{ route('baseline.assets') }}" class="btn-gov btn-gov-outline">
                            Reset
                        </a>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<div id="baselineAssetsDynamic">
    @include('baseline.partials._assets-table', ['assets' => $assets, 'filters' => $filters])
</div>

@endsection

@push('scripts')
<script>
    (function () {
        const $form = $('#baselineAssetsFilters');
        const $dynamic = $('#baselineAssetsDynamic');
        const dataUrl = @json(route('baseline.assets.data'));

        let searchTimer = null;

        function loadAssets(extraParams) {
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
            loadAssets('page=1');
        });

        $form.on('input', 'input[name=\"search\"]', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () { loadAssets('page=1'); }, 350);
        });

        $form.on('submit', function (e) {
            e.preventDefault();
            loadAssets('page=1');
        });

        $(document).on('click', '#baselineAssetsDynamic .pagination a', function (e) {
            const href = $(this).attr('href');
            if (!href) return;
            e.preventDefault();
            const url = new URL(href, window.location.origin);
            const page = url.searchParams.get('page') || '1';
            loadAssets('page=' + encodeURIComponent(page));
        });
    })();
</script>
@endpush

@push('styles')
<style>
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
    }

    .btn-icon-action:hover {
        background: var(--gov-green);
        color: #fff;
    }
</style>
@endpush

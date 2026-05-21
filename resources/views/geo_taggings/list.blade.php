@extends('layouts.app')

@section('title', 'Geo Taggings')

@section('content')

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Geo Taggings</h1>
        <p class="page-subtitle">
            View geo-tagged assets, locations, institutions and field records by district, tehsil and type.
        </p>
    </div>

    <div class="page-title-actions">
        <a href="{{ route('geo-taggings.map') }}" class="btn-gov btn-gov-outline">
            <i class="bi bi-map"></i>
            Map View
        </a>

        <a href="{{ route('dashboard') }}" class="btn-gov btn-gov-primary">
            <i class="bi bi-speedometer2"></i>
            Dashboard
        </a>
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
        <form method="GET" action="{{ route('geo-taggings.list') }}" id="geoTaggingFilters">
            <input type="hidden" name="per_page" value="{{ $filters['per_page'] ?? request('per_page', 20) }}">
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
                        @foreach ($tehsils as $tehsil)
                            <option value="{{ $tehsil->id }}"
                                {{ ($filters['tehsil_id'] ?? '') == $tehsil->id ? 'selected' : '' }}>
                                {{ $tehsil->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">Geo Tagging Type</label>
                    <select name="geo_tagging_type_id" class="form-select">
                        <option value="">All Types</option>
                        @foreach ($geoTaggingTypes as $type)
                            <option value="{{ $type->id }}"
                                {{ ($filters['geo_tagging_type_id'] ?? '') == $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        @foreach (['submitted', 'verified', 'rejected'] as $status)
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
                    <label class="form-label">Search</label>
                    <input
                        type="text"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        class="form-control"
                        placeholder="Search name, address, remarks"
                    >
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn-gov btn-gov-primary flex-fill">
                            <i class="bi bi-funnel"></i>
                            Apply
                        </button>

                        <a href="{{ route('geo-taggings.list') }}" class="btn-gov btn-gov-outline flex-fill">
                            <i class="bi bi-arrow-clockwise"></i>
                            Reset
                        </a>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<div id="geoTaggingDynamic">
    @include('geo_taggings.partials._geo-tagging-table', ['geoTaggings' => $geoTaggings, 'filters' => $filters])
</div>

@endsection

@push('scripts')
<script>
    (function () {
        const $form = $('#geoTaggingFilters');
        const $dynamic = $('#geoTaggingDynamic');
        const dataUrl = @json(route('geo-taggings.data'));

        let searchTimer = null;

        function loadGeo(extraParams) {
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
            loadGeo('page=1');
        });

        $form.on('input', 'input[name=\"search\"]', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () { loadGeo('page=1'); }, 350);
        });

        $form.on('submit', function (e) {
            e.preventDefault();
            loadGeo('page=1');
        });

        $(document).on('click', '#geoTaggingDynamic .pagination a', function (e) {
            const href = $(this).attr('href');
            if (!href) return;
            e.preventDefault();
            const url = new URL(href, window.location.origin);
            const page = url.searchParams.get('page') || '1';
            loadGeo('page=' + encodeURIComponent(page));
        });

        $(document).on('change', '#geoTaggingDynamic .geo-per-page-form select[name=\"per_page\"]', function () {
            $form.find('input[name=\"per_page\"]').val($(this).val());
            loadGeo('page=1');
        });
    })();
</script>
@endpush

@push('styles')
<style>
    .geo-title {
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 2px;
    }

    .geo-address {
        font-size: 12.5px;
        color: var(--text-secondary);
        max-width: 360px;
    }

    .geo-meta {
        font-size: 11.5px;
        color: var(--text-muted);
        margin-top: 3px;
    }

    .type-chip {
        display: inline-flex;
        align-items: center;
        padding: 5px 9px;
        border-radius: 999px;
        background: rgba(27, 107, 70, 0.10);
        color: var(--gov-green);
        font-size: 11.5px;
        font-weight: 800;
        white-space: nowrap;
    }

    .location-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 8px;
        border-radius: 999px;
        background: rgba(14, 165, 233, 0.10);
        color: #0369a1;
        font-size: 11.5px;
        font-weight: 700;
        white-space: nowrap;
    }

    .location-chip:hover {
        background: rgba(14, 165, 233, 0.18);
        color: #075985;
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
        transition: 0.2s ease;
    }

    .btn-icon-action:hover {
        background: var(--gov-green);
        color: #fff;
    }

    .pagination-wrapper nav {
        display: flex;
        justify-content: flex-end;
    }
</style>
@endpush

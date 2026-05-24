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

        $(document).on('click', '#geoTaggingDynamic .geo-pagination-nav a, #geoTaggingDynamic .geo-page-number', function (e) {
            const href = $(this).attr('href');
            if (!href || href === 'javascript:void(0)') return;
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
    :root {
        --geo-border: rgba(15, 23, 42, 0.08);
        --geo-muted: #64748b;
        --geo-text: #0f172a;
    }

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

    .geo-table-card {
        background: #ffffff;
        border: 1px solid var(--geo-border);
        border-radius: 20px;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }

    .geo-card-header {
        padding: 18px 18px 14px;
        border-bottom: 1px solid #e2e8f0;
        background: linear-gradient(135deg, #ffffff, #f8fafc);
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
    }

    .geo-section-title {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--geo-text);
        font-size: 16px;
        font-weight: 900;
        letter-spacing: -0.01em;
        margin: 0;
    }

    .geo-section-title i {
        color: var(--gov-green);
    }

    .geo-section-subtitle {
        color: var(--geo-muted);
        font-size: 13px;
        font-weight: 600;
        margin: 0;
    }

    .geo-muted {
        color: #334155;
        font-size: 13px;
        font-weight: 800;
        white-space: nowrap;
    }

    .geo-table-wrap {
        width: 100%;
        overflow-x: hidden;
    }

    .geo-table {
        width: 100%;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0;
        margin: 0;
    }

    .geo-table thead th {
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

    .geo-table tbody td {
        padding: 14px;
        border-bottom: 1px solid #e5e7eb;
        color: #0f172a;
        font-size: 13px;
        vertical-align: middle;
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .geo-table tbody tr {
        transition: 0.18s ease;
    }

    .geo-table tbody tr:hover {
        transform: translateY(-1px);
        box-shadow: inset 4px 0 0 #16a34a;
        filter: brightness(0.995);
    }

    .geo-col-sr {
        width: 70px;
    }

    .geo-col-action {
        width: 90px;
    }

    .geo-pagination-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 14px 18px;
        border-top: 1px solid #e2e8f0;
        background: #ffffff;
    }

    .geo-pagination-summary-group {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 240px;
    }

    .geo-pagination-summary {
        color: #0f172a;
        font-size: 12.5px;
        font-weight: 800;
        white-space: nowrap;
    }

    .geo-pagination-per-page {
        color: #64748b;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .geo-pagination-nav {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .geo-page-link,
    .geo-page-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-width: 44px;
        height: 38px;
        padding: 0 12px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #0f172a;
        text-decoration: none;
        font-size: 12.5px;
        font-weight: 900;
        transition: 0.16s ease;
        white-space: nowrap;
    }

    .geo-page-link:hover,
    .geo-page-number:hover {
        border-color: #16a34a;
        color: #14532d;
        background: #f0fdf4;
    }

    .geo-page-number.active {
        background: #14532d;
        border-color: #14532d;
        color: #ffffff;
    }

    .geo-page-link.disabled {
        opacity: 0.55;
        pointer-events: none;
    }

    .geo-page-dots {
        padding: 0 4px;
        color: #64748b;
        font-weight: 900;
    }

    @media (max-width: 991px) {
        .geo-pagination-bar {
            align-items: flex-start;
            flex-direction: column;
        }

        .geo-pagination-summary {
            white-space: normal;
        }

        .geo-pagination-summary-group {
            min-width: 0;
        }

        .geo-pagination-nav {
            justify-content: flex-start;
            width: 100%;
        }
    }
</style>
@endpush

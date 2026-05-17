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
        <form method="GET" action="{{ route('geo-taggings.list') }}">
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

{{-- Geo Tagging Table --}}
<div class="card-ppmf">
    <div class="card-ppmf-header">
        <div>
            <div class="card-ppmf-title">
                <i class="bi bi-geo-alt"></i>
                Geo Tagging Records
            </div>

            <p class="card-subtitle mb-0">
                Total records:
                {{ method_exists($geoTaggings, 'total') ? number_format($geoTaggings->total()) : number_format($geoTaggings->count()) }}
            </p>
        </div>
    </div>

    <div class="card-ppmf-body p-0">
        <div class="table-responsive">
            <table class="table-ppmf">
                <thead>
                    <tr>
                        <th style="width: 70px;">Sr.</th>
                        <th>Name / Detail</th>
                        <th>Type</th>
                        <th>District</th>
                        <th>Tehsil</th>
                        <th>Performed By</th>
                        <th>Date & Time</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th class="text-center" style="width: 90px;">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($geoTaggings as $index => $geoTagging)
                        <tr>
                            <td>
                                {{ method_exists($geoTaggings, 'firstItem') ? $geoTaggings->firstItem() + $index : $index + 1 }}
                            </td>

                            <td>
                                <div class="geo-title">
                                    {{ $geoTagging->name ?? 'N/A' }}
                                </div>

                                <div class="geo-address">
                                    {{ $geoTagging->address ?? 'No address available' }}
                                </div>

                                @if(!empty($geoTagging->remarks))
                                    <div class="geo-meta">
                                        {{ \Illuminate\Support\Str::limit($geoTagging->remarks, 70) }}
                                    </div>
                                @endif
                            </td>

                            <td>
                                <span class="type-chip">
                                    {{ $geoTagging->geoTaggingType->name ?? 'N/A' }}
                                </span>
                            </td>

                            <td>{{ $geoTagging->district->name ?? 'N/A' }}</td>

                            <td>{{ $geoTagging->tehsil->name ?? 'N/A' }}</td>

                            <td>
                                <div class="fw-bold">
                                    {{ $geoTagging->performer->username ?? 'N/A' }}
                                </div>

                                <small class="text-muted">
                                    {{ $geoTagging->performer->designation ?? $geoTagging->performer->name ?? '' }}
                                </small>
                            </td>

                            <td>
                                @if ($geoTagging->tagged_at)
                                    {{ \Carbon\Carbon::parse($geoTagging->tagged_at)->format('d M, Y h:i A') }}
                                @else
                                    N/A
                                @endif
                            </td>

                            <td>
                                @if ($geoTagging->latitude && $geoTagging->longitude)
                                    <a
                                        href="https://www.google.com/maps?q={{ $geoTagging->latitude }},{{ $geoTagging->longitude }}"
                                        target="_blank"
                                        class="location-chip text-decoration-none"
                                    >
                                        <i class="bi bi-geo-alt"></i>
                                        {{ number_format($geoTagging->latitude, 5) }},
                                        {{ number_format($geoTagging->longitude, 5) }}
                                    </a>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>

                            <td>
                                <span class="badge-ppmf
                                    @if(($geoTagging->status ?? '') === 'verified') achieved
                                    @elseif(($geoTagging->status ?? '') === 'rejected') critical
                                    @else pending
                                    @endif
                                ">
                                    {{ ucfirst(str_replace('_', ' ', $geoTagging->status ?? 'Submitted')) }}
                                </span>
                            </td>

                            <td class="text-center">
                                <a
                                    href="{{ route('geo-taggings.show', $geoTagging->id) }}"
                                    class="btn-icon-action"
                                    title="View Detail"
                                >
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <div class="manual-box-ppmf">
                                    <i class="bi bi-geo"></i>
                                    <h5>No Geo Tagging Records Found</h5>
                                    <p>No records are available for the selected filters.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if (method_exists($geoTaggings, 'links'))
        <div class="card-ppmf-body border-top">
            <div class="pagination-wrapper">
                {{ $geoTaggings->links() }}
            </div>
        </div>
    @endif
</div>

@endsection

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

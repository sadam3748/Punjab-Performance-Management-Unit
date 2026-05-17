@extends('layouts.app')

@section('title', 'Geo Tagging Map')

@section('content')

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Geo Tagging Map</h1>
        <p class="page-subtitle">
            View geo-tagged assets and locations with district, tehsil, type and status filters.
        </p>
    </div>

    <div class="page-title-actions">
        <a href="{{ route('geo-taggings.list') }}" class="btn-gov btn-gov-outline">
            <i class="bi bi-list-ul"></i>
            List View
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
        <form method="GET" action="{{ route('geo-taggings.map') }}">
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

                <div class="col-xl-6 col-lg-4 col-md-12">
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="submit" class="btn-gov btn-gov-primary">
                            <i class="bi bi-funnel"></i>
                            Apply Filter
                        </button>

                        <a href="{{ route('geo-taggings.map') }}" class="btn-gov btn-gov-outline">
                            <i class="bi bi-arrow-clockwise"></i>
                            Reset
                        </a>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<div class="row g-4">

    {{-- Map Preview --}}
    <div class="col-xl-7 col-lg-12">
        <div class="card-ppmf h-100">
            <div class="card-ppmf-header">
                <div>
                    <div class="card-ppmf-title">
                        <i class="bi bi-map"></i>
                        Map Preview
                    </div>

                    <p class="card-subtitle mb-0">
                        {{ count($mapRecords) }} geo-tagged record(s) with location found.
                    </p>
                </div>
            </div>

            <div class="card-ppmf-body">
                <div class="geo-map-placeholder">
                    <div class="geo-map-grid"></div>

                    <div class="geo-map-content">
                        <i class="bi bi-geo-alt-fill"></i>
                        <h4>Geo Tagging Location Data</h4>
                        <p>
                            This page shows all geo-tagged records with latitude and longitude.
                            Each record can be opened directly in Google Maps.
                        </p>

                        @if(count($mapRecords) > 0)
                            @php
                                $firstRecord = $mapRecords->first();
                            @endphp

                            <a
                                href="https://www.google.com/maps?q={{ $firstRecord->latitude }},{{ $firstRecord->longitude }}"
                                target="_blank"
                                class="btn-gov btn-gov-primary"
                            >
                                <i class="bi bi-box-arrow-up-right"></i>
                                Open First Location
                            </a>
                        @else
                            <span class="badge-ppmf pending">
                                No location data available
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Location List --}}
    <div class="col-xl-5 col-lg-12">
        <div class="card-ppmf h-100">
            <div class="card-ppmf-header">
                <div class="card-ppmf-title">
                    <i class="bi bi-pin-map"></i>
                    Geo Tagged Locations
                </div>
            </div>

            <div class="card-ppmf-body">
                <div class="geo-location-list">

                    @forelse ($mapRecords as $record)
                        <div class="geo-location-card">

                            <div class="geo-location-top">
                                <div>
                                    <h6>{{ $record->name ?? 'N/A' }}</h6>
                                    <p>{{ $record->geoTaggingType->name ?? 'N/A' }}</p>
                                </div>

                                <span class="badge-ppmf
                                    @if(($record->status ?? '') === 'verified') achieved
                                    @elseif(($record->status ?? '') === 'rejected') critical
                                    @else pending
                                    @endif
                                ">
                                    {{ ucfirst(str_replace('_', ' ', $record->status ?? 'Submitted')) }}
                                </span>
                            </div>

                            <div class="geo-location-address">
                                <i class="bi bi-geo"></i>
                                <span>{{ $record->address ?? 'No address available' }}</span>
                            </div>

                            <div class="geo-location-meta">
                                <div>
                                    <i class="bi bi-building"></i>
                                    {{ $record->district->name ?? 'N/A' }}
                                    @if($record->tehsil)
                                        / {{ $record->tehsil->name }}
                                    @endif
                                </div>

                                <div>
                                    <i class="bi bi-person"></i>
                                    {{ $record->performer->username ?? $record->performer->name ?? 'N/A' }}
                                </div>

                                <div>
                                    <i class="bi bi-calendar-event"></i>
                                    @if($record->tagged_at)
                                        {{ \Carbon\Carbon::parse($record->tagged_at)->format('d M, Y h:i A') }}
                                    @else
                                        N/A
                                    @endif
                                </div>

                                <div>
                                    <i class="bi bi-crosshair"></i>
                                    {{ $record->latitude }}, {{ $record->longitude }}
                                </div>
                            </div>

                            <div class="geo-location-actions">
                                <a
                                    href="{{ route('geo-taggings.show', $record->id) }}"
                                    class="btn-gov btn-gov-outline btn-gov-sm"
                                >
                                    <i class="bi bi-eye"></i>
                                    Detail
                                </a>

                                <a
                                    href="https://www.google.com/maps?q={{ $record->latitude }},{{ $record->longitude }}"
                                    target="_blank"
                                    class="btn-gov btn-gov-primary btn-gov-sm"
                                >
                                    <i class="bi bi-box-arrow-up-right"></i>
                                    Google Map
                                </a>
                            </div>

                        </div>
                    @empty
                        <div class="manual-box-ppmf">
                            <i class="bi bi-geo"></i>
                            <h5>No Geo Tagged Locations</h5>
                            <p>No records with latitude and longitude are available for the selected filters.</p>
                        </div>
                    @endforelse

                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('styles')
<style>
    .geo-map-placeholder {
        min-height: 540px;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-light);
        background:
            linear-gradient(135deg, rgba(27, 107, 70, .94), rgba(20, 83, 45, .9)),
            radial-gradient(circle at top left, rgba(255,255,255,.18), transparent 42%);
        position: relative;
        overflow: hidden;
        display: grid;
        place-items: center;
        text-align: center;
        padding: 32px;
    }

    .geo-map-grid {
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(255,255,255,.08) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,.08) 1px, transparent 1px);
        background-size: 42px 42px;
        opacity: .58;
    }

    .geo-map-content {
        position: relative;
        max-width: 460px;
        color: #fff;
    }

    .geo-map-content i {
        font-size: 56px;
        margin-bottom: 14px;
        display: inline-block;
    }

    .geo-map-content h4 {
        font-weight: 800;
        margin-bottom: 8px;
    }

    .geo-map-content p {
        opacity: .9;
        margin-bottom: 18px;
        line-height: 1.7;
    }

    .geo-location-list {
        max-height: 560px;
        overflow-y: auto;
        display: grid;
        gap: 12px;
        padding-right: 4px;
    }

    .geo-location-card {
        border: 1px solid var(--border-light);
        border-radius: var(--radius-md);
        padding: 14px;
        background: #fff;
        transition: .2s ease;
    }

    .geo-location-card:hover {
        border-color: rgba(27, 107, 70, .35);
        box-shadow: var(--shadow-sm);
    }

    .geo-location-top {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        align-items: flex-start;
        margin-bottom: 8px;
    }

    .geo-location-top h6 {
        font-size: 14px;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 3px;
    }

    .geo-location-top p {
        font-size: 12px;
        color: var(--text-muted);
        margin-bottom: 0;
    }

    .geo-location-address {
        display: flex;
        align-items: flex-start;
        gap: 7px;
        padding: 9px 10px;
        background: var(--bg);
        border-radius: var(--radius-sm);
        margin-bottom: 10px;
        font-size: 12.5px;
        color: var(--text-secondary);
    }

    .geo-location-address i {
        color: var(--gov-green);
        margin-top: 1px;
    }

    .geo-location-meta {
        display: grid;
        gap: 5px;
        margin-bottom: 12px;
    }

    .geo-location-meta div {
        font-size: 12.5px;
        color: var(--text-secondary);
        display: flex;
        align-items: center;
        gap: 7px;
    }

    .geo-location-meta i {
        color: var(--gov-green);
    }

    .geo-location-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    @media (max-width: 768px) {
        .geo-map-placeholder {
            min-height: 360px;
        }

        .geo-location-list {
            max-height: none;
        }
    }
</style>
@endpush

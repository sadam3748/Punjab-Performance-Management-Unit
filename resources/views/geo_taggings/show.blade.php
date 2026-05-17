@extends('layouts.app')

@section('title', 'Geo Tagging Detail')

@section('content')

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Geo Tagging Detail</h1>
        <p class="page-subtitle">
            Complete geo-tagging record with location, administrative area and performer information.
        </p>
    </div>

    <div class="page-title-actions">
        <a href="{{ route('geo-taggings.list') }}" class="btn-gov btn-gov-outline">
            <i class="bi bi-arrow-left"></i>
            Back to List
        </a>

        <a href="{{ route('geo-taggings.map') }}" class="btn-gov btn-gov-primary">
            <i class="bi bi-map"></i>
            Map View
        </a>
    </div>
</div>

<div class="row g-4">

    {{-- Main Geo Tagging Detail --}}
    <div class="col-xl-8 col-lg-12">
        <div class="card-ppmf">
            <div class="card-ppmf-header">
                <div class="card-ppmf-title">
                    <i class="bi bi-geo-alt"></i>
                    Geo Tagging Information
                </div>

                <span class="badge-ppmf
                    @if(($geoTagging->status ?? '') === 'verified') achieved
                    @elseif(($geoTagging->status ?? '') === 'rejected') critical
                    @else pending
                    @endif
                ">
                    {{ ucfirst(str_replace('_', ' ', $geoTagging->status ?? 'Submitted')) }}
                </span>
            </div>

            <div class="card-ppmf-body">
                <div class="detail-grid-ppmf">

                    <div class="detail-item-ppmf">
                        <span>Name</span>
                        <strong>{{ $geoTagging->name ?? 'N/A' }}</strong>
                    </div>

                    <div class="detail-item-ppmf">
                        <span>Type</span>
                        <strong>{{ $geoTagging->geoTaggingType->name ?? 'N/A' }}</strong>
                    </div>

                    <div class="detail-item-ppmf detail-wide">
                        <span>Address</span>
                        <strong>{{ $geoTagging->address ?? 'N/A' }}</strong>
                    </div>

                    <div class="detail-item-ppmf">
                        <span>Division</span>
                        <strong>{{ $geoTagging->division->name ?? 'N/A' }}</strong>
                    </div>

                    <div class="detail-item-ppmf">
                        <span>District</span>
                        <strong>{{ $geoTagging->district->name ?? 'N/A' }}</strong>
                    </div>

                    <div class="detail-item-ppmf">
                        <span>Tehsil</span>
                        <strong>{{ $geoTagging->tehsil->name ?? 'N/A' }}</strong>
                    </div>

                    <div class="detail-item-ppmf">
                        <span>Date & Time</span>
                        <strong>
                            @if($geoTagging->tagged_at)
                                {{ \Carbon\Carbon::parse($geoTagging->tagged_at)->format('d M, Y h:i A') }}
                            @else
                                N/A
                            @endif
                        </strong>
                    </div>

                    <div class="detail-item-ppmf">
                        <span>Performed By</span>
                        <strong>
                            {{ $geoTagging->performer->username ?? $geoTagging->performer->name ?? 'N/A' }}
                        </strong>
                    </div>

                    <div class="detail-item-ppmf">
                        <span>Latitude</span>
                        <strong>{{ $geoTagging->latitude ?? 'N/A' }}</strong>
                    </div>

                    <div class="detail-item-ppmf">
                        <span>Longitude</span>
                        <strong>{{ $geoTagging->longitude ?? 'N/A' }}</strong>
                    </div>

                    <div class="detail-item-ppmf">
                        <span>Created At</span>
                        <strong>
                            @if($geoTagging->created_at)
                                {{ $geoTagging->created_at->format('d M, Y h:i A') }}
                            @else
                                N/A
                            @endif
                        </strong>
                    </div>

                    <div class="detail-item-ppmf">
                        <span>Updated At</span>
                        <strong>
                            @if($geoTagging->updated_at)
                                {{ $geoTagging->updated_at->format('d M, Y h:i A') }}
                            @else
                                N/A
                            @endif
                        </strong>
                    </div>

                </div>
            </div>
        </div>

        {{-- Additional Data --}}
        <div class="card-ppmf mt-4">
            <div class="card-ppmf-header">
                <div class="card-ppmf-title">
                    <i class="bi bi-card-checklist"></i>
                    Additional Detail
                </div>
            </div>

            <div class="card-ppmf-body">
                @php
                    $extraData = $geoTagging->extra_data ?? [];
                @endphp

                @if(!empty($extraData) && is_array($extraData))
                    <div class="table-responsive">
                        <table class="table-ppmf">
                            <thead>
                                <tr>
                                    <th style="width: 70px;">Sr.</th>
                                    <th>Field</th>
                                    <th>Value</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($extraData as $key => $value)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>

                                        <td>
                                            {{ ucwords(str_replace('_', ' ', $key)) }}
                                        </td>

                                        <td>
                                            @if(is_bool($value))
                                                <span class="badge-ppmf {{ $value ? 'achieved' : 'critical' }}">
                                                    {{ $value ? 'Yes' : 'No' }}
                                                </span>
                                            @elseif(is_array($value))
                                                {{ implode(', ', $value) }}
                                            @else
                                                <span class="fw-bold">{{ $value ?: 'N/A' }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="manual-box-ppmf">
                        <i class="bi bi-info-circle"></i>
                        <h5>No Additional Data</h5>
                        <p>This geo-tagging record does not contain extra JSON detail.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Remarks --}}
        @if(!empty($geoTagging->remarks))
            <div class="card-ppmf mt-4">
                <div class="card-ppmf-header">
                    <div class="card-ppmf-title">
                        <i class="bi bi-chat-left-text"></i>
                        Remarks
                    </div>
                </div>

                <div class="card-ppmf-body">
                    <div class="remarks-box-ppmf">
                        {{ $geoTagging->remarks }}
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Side Detail --}}
    <div class="col-xl-4 col-lg-12">

        {{-- Officer Detail --}}
        <div class="card-ppmf mb-4">
            <div class="card-ppmf-header">
                <div class="card-ppmf-title">
                    <i class="bi bi-person-badge"></i>
                    Officer Detail
                </div>
            </div>

            <div class="card-ppmf-body">
                <div class="summary-stack">

                    <div class="summary-item">
                        <span>Name</span>
                        <strong>{{ $geoTagging->performer->name ?? 'N/A' }}</strong>
                    </div>

                    <div class="summary-item">
                        <span>Username</span>
                        <strong>{{ $geoTagging->performer->username ?? 'N/A' }}</strong>
                    </div>

                    <div class="summary-item">
                        <span>Designation</span>
                        <strong>{{ $geoTagging->performer->designation ?? 'N/A' }}</strong>
                    </div>

                    <div class="summary-item">
                        <span>Role</span>
                        <strong>{{ $geoTagging->performer->role->name ?? 'N/A' }}</strong>
                    </div>

                    <div class="summary-item">
                        <span>Status</span>
                        <strong>{{ ucfirst(str_replace('_', ' ', $geoTagging->status ?? 'N/A')) }}</strong>
                    </div>

                </div>
            </div>
        </div>

        {{-- Location --}}
        <div class="card-ppmf mb-4">
            <div class="card-ppmf-header">
                <div class="card-ppmf-title">
                    <i class="bi bi-geo-alt"></i>
                    Location
                </div>
            </div>

            <div class="card-ppmf-body">
                @if($geoTagging->latitude && $geoTagging->longitude)
                    <div class="location-map-ppmf">
                        <i class="bi bi-geo-alt-fill"></i>
                        <h5>Geo Location Available</h5>

                        <p>
                            {{ $geoTagging->latitude }},
                            {{ $geoTagging->longitude }}
                        </p>

                        <a
                            href="https://www.google.com/maps?q={{ $geoTagging->latitude }},{{ $geoTagging->longitude }}"
                            target="_blank"
                            class="btn-gov btn-gov-primary btn-gov-sm"
                        >
                            <i class="bi bi-box-arrow-up-right"></i>
                            Open in Google Maps
                        </a>
                    </div>
                @else
                    <div class="location-map-ppmf">
                        <i class="bi bi-geo"></i>
                        <h5>No Location Found</h5>
                        <p>Latitude and longitude are not available for this geo-tagging record.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Quick Summary --}}
        <div class="card-ppmf">
            <div class="card-ppmf-header">
                <div class="card-ppmf-title">
                    <i class="bi bi-info-circle"></i>
                    Record Summary
                </div>
            </div>

            <div class="card-ppmf-body">
                <div class="summary-stack">

                    <div class="summary-item">
                        <span>Geo Tagging ID</span>
                        <strong>#{{ $geoTagging->id }}</strong>
                    </div>

                    <div class="summary-item">
                        <span>Type</span>
                        <strong>{{ $geoTagging->geoTaggingType->name ?? 'N/A' }}</strong>
                    </div>

                    <div class="summary-item">
                        <span>District</span>
                        <strong>{{ $geoTagging->district->name ?? 'N/A' }}</strong>
                    </div>

                    <div class="summary-item">
                        <span>Tehsil</span>
                        <strong>{{ $geoTagging->tehsil->name ?? 'N/A' }}</strong>
                    </div>

                </div>
            </div>
        </div>

    </div>

</div>

@endsection

@extends('layouts.app')

@section('title', 'Baseline Asset Detail')

@section('content')

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Baseline Asset Detail</h1>
        <p class="page-subtitle">
            Complete baseline asset information with location and extra JSON detail.
        </p>
    </div>

    <div class="page-title-actions">
        <a href="{{ route('baseline.assets') }}" class="btn-gov btn-gov-outline">
            <i class="bi bi-arrow-left"></i>
            Back to Assets
        </a>
    </div>
</div>

<div class="row g-4">

    <div class="col-xl-8">
        <div class="card-ppmf">
            <div class="card-ppmf-header">
                <div class="card-ppmf-title">
                    <i class="bi bi-box-seam"></i>
                    Asset Information
                </div>

                <span class="badge-ppmf
                    @if(in_array($asset->status, ['functional', 'active'])) achieved
                    @elseif(in_array($asset->status, ['non_functional', 'inactive'])) critical
                    @else pending
                    @endif
                ">
                    {{ ucfirst(str_replace('_', ' ', $asset->status ?? 'N/A')) }}
                </span>
            </div>

            <div class="card-ppmf-body">
                <div class="detail-grid-ppmf">

                    <div class="detail-item-ppmf">
                        <span>Asset Name</span>
                        <strong>{{ $asset->name ?? 'N/A' }}</strong>
                    </div>

                    <div class="detail-item-ppmf">
                        <span>KPI Category</span>
                        <strong>{{ $asset->kpiCategory->name ?? 'N/A' }}</strong>
                    </div>

                    <div class="detail-item-ppmf detail-wide">
                        <span>Address</span>
                        <strong>{{ $asset->address ?? 'N/A' }}</strong>
                    </div>

                    <div class="detail-item-ppmf">
                        <span>Division</span>
                        <strong>{{ $asset->division->name ?? 'N/A' }}</strong>
                    </div>

                    <div class="detail-item-ppmf">
                        <span>District</span>
                        <strong>{{ $asset->district->name ?? 'N/A' }}</strong>
                    </div>

                    <div class="detail-item-ppmf">
                        <span>Tehsil</span>
                        <strong>{{ $asset->tehsil->name ?? 'N/A' }}</strong>
                    </div>

                    <div class="detail-item-ppmf">
                        <span>Baseline Date</span>
                        <strong>
                            {{ $asset->baseline_date ? \Carbon\Carbon::parse($asset->baseline_date)->format('d M, Y') : 'N/A' }}
                        </strong>
                    </div>

                    <div class="detail-item-ppmf">
                        <span>Status</span>
                        <strong>{{ ucfirst(str_replace('_', ' ', $asset->status ?? 'N/A')) }}</strong>
                    </div>

                    <div class="detail-item-ppmf">
                        <span>Latitude</span>
                        <strong>{{ $asset->latitude ?? 'N/A' }}</strong>
                    </div>

                    <div class="detail-item-ppmf">
                        <span>Longitude</span>
                        <strong>{{ $asset->longitude ?? 'N/A' }}</strong>
                    </div>

                </div>
            </div>
        </div>

        <div class="card-ppmf mt-4">
            <div class="card-ppmf-header">
                <div class="card-ppmf-title">
                    <i class="bi bi-card-list"></i>
                    Extra Asset Data
                </div>
            </div>

            <div class="card-ppmf-body">
                @php
                    $detailData = $asset->detail_data ?? [];
                @endphp

                @if(is_array($detailData) && count($detailData) > 0)
                    <div class="table-responsive">
                        <table class="table-ppmf">
                            <thead>
                                <tr>
                                    <th style="width:70px;">Sr.</th>
                                    <th>Field</th>
                                    <th>Value</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($detailData as $key => $value)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>

                                        <td>{{ ucwords(str_replace('_', ' ', $key)) }}</td>

                                        <td>
                                            @if(is_array($value))
                                                {{ implode(', ', $value) }}
                                            @elseif(is_bool($value))
                                                <span class="badge-ppmf {{ $value ? 'achieved' : 'critical' }}">
                                                    {{ $value ? 'Yes' : 'No' }}
                                                </span>
                                            @else
                                                <strong>{{ $value ?: 'N/A' }}</strong>
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
                        <h5>No Extra Data</h5>
                        <p>This baseline asset does not contain extra JSON detail.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card-ppmf">
            <div class="card-ppmf-header">
                <div class="card-ppmf-title">
                    <i class="bi bi-geo-alt"></i>
                    Location
                </div>
            </div>

            <div class="card-ppmf-body">
                @if($asset->latitude && $asset->longitude)
                    <div class="location-map-ppmf">
                        <i class="bi bi-geo-alt-fill"></i>
                        <h5>Location Available</h5>
                        <p>{{ $asset->latitude }}, {{ $asset->longitude }}</p>

                        <a href="https://www.google.com/maps?q={{ $asset->latitude }},{{ $asset->longitude }}"
                           target="_blank"
                           class="btn-gov btn-gov-primary btn-gov-sm">
                            <i class="bi bi-box-arrow-up-right"></i>
                            Open Google Maps
                        </a>
                    </div>
                @else
                    <div class="location-map-ppmf">
                        <i class="bi bi-geo"></i>
                        <h5>No Location</h5>
                        <p>Latitude and longitude are not available.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>

@endsection

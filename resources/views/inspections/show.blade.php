@extends('layouts.app')

@section('title', 'Inspection Detail')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

    <style>
        .inspection-detail-page {
            --gov-primary: #0f766e;
            --gov-primary-dark: #115e59;
            --gov-soft: #ecfdf5;
            --ink: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --panel: #ffffff;
            --page: #f6f8fb;
            --success: #16a34a;
            --info: #0284c7;
            --warning: #d97706;
            --danger: #dc2626;
        }

        .inspection-detail-page .detail-hero {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(15, 118, 110, .15);
            border-radius: 22px;
            padding: 22px 24px;
            background:
                radial-gradient(circle at top right, rgba(20, 184, 166, .16), transparent 34%),
                linear-gradient(135deg, #ffffff 0%, #f0fdfa 100%);
            box-shadow: 0 18px 44px rgba(15, 23, 42, .08);
            margin-bottom: 22px;
        }

        .inspection-detail-page .detail-hero::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            width: 6px;
            height: 100%;
            background: linear-gradient(180deg, #0f766e, #22c55e);
        }

        .inspection-detail-page .hero-meta {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
        }

        .inspection-detail-page .hero-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--gov-primary-dark);
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .09em;
            margin-bottom: 8px;
        }

        .inspection-detail-page .hero-title {
            margin: 0;
            color: var(--ink);
            font-size: 26px;
            font-weight: 900;
            letter-spacing: -.03em;
            line-height: 1.15;
        }

        .inspection-detail-page .hero-subtitle {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 14px;
            font-weight: 600;
        }

        .inspection-detail-page .hero-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .inspection-detail-page .detail-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border-radius: 999px;
            padding: 9px 13px;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .04em;
            border: 1px solid transparent;
        }

        .inspection-detail-page .status-approved { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
        .inspection-detail-page .status-reviewed { background: #e0f2fe; color: #075985; border-color: #bae6fd; }
        .inspection-detail-page .status-rejected { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
        .inspection-detail-page .status-submitted,
        .inspection-detail-page .status-pending { background: #fef3c7; color: #92400e; border-color: #fde68a; }

        .inspection-detail-page .summary-strip {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 22px;
        }

        .inspection-detail-page .summary-tile {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 82px;
            padding: 15px;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 12px 28px rgba(15, 23, 42, .055);
        }

        .inspection-detail-page .summary-icon {
            width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            background: linear-gradient(135deg, #0f766e, #14b8a6);
            color: #fff;
            font-size: 20px;
            box-shadow: 0 10px 20px rgba(15, 118, 110, .2);
            flex: 0 0 auto;
        }

        .inspection-detail-page .summary-label {
            display: block;
            color: var(--muted);
            font-size: 11px;
            font-weight: 900;
            letter-spacing: .06em;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .inspection-detail-page .summary-value {
            color: var(--ink);
            font-size: 14px;
            font-weight: 900;
            line-height: 1.25;
        }

        .inspection-detail-page .detail-card {
            border: 1px solid var(--line);
            border-radius: 20px;
            background: var(--panel);
            box-shadow: 0 14px 34px rgba(15, 23, 42, .065);
            overflow: hidden;
            margin-bottom: 22px;
        }

        .inspection-detail-page .detail-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 16px 18px;
            background: linear-gradient(135deg, #ffffff, #f8fafc);
            border-bottom: 1px solid var(--line);
        }

        .inspection-detail-page .detail-card-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            color: var(--ink);
            font-size: 15px;
            font-weight: 900;
            letter-spacing: -.01em;
        }

        .inspection-detail-page .detail-card-title i {
            width: 34px;
            height: 34px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--gov-primary);
            background: var(--gov-soft);
        }

        .inspection-detail-page .detail-card-body {
            padding: 18px;
        }

        .inspection-detail-page .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            border: 1px solid var(--line);
            border-radius: 16px;
            overflow: hidden;
        }

        .inspection-detail-page .info-item {
            display: grid;
            grid-template-columns: 210px minmax(0, 1fr);
            min-height: 50px;
            border-bottom: 1px solid var(--line);
            background: #fff;
        }

        .inspection-detail-page .info-item:nth-last-child(-n+2) {
            border-bottom: 0;
        }

        .inspection-detail-page .info-item.info-wide {
            grid-column: 1 / -1;
        }

        .inspection-detail-page .info-label {
            display: flex;
            align-items: center;
            padding: 12px 14px;
            background: #f8fafc;
            color: #475569;
            font-size: 13px;
            font-weight: 800;
            border-right: 1px solid var(--line);
        }

        .inspection-detail-page .info-value {
            display: flex;
            align-items: center;
            padding: 12px 14px;
            color: var(--ink);
            font-size: 13px;
            font-weight: 800;
            word-break: break-word;
        }

        .inspection-detail-page .dynamic-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .inspection-detail-page .dynamic-item {
            border: 1px solid var(--line);
            border-radius: 16px;
            background: #fff;
            overflow: hidden;
        }

        .inspection-detail-page .dynamic-key {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: center;
            padding: 11px 13px;
            background: #f8fafc;
            border-bottom: 1px solid var(--line);
            color: #334155;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .035em;
        }

        .inspection-detail-page .dynamic-value {
            padding: 13px;
            color: var(--ink);
            font-size: 14px;
            font-weight: 800;
            line-height: 1.55;
            min-height: 48px;
        }

        .inspection-detail-page .value-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 900;
            border: 1px solid transparent;
        }

        .inspection-detail-page .chip-yes { color: #166534; background: #dcfce7; border-color: #bbf7d0; }
        .inspection-detail-page .chip-no { color: #991b1b; background: #fee2e2; border-color: #fecaca; }
        .inspection-detail-page .chip-neutral { color: #334155; background: #f1f5f9; border-color: #e2e8f0; }

        .inspection-detail-page .nested-list {
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 8px;
        }

        .inspection-detail-page .nested-list li {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 9px 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 13px;
        }

        .inspection-detail-page .nested-list span:first-child {
            color: #64748b;
            font-weight: 800;
        }

        .inspection-detail-page .nested-list span:last-child {
            color: #0f172a;
            font-weight: 900;
            text-align: right;
        }

        .inspection-detail-page .media-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .inspection-detail-page .media-card {
            border: 1px solid var(--line);
            border-radius: 16px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .055);
        }

        .inspection-detail-page .media-card img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            display: block;
            background: #f1f5f9;
        }

        .inspection-detail-page .media-caption {
            padding: 11px 12px;
            color: #475569;
            font-size: 12px;
            font-weight: 800;
            border-top: 1px solid var(--line);
        }

        .inspection-detail-page .map-panel {
            height: 420px;
            border: 1px solid var(--line);
            border-radius: 18px;
            overflow: hidden;
            background: #e2e8f0;
        }

        .inspection-detail-page #inspectionDetailMap {
            width: 100%;
            height: 100%;
        }

        .inspection-detail-page .map-fallback {
            min-height: 180px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 20px;
            border: 1px dashed #cbd5e1;
            border-radius: 18px;
            background: #f8fafc;
            text-align: center;
            color: #64748b;
        }

        .inspection-detail-page .map-fallback i {
            font-size: 34px;
            color: #94a3b8;
        }

        .inspection-detail-page .empty-state {
            min-height: 150px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 24px;
            border: 1px dashed #cbd5e1;
            border-radius: 18px;
            background: #f8fafc;
            text-align: center;
        }

        .inspection-detail-page .empty-state i {
            font-size: 34px;
            color: #94a3b8;
        }

        .inspection-detail-page .empty-state h5 {
            margin: 0;
            color: #334155;
            font-size: 15px;
            font-weight: 900;
        }

        .inspection-detail-page .empty-state p {
            margin: 0;
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
        }

        .inspection-detail-page .remarks-box {
            padding: 16px;
            border-radius: 16px;
            border: 1px solid #fde68a;
            background: #fffbeb;
            color: #78350f;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.65;
        }

        @media (max-width: 1199px) {
            .inspection-detail-page .summary-strip {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .inspection-detail-page .media-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 991px) {
            .inspection-detail-page .info-grid,
            .inspection-detail-page .dynamic-grid {
                grid-template-columns: 1fr;
            }

            .inspection-detail-page .info-item:nth-last-child(-n+2) {
                border-bottom: 1px solid var(--line);
            }

            .inspection-detail-page .info-item:last-child {
                border-bottom: 0;
            }
        }

        @media (max-width: 767px) {
            .inspection-detail-page .detail-hero {
                padding: 18px;
            }

            .inspection-detail-page .hero-title {
                font-size: 21px;
            }

            .inspection-detail-page .summary-strip,
            .inspection-detail-page .media-grid {
                grid-template-columns: 1fr;
            }

            .inspection-detail-page .info-item {
                grid-template-columns: 1fr;
            }

            .inspection-detail-page .info-label {
                border-right: 0;
                border-bottom: 1px solid var(--line);
            }

            .inspection-detail-page .map-panel {
                height: 320px;
            }
        }
    </style>
@endpush

@section('content')
@php
    $status = strtolower($inspection->status ?? 'submitted');
    $statusClass = match ($status) {
        'approved' => 'status-approved',
        'reviewed' => 'status-reviewed',
        'rejected' => 'status-rejected',
        'pending' => 'status-pending',
        default => 'status-submitted',
    };

    $formatLabel = function ($key) {
        return ucwords(str_replace(['_', '-'], ' ', (string) $key));
    };

    $isMetaKey = function ($key) {
        return in_array($key, [
            'id', 'inspection_id', 'created_at', 'updated_at', 'deleted_at',
            'pivot', 'kpi_category_id', 'user_id'
        ], true);
    };

    $normalizeSingle = function ($payload) use (&$normalizeSingle, $isMetaKey) {
        if (blank($payload)) {
            return [];
        }

        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            return json_last_error() === JSON_ERROR_NONE ? $normalizeSingle($decoded) : ['value' => $payload];
        }

        if ($payload instanceof \Illuminate\Support\Collection) {
            return $payload->map(fn ($item) => $normalizeSingle($item))->filter()->values()->all();
        }

        if (is_object($payload)) {
            if (method_exists($payload, 'toArray')) {
                $payload = $payload->toArray();
            } else {
                $payload = (array) $payload;
            }
        }

        if (!is_array($payload)) {
            return ['value' => $payload];
        }

        foreach (['data', 'payload', 'details', 'values', 'observation_data', 'action_data', 'actions', 'observations'] as $candidate) {
            if (array_key_exists($candidate, $payload) && is_array($payload[$candidate])) {
                return $normalizeSingle($payload[$candidate]);
            }
        }

        return collect($payload)
            ->reject(fn ($value, $key) => $isMetaKey($key))
            ->toArray();
    };

    $normalizeBlock = function ($payload) use ($normalizeSingle) {
        $normal = $normalizeSingle($payload);

        if (isset($normal[0]) && is_array($normal[0])) {
            $merged = [];
            foreach ($normal as $index => $row) {
                if (count($row) === 1 && array_key_exists('value', $row)) {
                    $merged['Item '.($index + 1)] = $row['value'];
                } else {
                    foreach ($row as $key => $value) {
                        $merged[$key] = $value;
                    }
                }
            }
            return $merged;
        }

        return $normal;
    };

    $observations = $normalizeBlock($inspection->observations ?? []);
    $actions = $normalizeBlock($inspection->actions ?? []);
    $attachments = $inspection->attachments ?? collect();
    $lat = $inspection->latitude ?? null;
    $lng = $inspection->longitude ?? null;
@endphp

<div class="inspection-detail-page">
    <div class="detail-hero">
        <div class="hero-meta">
            <div>
                <div class="hero-kicker">
                    <i class="bi bi-clipboard2-check"></i>
                    Inspection Detail
                </div>

                <h1 class="hero-title">
                    {{ $inspection->kpiCategory->name ?? 'Inspection Record' }}
                </h1>

                <p class="hero-subtitle">
                    {{ $inspection->district->name ?? 'N/A' }}
                    @if(!empty($inspection->tehsil->name))
                        / {{ $inspection->tehsil->name }}
                    @endif
                    @if($inspection->inspection_datetime)
                        • {{ \Carbon\Carbon::parse($inspection->inspection_datetime)->format('d M Y, h:i A') }}
                    @endif
                </p>
            </div>

            <div class="hero-actions">
                <span class="detail-status-badge {{ $statusClass }}">
                    <i class="bi bi-circle-fill"></i>
                    {{ ucfirst(str_replace('_', ' ', $inspection->status ?? 'Submitted')) }}
                </span>

                <a href="{{ route('inspections.list') }}" class="btn-gov btn-gov-outline">
                    <i class="bi bi-arrow-left"></i>
                    Back to List
                </a>

                <a href="{{ route('inspections.map') }}" class="btn-gov btn-gov-primary">
                    <i class="bi bi-map"></i>
                    Map View
                </a>
            </div>
        </div>
    </div>

    <div class="summary-strip">
        <div class="summary-tile">
            <div class="summary-icon"><i class="bi bi-diagram-3"></i></div>
            <div>
                <span class="summary-label">Inspection Type</span>
                <div class="summary-value">{{ $inspection->kpiCategory->name ?? 'N/A' }}</div>
            </div>
        </div>

        <div class="summary-tile">
            <div class="summary-icon"><i class="bi bi-calendar2-check"></i></div>
            <div>
                <span class="summary-label">Date & Time</span>
                <div class="summary-value">
                    {{ $inspection->inspection_datetime ? \Carbon\Carbon::parse($inspection->inspection_datetime)->format('d M, Y h:i A') : 'N/A' }}
                </div>
            </div>
        </div>

        <div class="summary-tile">
            <div class="summary-icon"><i class="bi bi-geo-alt"></i></div>
            <div>
                <span class="summary-label">District / Tehsil</span>
                <div class="summary-value">
                    {{ $inspection->district->name ?? 'N/A' }}
                    @if(!empty($inspection->tehsil->name))
                        / {{ $inspection->tehsil->name }}
                    @endif
                </div>
            </div>
        </div>

        <div class="summary-tile">
            <div class="summary-icon"><i class="bi bi-person-badge"></i></div>
            <div>
                <span class="summary-label">Performed By</span>
                <div class="summary-value">
                    {{ $inspection->performer->name ?? $inspection->performer->username ?? 'N/A' }}
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8 col-lg-12">
            <div class="detail-card">
                <div class="detail-card-header">
                    <h2 class="detail-card-title">
                        <i class="bi bi-card-checklist"></i>
                        Core Inspection Information
                    </h2>
                </div>

                <div class="detail-card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Inspection Type</div>
                            <div class="info-value">{{ $inspection->kpiCategory->name ?? 'N/A' }}</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Date & Time</div>
                            <div class="info-value">
                                {{ $inspection->inspection_datetime ? \Carbon\Carbon::parse($inspection->inspection_datetime)->format('d M, Y h:i A') : 'N/A' }}
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Main Title / Name</div>
                            <div class="info-value">{{ $inspection->main_title ?? 'N/A' }}</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Identifier / CNIC</div>
                            <div class="info-value">{{ $inspection->main_identifier ?? 'N/A' }}</div>
                        </div>

                        <div class="info-item info-wide">
                            <div class="info-label">Address</div>
                            <div class="info-value">{{ $inspection->main_address ?? 'N/A' }}</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Division</div>
                            <div class="info-value">{{ $inspection->division->name ?? 'N/A' }}</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">District</div>
                            <div class="info-value">{{ $inspection->district->name ?? 'N/A' }}</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Tehsil</div>
                            <div class="info-value">{{ $inspection->tehsil->name ?? 'N/A' }}</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Latitude</div>
                            <div class="info-value">{{ $lat ?? 'N/A' }}</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Longitude</div>
                            <div class="info-value">{{ $lng ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="detail-card">
                <div class="detail-card-header">
                    <h2 class="detail-card-title">
                        <i class="bi bi-eye"></i>
                        Dynamic Observations
                    </h2>
                    <span class="value-chip chip-neutral">{{ count($observations) }} Fields</span>
                </div>

                <div class="detail-card-body">
                    @if(!empty($observations))
                        <div class="dynamic-grid">
                            @foreach($observations as $key => $value)
                                <div class="dynamic-item">
                                    <div class="dynamic-key">
                                        <span>{{ $formatLabel($key) }}</span>
                                        <i class="bi bi-check2-square"></i>
                                    </div>
                                    <div class="dynamic-value">
                                        @if(is_bool($value))
                                            <span class="value-chip {{ $value ? 'chip-yes' : 'chip-no' }}">
                                                <i class="bi {{ $value ? 'bi-check-circle' : 'bi-x-circle' }}"></i>
                                                {{ $value ? 'Yes' : 'No' }}
                                            </span>
                                        @elseif(is_array($value))
                                            <ul class="nested-list">
                                                @foreach($value as $nestedKey => $nestedValue)
                                                    <li>
                                                        <span>{{ is_numeric($nestedKey) ? 'Item '.($nestedKey + 1) : $formatLabel($nestedKey) }}</span>
                                                        <span>{{ is_array($nestedValue) ? json_encode($nestedValue) : ($nestedValue ?: 'N/A') }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            {{ filled($value) ? $value : 'N/A' }}
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="bi bi-clipboard-x"></i>
                            <h5>No Observations Added</h5>
                            <p>This inspection record does not contain observation data.</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="detail-card">
                <div class="detail-card-header">
                    <h2 class="detail-card-title">
                        <i class="bi bi-lightning-charge"></i>
                        Dynamic Actions / Compliance Detail
                    </h2>
                    <span class="value-chip chip-neutral">{{ count($actions) }} Fields</span>
                </div>

                <div class="detail-card-body">
                    @if(!empty($actions))
                        <div class="dynamic-grid">
                            @foreach($actions as $key => $value)
                                <div class="dynamic-item">
                                    <div class="dynamic-key">
                                        <span>{{ $formatLabel($key) }}</span>
                                        <i class="bi bi-activity"></i>
                                    </div>
                                    <div class="dynamic-value">
                                        @if(is_bool($value))
                                            <span class="value-chip {{ $value ? 'chip-yes' : 'chip-no' }}">
                                                <i class="bi {{ $value ? 'bi-check-circle' : 'bi-x-circle' }}"></i>
                                                {{ $value ? 'Yes' : 'No' }}
                                            </span>
                                        @elseif(is_array($value))
                                            <ul class="nested-list">
                                                @foreach($value as $nestedKey => $nestedValue)
                                                    <li>
                                                        <span>{{ is_numeric($nestedKey) ? 'Item '.($nestedKey + 1) : $formatLabel($nestedKey) }}</span>
                                                        <span>{{ is_array($nestedValue) ? json_encode($nestedValue) : ($nestedValue ?: 'N/A') }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            {{ filled($value) ? $value : 'N/A' }}
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="bi bi-lightning"></i>
                            <h5>No Actions Added</h5>
                            <p>This inspection record does not contain action data.</p>
                        </div>
                    @endif
                </div>
            </div>

            @if(!empty($inspection->remarks))
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h2 class="detail-card-title">
                            <i class="bi bi-chat-left-text"></i>
                            Remarks
                        </h2>
                    </div>
                    <div class="detail-card-body">
                        <div class="remarks-box">{{ $inspection->remarks }}</div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-xl-4 col-lg-12">
            <div class="detail-card">
                <div class="detail-card-header">
                    <h2 class="detail-card-title">
                        <i class="bi bi-person-badge"></i>
                        Officer Detail
                    </h2>
                </div>

                <div class="detail-card-body">
                    <div class="info-grid" style="grid-template-columns: 1fr;">
                        <div class="info-item" style="grid-template-columns: 145px minmax(0, 1fr);">
                            <div class="info-label">Name</div>
                            <div class="info-value">{{ $inspection->performer->name ?? 'N/A' }}</div>
                        </div>
                        <div class="info-item" style="grid-template-columns: 145px minmax(0, 1fr);">
                            <div class="info-label">Username</div>
                            <div class="info-value">{{ $inspection->performer->username ?? 'N/A' }}</div>
                        </div>
                        <div class="info-item" style="grid-template-columns: 145px minmax(0, 1fr);">
                            <div class="info-label">Role</div>
                            <div class="info-value">{{ $inspection->performer->role->name ?? 'N/A' }}</div>
                        </div>
                        <div class="info-item" style="grid-template-columns: 145px minmax(0, 1fr);">
                            <div class="info-label">Designation</div>
                            <div class="info-value">{{ $inspection->performer->designation ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="detail-card">
                <div class="detail-card-header">
                    <h2 class="detail-card-title">
                        <i class="bi bi-geo-alt"></i>
                        Geo Location
                    </h2>

                    @if($lat && $lng)
                        <a href="https://www.google.com/maps?q={{ $lat }},{{ $lng }}" target="_blank" class="btn-gov btn-gov-outline btn-gov-sm">
                            <i class="bi bi-box-arrow-up-right"></i>
                            Open
                        </a>
                    @endif
                </div>

                <div class="detail-card-body">
                    @if($lat && $lng)
                        <div class="map-panel">
                            <div id="inspectionDetailMap"></div>
                        </div>
                    @else
                        <div class="map-fallback">
                            <i class="bi bi-geo"></i>
                            <strong>No Location Found</strong>
                            <span>Latitude and longitude are not available for this inspection.</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="detail-card">
                <div class="detail-card-header">
                    <h2 class="detail-card-title">
                        <i class="bi bi-paperclip"></i>
                        Attachments / Evidence
                    </h2>
                </div>

                <div class="detail-card-body">
                    @if($attachments && count($attachments))
                        <div class="media-grid" style="grid-template-columns: 1fr;">
                            @foreach($attachments as $attachment)
                                @php
                                    $path = $attachment->file_path
                                        ?? $attachment->attachment_path
                                        ?? $attachment->path
                                        ?? $attachment->url
                                        ?? null;

                                    $caption = $attachment->caption
                                        ?? $attachment->file_name
                                        ?? $attachment->type
                                        ?? 'Inspection Evidence';

                                    $url = $path
                                        ? (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://']) ? $path : asset('storage/' . ltrim($path, '/')))
                                        : null;
                                @endphp

                                @if($url)
                                    <div class="media-card">
                                        <a href="{{ $url }}" target="_blank">
                                            <img src="{{ $url }}" alt="{{ $caption }}">
                                        </a>
                                        <div class="media-caption">{{ $caption }}</div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="bi bi-image"></i>
                            <h5>No Attachments Found</h5>
                            <p>No image or evidence file is attached with this record.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @if($lat && $lng)
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const lat = Number(@json($lat));
                const lng = Number(@json($lng));

                if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                    return;
                }

                const map = L.map('inspectionDetailMap', {
                    zoomControl: true,
                    scrollWheelZoom: false
                }).setView([lat, lng], 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                const marker = L.marker([lat, lng]).addTo(map);
                marker.bindPopup(`
                    <strong>{{ addslashes($inspection->kpiCategory->name ?? 'Inspection Location') }}</strong><br>
                    {{ addslashes($inspection->district->name ?? 'N/A') }}<br>
                    ${lat}, ${lng}
                `).openPopup();

                setTimeout(function () {
                    map.invalidateSize();
                }, 250);
            });
        </script>
    @endif
@endpush

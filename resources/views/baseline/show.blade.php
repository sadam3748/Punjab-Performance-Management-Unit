@extends('layouts.app')

@section('title', 'District Baseline Detail')

@section('content')

@php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;

    $baseline = $baseline ?? $districtBaseline ?? $record ?? null;

    $normalizeBaselineData = function ($rawData) {
        if (is_string($rawData)) {
            $decoded = json_decode($rawData, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($rawData) ? $rawData : [];
    };

    $readableLabel = function ($key) {
        $key = (string) $key;
        $key = str_replace(['total_number_of_', 'total_no_of_', 'total_no_', 'number_of_', 'no_of_'], '', $key);
        $key = str_replace(['_in_the_district', '_district'], '', $key);
        return Str::headline($key);
    };

    $isUsefulField = function ($key, $value) {
        if (is_int($key) || ctype_digit((string) $key)) {
            return false;
        }

        if (is_null($value) || $value === '') {
            return false;
        }

        if (is_array($value) && count($value) === 0) {
            return false;
        }

        $keyText = strtolower((string) $key);
        $valueText = strtolower(is_scalar($value) ? (string) $value : '');

        if ($keyText === 'notes' && str_contains($valueText, 'dummy baseline')) {
            return false;
        }

        return true;
    };

    $formatBaselineValue = function ($value) {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_numeric($value)) {
            $number = (float) $value;
            return number_format($number, floor($number) == $number ? 0 : 2);
        }

        if (is_array($value)) {
            $items = collect($value)
                ->filter(fn ($item) => is_scalar($item) && trim((string) $item) !== '')
                ->take(6)
                ->map(fn ($item) => (string) $item)
                ->values()
                ->all();

            return count($items) ? implode(', ', $items) : 'Multiple values';
        }

        return (string) $value;
    };

    $baselineDataArray = $baseline ? $normalizeBaselineData($baseline->baseline_data ?? $baseline->data ?? []) : [];

    $readableFields = collect($baselineDataArray)
        ->filter(fn ($value, $key) => $isUsefulField($key, $value));

    $priorityKeywords = ['total', 'functional', 'compliant', 'population', 'school', 'health', 'road', 'manhole', 'plant', 'institution'];

    $highlightFields = $readableFields
        ->filter(function ($value, $key) use ($priorityKeywords) {
            $keyText = strtolower((string) $key);
            foreach ($priorityKeywords as $keyword) {
                if (str_contains($keyText, $keyword)) {
                    return true;
                }
            }
            return false;
        })
        ->take(4);

    if ($highlightFields->count() < 4) {
        $highlightFields = $highlightFields
            ->merge($readableFields->reject(fn ($value, $key) => $highlightFields->has($key))->take(4 - $highlightFields->count()));
    }
@endphp

<div class="page-title-bar baseline-title-bar">
    <div>
        <span class="baseline-eyebrow">District Baseline Profile</span>
        <h1 class="page-title">District Baseline Detail</h1>
        <p class="page-subtitle">
            Complete field-wise baseline information for selected district, KPI category and year.
        </p>
    </div>

    <div class="page-title-actions">
        @if(Route::has('kpi.district-baseline'))
            <a href="{{ route('kpi.district-baseline') }}" class="btn-gov btn-gov-outline">
                <i class="bi bi-arrow-left"></i>
                Back to Baseline Report
            </a>
        @endif

        @if(Route::has('baseline.edit') && $baseline)
            <a href="{{ route('baseline.edit', $baseline->id) }}" class="btn-gov btn-gov-primary">
                <i class="bi bi-pencil-square"></i>
                Edit Baseline
            </a>
        @endif
    </div>
</div>

@if(!$baseline)
    <div class="baseline-empty-state standalone">
        <i class="bi bi-database-x"></i>
        <h5>Baseline Record Not Found</h5>
        <p>The requested district baseline record is not available.</p>
    </div>
@else

<div class="baseline-profile-hero">
    <div class="profile-identity">
        <div class="profile-icon"><i class="bi bi-geo-alt-fill"></i></div>
        <div>
            <span>District</span>
            <h2>{{ $baseline->district->name ?? 'N/A' }}</h2>
            <p>{{ $baseline->kpiCategory->name ?? 'General KPI Baseline' }}</p>
        </div>
    </div>

    <div class="profile-meta-grid">
        <div>
            <span>Year</span>
            <strong>{{ $baseline->year ?? 'N/A' }}</strong>
        </div>
        <div>
            <span>Total Fields</span>
            <strong>{{ number_format($readableFields->count()) }}</strong>
        </div>
        <div>
            <span>Last Updated</span>
            <strong>{{ $baseline->updated_at ? $baseline->updated_at->format('d M Y') : 'N/A' }}</strong>
        </div>
    </div>
</div>

<div class="baseline-highlight-grid">
    @forelse($highlightFields as $key => $value)
        <div class="baseline-highlight-card">
            <div class="highlight-icon"><i class="bi bi-bar-chart-line"></i></div>
            <div>
                <span>{{ $readableLabel($key) }}</span>
                <strong>{{ $formatBaselineValue($value) }}</strong>
            </div>
        </div>
    @empty
        <div class="baseline-empty-state compact full-width">
            <i class="bi bi-info-circle"></i>
            <h5>No Highlight Fields</h5>
            <p>No readable baseline indicators were found for this record.</p>
        </div>
    @endforelse
</div>

<div class="row g-4 mt-1">
    <div class="col-xl-8">
        <div class="baseline-detail-card">
            <div class="baseline-detail-head">
                <div>
                    <h5><i class="bi bi-list-check"></i> KPI-Specific Baseline Indicators</h5>
                    <p>Complete field/value list. Each row explains what was recorded against this baseline.</p>
                </div>
            </div>

            @if($readableFields->count() > 0)
                <div class="table-responsive">
                    <table class="baseline-detail-table">
                        <thead>
                            <tr>
                                <th style="width: 70px;">Sr.</th>
                                <th>Baseline Field</th>
                                <th style="width: 240px;">Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($readableFields as $key => $value)
                                <tr>
                                    <td><span class="sr-pill">{{ $loop->iteration }}</span></td>
                                    <td>
                                        <div class="field-label-wrap">
                                            <strong>{{ $readableLabel($key) }}</strong>
                                            <small>JSON key: {{ $key }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="value-pill">{{ $formatBaselineValue($value) }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="baseline-empty-state compact">
                    <i class="bi bi-database-x"></i>
                    <h5>No Baseline Indicators Available</h5>
                    <p>This record does not contain readable baseline fields.</p>
                </div>
            @endif
        </div>
    </div>

    <div class="col-xl-4">
        <div class="baseline-detail-card h-100">
            <div class="baseline-detail-head">
                <div>
                    <h5><i class="bi bi-shield-check"></i> Record Control</h5>
                    <p>Audit information for data ownership and update tracking.</p>
                </div>
            </div>

            <div class="record-control-list">
                <div class="record-control-item">
                    <span>Created By</span>
                    <strong>{{ $baseline->creator->username ?? $baseline->creator->name ?? 'N/A' }}</strong>
                </div>
                <div class="record-control-item">
                    <span>Updated By</span>
                    <strong>{{ $baseline->updater->username ?? $baseline->updater->name ?? 'N/A' }}</strong>
                </div>
                <div class="record-control-item">
                    <span>Created At</span>
                    <strong>{{ $baseline->created_at ? $baseline->created_at->format('d M, Y h:i A') : 'N/A' }}</strong>
                </div>
                <div class="record-control-item">
                    <span>Last Updated</span>
                    <strong>{{ $baseline->updated_at ? $baseline->updated_at->format('d M, Y h:i A') : 'N/A' }}</strong>
                </div>
            </div>

            <div class="baseline-help-box mt-3">
                <i class="bi bi-info-circle"></i>
                <p>
                    Field means the baseline item name. Value means the saved number or text against that item.
                </p>
            </div>
        </div>
    </div>
</div>

@endif

@endsection

@push('styles')
<style>
    .baseline-eyebrow {
        display: inline-flex;
        align-items: center;
        margin-bottom: 4px;
        color: #166534;
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .08em;
    }

    .baseline-profile-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        margin-bottom: 18px;
        padding: 22px;
        border-radius: 24px;
        border: 1px solid rgba(22, 101, 52, .15);
        background: radial-gradient(circle at top left, rgba(34, 197, 94, .14), transparent 34%), linear-gradient(135deg, #fff, #f8fafc);
        box-shadow: 0 14px 34px rgba(15, 23, 42, .08);
    }

    .profile-identity {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .profile-icon {
        width: 64px;
        height: 64px;
        border-radius: 22px;
        display: grid;
        place-items: center;
        color: #fff;
        background: linear-gradient(135deg, #14532d, #16a34a);
        font-size: 28px;
        box-shadow: 0 12px 28px rgba(22, 101, 52, .22);
        flex-shrink: 0;
    }

    .profile-identity span {
        color: #166534;
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .08em;
    }

    .profile-identity h2 {
        margin: 2px 0;
        color: #0f172a;
        font-size: 30px;
        font-weight: 900;
        letter-spacing: -.04em;
    }

    .profile-identity p {
        margin: 0;
        color: #64748b;
        font-size: 14px;
        font-weight: 700;
    }

    .profile-meta-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(110px, 1fr));
        gap: 10px;
        min-width: 390px;
    }

    .profile-meta-grid div {
        padding: 13px;
        border-radius: 17px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        text-align: center;
    }

    .profile-meta-grid span {
        display: block;
        color: #64748b;
        font-size: 11.5px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .profile-meta-grid strong {
        display: block;
        color: #0f172a;
        font-size: 18px;
        font-weight: 900;
    }

    .baseline-highlight-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 18px;
    }

    .baseline-highlight-card {
        display: flex;
        align-items: center;
        gap: 13px;
        min-height: 105px;
        padding: 16px;
        border-radius: 20px;
        background: #fff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .07);
    }

    .highlight-icon {
        width: 44px;
        height: 44px;
        border-radius: 15px;
        display: grid;
        place-items: center;
        color: #fff;
        background: linear-gradient(135deg, #075985, #0ea5e9);
        font-size: 20px;
        flex-shrink: 0;
    }

    .baseline-highlight-card span {
        display: block;
        color: #64748b;
        font-size: 12px;
        font-weight: 900;
        line-height: 1.25;
    }

    .baseline-highlight-card strong {
        display: block;
        margin-top: 4px;
        color: #0f172a;
        font-size: 22px;
        font-weight: 900;
        line-height: 1.1;
        word-break: break-word;
    }

    .baseline-detail-card {
        border-radius: 22px;
        background: #fff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 14px 34px rgba(15, 23, 42, .07);
        overflow: hidden;
    }

    .baseline-detail-head {
        padding: 18px 20px;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
    }

    .baseline-detail-head h5 {
        margin: 0;
        color: #0f172a;
        font-size: 17px;
        font-weight: 900;
    }

    .baseline-detail-head h5 i { color: #166534; }

    .baseline-detail-head p {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 13px;
        font-weight: 600;
    }

    .baseline-detail-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin: 0;
    }

    .baseline-detail-table thead th {
        padding: 13px 16px;
        background: linear-gradient(180deg, var(--gov-green-dark) 0%, var(--gov-green) 100%);
        color: #fff;
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .03em;
        white-space: nowrap;
        border-bottom: 2px solid var(--gold);
    }

    .baseline-detail-table tbody td {
        padding: 15px 16px;
        border-bottom: 1px solid #e2e8f0;
        color: #334155;
        font-size: 13px;
        vertical-align: middle;
    }

    .baseline-detail-table tbody tr:hover { background: #f8fafc; }

    .sr-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 30px;
        border-radius: 999px;
        background: #f1f5f9;
        color: #334155;
        font-size: 12px;
        font-weight: 900;
    }

    .field-label-wrap strong {
        display: block;
        color: #0f172a;
        font-size: 14px;
        font-weight: 900;
    }

    .field-label-wrap small {
        display: block;
        margin-top: 2px;
        color: #94a3b8;
        font-size: 11.5px;
        font-weight: 700;
    }

    .value-pill {
        display: inline-flex;
        max-width: 100%;
        padding: 8px 11px;
        border-radius: 13px;
        background: #ecfdf5;
        color: #14532d;
        border: 1px solid #bbf7d0;
        font-size: 13px;
        font-weight: 900;
        word-break: break-word;
    }

    .record-control-list {
        display: grid;
        gap: 10px;
        padding: 18px;
    }

    .record-control-item {
        padding: 14px;
        border-radius: 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }

    .record-control-item span {
        display: block;
        color: #64748b;
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .record-control-item strong {
        display: block;
        margin-top: 3px;
        color: #0f172a;
        font-size: 14px;
        font-weight: 900;
    }

    .baseline-help-box {
        display: flex;
        gap: 10px;
        margin: 0 18px 18px;
        padding: 14px;
        border-radius: 16px;
        background: #fffbeb;
        border: 1px solid #fde68a;
        color: #92400e;
    }

    .baseline-help-box p {
        margin: 0;
        font-size: 12.5px;
        font-weight: 700;
        line-height: 1.45;
    }

    .baseline-empty-state {
        padding: 46px 16px;
        text-align: center;
        color: #64748b;
    }

    .baseline-empty-state.standalone,
    .baseline-empty-state.compact {
        border-radius: 22px;
        background: #fff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 14px 34px rgba(15, 23, 42, .07);
    }

    .baseline-empty-state.compact { padding: 30px 16px; }
    .baseline-empty-state.full-width { grid-column: 1 / -1; }

    .baseline-empty-state i { font-size: 38px; color: #94a3b8; }
    .baseline-empty-state h5 { margin: 10px 0 4px; color: #0f172a; font-weight: 900; }
    .baseline-empty-state p { margin: 0; }

    @media (max-width: 1199px) {
        .baseline-profile-hero { align-items: flex-start; flex-direction: column; }
        .profile-meta-grid { min-width: 0; width: 100%; }
        .baseline-highlight-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 767px) {
        .profile-identity { align-items: flex-start; }
        .profile-identity h2 { font-size: 24px; }
        .profile-meta-grid { grid-template-columns: 1fr; }
        .baseline-highlight-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

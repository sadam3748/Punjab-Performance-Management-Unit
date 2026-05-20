@extends('layouts.app')

@section('title', 'Division Scorecard | PPMF Portal')
@section('page_title', 'Division Scorecard')

@push('styles')
<style>
    .sc-page-card{background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:20px;box-shadow:0 16px 38px rgba(15,23,42,.07);padding:22px}
    .sc-panel{background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:18px;box-shadow:0 12px 28px rgba(15,23,42,.05);overflow:hidden}
    .sc-panel-header{padding:16px 18px;border-bottom:1px solid rgba(148,163,184,.22);background:linear-gradient(135deg,#f8fafc,#ffffff);display:flex;align-items:flex-start;justify-content:space-between;gap:14px}
    .sc-panel-title{font-size:15px;font-weight:900;color:#0f172a;margin:0;text-transform:uppercase;letter-spacing:.04em}
    .sc-panel-subtitle{font-size:12px;color:#64748b;margin-top:4px}
    .sc-table{width:100%;border-collapse:separate;border-spacing:0;margin:0}
    .sc-table thead th{background:#174d43;color:#fff;padding:12px 12px;font-size:11px;text-transform:uppercase;letter-spacing:.06em;font-weight:900;white-space:nowrap}
    .sc-table tbody td{padding:12px;border-bottom:1px solid rgba(148,163,184,.18);font-size:13px;font-weight:700;color:#0f172a;vertical-align:middle}
    .sc-table tbody tr:hover{background:#f8fafc}
    .sc-rank{width:62px;text-align:center}
    .sc-rank-badge{width:34px;height:34px;border-radius:12px;background:#ecfdf5;color:#166534;display:inline-flex;align-items:center;justify-content:center;font-weight:900}
    .sc-district-name{font-weight:900;color:#0f172a;text-transform:uppercase}
    .sc-muted{font-size:12px;color:#64748b;font-weight:600}
    .sc-grade-badge{display:inline-flex;align-items:center;justify-content:center;min-width:52px;height:30px;border-radius:999px;padding:0 10px;color:#fff;font-size:12px;font-weight:900}
    .grade-critical{background:#dc2626}
    .grade-average{background:#f59e0b;color:#111827}
    .grade-good{background:#2563eb}
    .grade-excellent{background:#16a34a}
    @media(max-width:767px){.sc-page-card{padding:14px}.sc-panel-header{display:block}.sc-table{min-width:760px}}

    /* Detail stat cards: tighter + more centered (page-only) */
    .ppmf-detail-stats .stat-card-ppmf{min-height:86px;display:flex;align-items:center;justify-content:center;gap:14px}
    .ppmf-detail-stats .stat-card-ppmf strong{font-size:24px;line-height:1.05}
    .ppmf-detail-stats .stat-card-ppmf small{font-weight:750;font-size:12px}
    .ppmf-detail-stats .stat-icon-ppmf{width:46px;height:46px;border-radius:14px;display:flex;align-items:center;justify-content:center}
    .ppmf-detail-stats .stat-icon-ppmf i{font-size:18px}
</style>
@endpush

@php
    $divisionName = $division?->name ?? 'Division';
    $rank = $divisionRank ?? null;
    $score = (float) ($divisionScore ?? 0);
    $comparisonWeeks = $comparisonWeeks ?? [];
    $periodHeadingLabel = $periodHeadingLabel ?? null;
    $districtRows = $districtRows ?? collect();
    $totalDistricts = (int) ($totalDistricts ?? $districtRows->count());
    $reportedDistricts = (int) ($reportedDistricts ?? $districtRows->filter(fn ($r) => ($r->reported ?? false))->count());
    $improvedCount = (int) ($improvedCount ?? 0);
    $declinedCount = (int) ($declinedCount ?? 0);

    $scoreMeta = function ($s) {
        $s=(float)$s;
        if($s>=90)return ['grade'=>'A+','label'=>'Excellent','class'=>'excellent'];
        if($s>=70)return ['grade'=>$s>=80?'A':'B','label'=>'Good','class'=>'good'];
        if($s>=50)return ['grade'=>$s>=60?'C':'D','label'=>'Average','class'=>'average'];
        return ['grade'=>'E','label'=>'Critical','class'=>'critical'];
    };
    $meta = $scoreMeta($score);

    $currentWeekLabel = $comparisonWeeks[2]['label'] ?? null;
    $previousWeekLabel = $comparisonWeeks[1]['label'] ?? null;

    $perfMeta = function ($s) {
        $s = (float) $s;
        if ($s >= 80) return ['label' => 'Excellent', 'class' => 'success'];
        if ($s >= 60) return ['label' => 'Good', 'class' => 'primary'];
        if ($s >= 40) return ['label' => 'Average', 'class' => 'warning'];
        return ['label' => 'Critical', 'class' => 'danger'];
    };
@endphp

@section('content')
<div class="page-title-bar mb-4">
    <div>
        <h2 class="page-title mb-1">Division {{ strtoupper($divisionName) }} Scorecard @if($periodHeadingLabel) ({{ $periodHeadingLabel }}) @endif</h2>
        <p class="page-subtitle mb-0">
            Weekly Performance Comparison
            @if(!empty($comparisonWeeks[0]['label']) && !empty($comparisonWeeks[2]['label']))
                <span class="text-muted">· {{ $comparisonWeeks[0]['label'] }} to {{ $comparisonWeeks[2]['label'] }}</span>
            @elseif($currentWeekLabel)
                <span class="text-muted">· Current: {{ $currentWeekLabel }}</span>
            @endif
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('scorecard.index', request()->query()) }}" class="btn btn-gov btn-gov-outline"><i class="bi bi-table"></i> Back to Scorecard</a>
        <button type="button" class="btn btn-gov btn-gov-outline" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
    </div>
</div>

<div class="sc-page-card">
    <div class="row g-3 mb-3 justify-content-center ppmf-detail-stats">
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="stat-card-ppmf">
                <div class="stat-icon-ppmf success">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <div>
                    <span>Division Score</span>
                    <strong>{{ number_format($score,2) }}%</strong>
                    <small>{{ $meta['label'] }} ({{ $meta['grade'] }})</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="stat-card-ppmf">
                <div class="stat-icon-ppmf primary"><i class="bi bi-award"></i></div>
                <div>
                    <span>Division Rank</span>
                    <strong>{{ $rank ? '#'.$rank : '—' }}</strong>
                    <small>Among divisions</small>
                </div>
            </div>
        </div>
        {{-- Current Week card removed (requested) --}}

        @if(false)
        <div class="col-md-6 col-xl-3">
            <div class="card-ppmf stat-card-ppmf h-100">
                <div class="card-ppmf-body d-flex align-items-center gap-3">
                    <div class="stat-icon-ppmf bg-primary-subtle text-primary"><i class="bi bi-award"></i></div>
                    <div>
                        <div class="text-muted" style="font-size:12px;font-weight:800">Division Rank</div>
                        <div style="font-size:22px;font-weight:900">{{ $rank ? '#'.$rank : '—' }}</div>
                        <div class="text-muted" style="font-size:12px;font-weight:800">Among divisions</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card-ppmf stat-card-ppmf h-100">
                <div class="card-ppmf-body d-flex align-items-center gap-3">
                    <div class="stat-icon-ppmf bg-warning-subtle text-warning"><i class="bi bi-buildings"></i></div>
                    <div>
                        <div class="text-muted" style="font-size:12px;font-weight:800">Districts Reported</div>
                        <div style="font-size:22px;font-weight:900">{{ $reportedDistricts }}/{{ $totalDistricts }}</div>
                        <div class="text-muted" style="font-size:12px;font-weight:800">Current week reporting</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card-ppmf stat-card-ppmf h-100">
                <div class="card-ppmf-body d-flex align-items-center gap-3">
                    <div class="stat-icon-ppmf bg-info-subtle text-info"><i class="bi bi-arrow-left-right"></i></div>
                    <div>
                        <div class="text-muted" style="font-size:12px;font-weight:800">Improved / Declined</div>
                        <div style="font-size:22px;font-weight:900">{{ $improvedCount }} / {{ $declinedCount }}</div>
                        <div class="text-muted" style="font-size:12px;font-weight:800">Vs previous week</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
        @endif

    @if(false)
    <div class="sc-panel mb-3">
        <div class="sc-panel-header">
            <div>
                <h5 class="sc-panel-title">Division Summary</h5>
                <div class="sc-panel-subtitle">Score is average of district weighted score percentages.</div>
            </div>
            <div class="text-end sc-muted">
                @if($rank)
                    <div><strong>#{{ $rank }}</strong> rank</div>
                @endif
                <div><span class="sc-grade-badge grade-{{ $meta['class'] }}">{{ $meta['grade'] }}</span> <strong>{{ number_format($score,2) }}%</strong></div>
            </div>
        </div>
    </div>
    @endif

    <div class="sc-panel">
        <div class="sc-panel-header">
            <div>
                <h5 class="sc-panel-title">Districts</h5>
                <div class="sc-panel-subtitle">Weekly comparison (previous to current).</div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="sc-table">
                <thead>
                    <tr>
                        <th class="sc-rank">#</th>
                        <th>District</th>
                        <th style="width:190px">{!! $comparisonWeeks[0]['table_label'] ?? 'Previous Week' !!}</th>
                        <th style="width:190px">{!! $comparisonWeeks[1]['table_label'] ?? 'Previous Week' !!}</th>
                        <th style="width:190px">{!! $comparisonWeeks[2]['table_label'] ?? 'Current Week' !!}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($districtRows as $r)
                        @php
                            $p2 = (float) ($r->previous_2_score ?? 0);
                            $p1 = (float) ($r->previous_1_score ?? 0);
                            $cur = (float) ($r->current_score ?? 0);

                            $arrow = function (float $a, float $b) {
                                if ($b > $a) return ['cls' => 'text-success', 'icon' => 'bi-arrow-up'];
                                if ($b < $a) return ['cls' => 'text-danger', 'icon' => 'bi-arrow-down'];
                                return ['cls' => 'text-secondary', 'icon' => 'bi-dash'];
                            };
                            $a2 = $arrow($p2, $p1);
                            $a1 = $arrow($p1, $cur);
                        @endphp
                        <tr>
                            <td class="sc-rank"><span class="sc-rank-badge">{{ $loop->iteration }}</span></td>
                            <td>
                                <a class="sc-district-name text-decoration-none" target="_blank" href="{{ route('scorecard.district-detail', array_merge(['district' => $r->district_id], request()->query())) }}">
                                    {{ $r->district_name }}
                                    <i class="bi bi-box-arrow-up-right ms-1" style="font-size:12px"></i>
                                </a>
                                <div class="sc-muted">Tier {{ $r->tier ?? '—' }}</div>
                            </td>
                            <td>{{ number_format($p2, 2) }} <i class="bi bi-dash text-secondary"></i></td>
                            <td>{{ number_format($p1, 2) }} <i class="bi {{ $a2['icon'] }} {{ $a2['cls'] }}"></i></td>
                            <td class="fw-bold">{{ number_format($cur, 2) }} <i class="bi {{ $a1['icon'] }} {{ $a1['cls'] }}"></i></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted p-4">No district data found for this division.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection


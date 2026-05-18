@extends('layouts.app')

@section('title', 'District Scorecard | PPMF Portal')
@section('page_title', 'District Scorecard')

@php
    $filters = $filters ?? [];
    $districtName = $district?->name ?? 'District';
    $tier = $summary['tier'] ?? ($district?->tier ?? null);
    $rank = $summary['rank'] ?? null;
    $score = (float) ($summary['score'] ?? 0);
    $reported = (int) ($summary['reported_kpis'] ?? 0);
    $totalKpis = (int) ($summary['total_kpis'] ?? 0);
    $calcType = $summary['calculation_type'] ?? ($calculationType ?? 'general');
    $detailPerPage = (int) ($detailPerPage ?? request('detail_per_page', 10));
    $detailPerPageOptions = [10, 20, 25, 50];

    $gradeMeta = function (float $s) {
        if ($s >= 90) return ['grade' => 'A+', 'label' => 'Excellent', 'class' => 'excellent'];
        if ($s >= 70) return ['grade' => $s >= 80 ? 'A' : 'B', 'label' => 'Good', 'class' => 'good'];
        if ($s >= 50) return ['grade' => $s >= 60 ? 'C' : 'D', 'label' => 'Average', 'class' => 'average'];
        return ['grade' => 'E', 'label' => 'Critical', 'class' => 'critical'];
    };
    $meta = $gradeMeta($score);

    $backUrl = url()->previous() ?: (Route::has('scorecard.index') ? route('scorecard.index', $filters) : '#');
@endphp

@push('styles')
<style>
    .sd-wrap{background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:20px;box-shadow:0 16px 38px rgba(15,23,42,.07);padding:22px}
    .sd-stat{background:#fff;border:1px solid rgba(148,163,184,.25);border-radius:16px;padding:14px 16px;box-shadow:0 10px 24px rgba(15,23,42,.04);position:relative;overflow:hidden}
    .sd-stat:before{content:"";position:absolute;left:0;top:0;bottom:0;width:4px;background:#e2e8f0}
    .sd-stat.stat-rank:before{background:#0f766e}
    .sd-stat.stat-score:before{background:#166534}
    .sd-stat.stat-grade:before{background:#2563eb}
    .sd-stat.stat-kpi:before{background:#f59e0b}
    .sd-stat .k{font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.06em;color:#64748b}
    .sd-stat .v{font-size:20px;font-weight:900;color:#0f172a;margin-top:6px}
    .sd-stat .s{font-size:12px;font-weight:700;color:#64748b;margin-top:4px}
    .sd-grade{display:inline-flex;align-items:center;justify-content:center;min-width:54px;height:30px;border-radius:999px;padding:0 10px;color:#fff;font-weight:900;font-size:12px}
    .sd-grade.excellent{background:#16a34a}.sd-grade.good{background:#2563eb}.sd-grade.average{background:#f59e0b;color:#111827}.sd-grade.critical{background:#dc2626}
    .sd-table{min-width:980px;border-collapse:separate;border-spacing:0}
    .sd-table thead th{
        background: linear-gradient(180deg, #14532d, #166534);
        color:#fff;
        font-size:11.5px;
        font-weight:900;
        text-transform:uppercase;
        letter-spacing:.055em;
        padding:13px 14px;
        border:0;
        white-space:nowrap;
        vertical-align:middle;
    }
    .sd-table tbody td{padding:14px;border-bottom:1px solid #e5e7eb;color:#0f172a;font-size:13px;vertical-align:middle}
    .sd-table tbody tr{transition:.18s ease}
    .sd-table tbody tr:hover{transform:translateY(-1px);box-shadow: inset 4px 0 0 #16a34a;filter: brightness(0.995)}
    .sd-cell{display:flex;align-items:center;justify-content:space-between;gap:10px}
    .sd-trend{font-size:13px;font-weight:900}
    .sd-trend.up{color:#16a34a}.sd-trend.down{color:#dc2626}.sd-trend.eq{color:#64748b}
    .sd-legend{font-size:12px;color:#64748b;font-weight:700}
    .sd-legend i{margin-right:6px}

    /* Pagination styling aligned with Inspection List */
    .inspection-pagination-bar{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:14px 18px;border-top:1px solid #e2e8f0;background:#ffffff}
    .inspection-pagination-summary-group{display:flex;flex-direction:column;gap:3px;min-width:210px}
    .inspection-pagination-summary{color:#334155;font-size:13px;font-weight:850;white-space:nowrap}
    .inspection-pagination-per-page{color:#64748b;font-size:12px;font-weight:750;white-space:nowrap}
    .inspection-pagination-nav{display:flex;align-items:center;justify-content:flex-end;gap:6px;flex-wrap:wrap}
    .inspection-pagination-nav .pagination{margin:0;gap:6px;flex-wrap:wrap}
    .inspection-pagination-nav .page-link{min-width:38px;height:36px;padding:0 12px;border-radius:11px;display:inline-flex;align-items:center;justify-content:center;border:1px solid #cbd5e1;background:#ffffff;color:#14532d;font-size:13px;font-weight:900;text-decoration:none;line-height:1;white-space:nowrap;box-shadow:none}
    .inspection-pagination-nav .page-link:hover{background:#ecfdf3;color:#14532d;border-color:#86efac}
    .inspection-pagination-nav .page-item.active .page-link{background:#166534;color:#ffffff;border-color:#166534;box-shadow:0 8px 18px rgba(22,101,52,0.22)}
    .inspection-pagination-nav .page-item.disabled .page-link{pointer-events:none;color:#94a3b8;background:#f1f5f9;border-color:#e2e8f0;box-shadow:none}
    @media (max-width: 991px){.inspection-pagination-bar{align-items:flex-start;flex-direction:column}.inspection-pagination-summary{white-space:normal}.inspection-pagination-summary-group{min-width:0}.inspection-pagination-nav{justify-content:flex-start;width:100%}}
    @media (max-width: 767px){.inspection-pagination-bar{padding:14px}.inspection-pagination-nav .page-link{min-width:34px;height:34px;padding:0 10px;font-size:12px}}
</style>
@endpush

@section('content')
<div class="page-title-bar mb-4">
    <div>
        <h2 class="page-title mb-1">District {{ $districtName }} Scorecard</h2>
        <p class="page-subtitle mb-0">Period: {{ $periodLabel ?? '—' }} · Calculation: {{ ucfirst(str_replace('_',' ', $calcType)) }}</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ $backUrl }}" class="btn btn-gov btn-gov-outline"><i class="bi bi-arrow-left"></i> Back</a>
        @if(Route::has('scorecard.index'))
            <a href="{{ route('scorecard.index', $filters) }}" class="btn btn-gov btn-gov-outline"><i class="bi bi-table"></i> District Wise</a>
        @endif
        @if(Route::has('scorecard.tier'))
            <a href="{{ route('scorecard.tier', $filters) }}" class="btn btn-gov btn-gov-outline"><i class="bi bi-layers"></i> Tier Wise</a>
        @endif
    </div>
</div>

<div class="sd-wrap mb-3">
    <div class="row g-2">
        <div class="col-md-3">
            <div class="sd-stat stat-rank">
                <div class="k"><i class="bi bi-hash"></i> Rank</div>
                <div class="v">{{ $rank ? $rank : '—' }}</div>
                <div class="s">Overall ranking</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="sd-stat stat-score">
                <div class="k"><i class="bi bi-speedometer2"></i> Total Score</div>
                <div class="v">{{ number_format($score, 2) }}</div>
                <div class="s">Score (0-100)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="sd-stat stat-grade">
                <div class="k"><i class="bi bi-award"></i> Grade / Performance</div>
                <div class="v d-flex align-items-center gap-2">
                    <span class="sd-grade {{ $meta['class'] }}">{{ $meta['grade'] }}</span>
                    <span>{{ $meta['label'] }}</span>
                </div>
                <div class="s">Based on total score</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="sd-stat stat-kpi">
                <div class="k"><i class="bi bi-list-check"></i> Reported KPIs</div>
                <div class="v">{{ $reported }} / {{ $totalKpis }}</div>
                <div class="s">{{ $tier ? ('Tier ' . $tier) : '—' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card-ppmf">
    <div class="card-ppmf-header d-flex align-items-start justify-content-between gap-2 flex-wrap">
        <div>
            <div class="card-ppmf-title">KPI / Performance Indicator Scores</div>
            <div class="text-muted" style="font-size:12px">Row values are weighted. Total score excludes missing KPIs from denominator (management view).</div>
        </div>
        <div class="sd-legend">
            <span class="me-3"><i class="bi bi-arrow-up sd-trend up"></i> Improved</span>
            <span class="me-3"><i class="bi bi-arrow-down sd-trend down"></i> Declined</span>
            <span><i class="bi bi-dash sd-trend eq"></i> No change</span>
        </div>
    </div>
    <div class="card-ppmf-body d-flex align-items-center justify-content-between gap-2 flex-wrap">
        <div class="text-muted" style="font-size:13px;font-weight:850">
            {{ method_exists($rows, 'total') ? 'Total: ' . number_format($rows->total()) . ' KPI(s)' : '' }}
        </div>
        <form method="GET" action="{{ url()->current() }}" class="d-flex align-items-center gap-2">
            @foreach(request()->except(['detail_per_page','page']) as $key => $value)
                @if(is_array($value))
                    @foreach($value as $v)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <span class="text-muted" style="font-size:12px;font-weight:800">Per page</span>
            <select name="detail_per_page" class="form-select form-select-sm" style="width:96px" onchange="this.form.submit()">
                @foreach($detailPerPageOptions as $size)
                    <option value="{{ $size }}" @selected((int)$detailPerPage===(int)$size)>{{ $size }}</option>
                @endforeach
            </select>
        </form>
    </div>
    <div class="card-ppmf-body p-0">
        <div class="table-responsive">
            <table class="table-ppmf mb-0 sd-table">
                <thead>
                    <tr>
                        <th>Performance Indicators</th>
                        <th style="width:110px">Weightage</th>
                        <th style="width:190px">{{ $weekHeaders[0]['label'] ?? 'Previous 2' }}</th>
                        <th style="width:190px">{{ $weekHeaders[1]['label'] ?? 'Previous 1' }}</th>
                        <th style="width:190px">{{ $weekHeaders[2]['label'] ?? 'Current' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($rows->getCollection() ?? $rows) as $r)
                        @php
                            $p2 = $r['previous_2']['weighted_score'] ?? null;
                            $p1 = $r['previous_1']['weighted_score'] ?? null;
                            $c = $r['current']['weighted_score'] ?? null;

                            $trend = function($a, $b){
                                if ($a === null || $b === null) return ['cls'=>'eq','icon'=>'bi-dash'];
                                if ($b > $a) return ['cls'=>'up','icon'=>'bi-arrow-up'];
                                if ($b < $a) return ['cls'=>'down','icon'=>'bi-arrow-down'];
                                return ['cls'=>'eq','icon'=>'bi-dash'];
                            };
                            $t21 = $trend($p2, $p1);
                            $t1c = $trend($p1, $c);
                        @endphp
                        <tr>
                            <td class="fw-bold">{{ $r['kpi_name'] }}</td>
                            <td>{{ number_format((float)$r['weightage'], 2) }}</td>
                            <td>
                                <div class="sd-cell">
                                    <span>{{ $p2 === null ? '—' : number_format((float)$p2, 2) }}</span>
                                    <span class="sd-trend eq"><i class="bi bi-dash"></i></span>
                                </div>
                            </td>
                            <td>
                                <div class="sd-cell">
                                    <span>{{ $p1 === null ? '—' : number_format((float)$p1, 2) }}</span>
                                    <span class="sd-trend {{ $t21['cls'] }}"><i class="bi {{ $t21['icon'] }}"></i></span>
                                </div>
                            </td>
                            <td>
                                <div class="sd-cell">
                                    <span class="fw-bold">{{ $c === null ? '—' : number_format((float)$c, 2) }}</span>
                                    <span class="sd-trend {{ $t1c['cls'] }}"><i class="bi {{ $t1c['icon'] }}"></i></span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted p-4">No KPI score data found for this district.</td></tr>
                    @endforelse
                </tbody>
                @if(($rows->total() ?? 0) > 0)
                    <tfoot>
                        <tr class="fw-bold">
                            <td>Total</td>
                            <td>{{ number_format((float)($totals['weightage'] ?? 0), 2) }}</td>
                            <td>{{ number_format((float)($totals['previous_2'] ?? 0), 2) }}</td>
                            <td>{{ number_format((float)($totals['previous_1'] ?? 0), 2) }}</td>
                            <td>{{ number_format((float)($totals['current'] ?? 0), 2) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
    @if(method_exists($rows, 'hasPages') && $rows->hasPages())
        <div class="card-ppmf-body inspection-pagination-bar">
            <div class="inspection-pagination-summary-group">
                <div class="inspection-pagination-summary">
                    Showing {{ $rows->firstItem() }} to {{ $rows->lastItem() }} of {{ $rows->total() }} KPI(s)
                </div>
                <div class="inspection-pagination-per-page">Per page: {{ $detailPerPage }}</div>
            </div>
            <div class="inspection-pagination-nav">
                {{ $rows->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>
@endsection

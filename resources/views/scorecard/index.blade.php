@extends('layouts.app')

@section('title', 'Chief Minister Governance Scorecard District Wise')
@section('page_title', 'Chief Minister Governance Scorecard District Wise')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/plugins/leaflet/leaflet.css') }}"/>
<style>
    .sc-page-card{background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:20px;box-shadow:0 16px 38px rgba(15,23,42,.07);padding:22px}
    .sc-filter-card{background:linear-gradient(135deg,#f8fafc,#ffffff);border:1px solid rgba(148,163,184,.28);border-radius:18px;padding:18px;margin-bottom:22px}
    .sc-filter-card .form-label{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#475569;margin-bottom:7px}
    .sc-filter-card .form-select,.sc-filter-card .form-control{height:42px;border-radius:11px;border-color:#cbd5e1;font-size:13px;font-weight:600;color:#334155;box-shadow:none}
    .sc-filter-card .form-select:focus,.sc-filter-card .form-control:focus{border-color:#1b6b57;box-shadow:0 0 0 3px rgba(27,107,87,.13)}
    .sc-layout{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(420px,.9fr);gap:22px;align-items:start}
    .sc-panel{background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:18px;box-shadow:0 12px 28px rgba(15,23,42,.05);overflow:hidden}
    .sc-panel-header{padding:16px 18px;border-bottom:1px solid rgba(148,163,184,.22);background:linear-gradient(135deg,#f8fafc,#ffffff);display:flex;align-items:flex-start;justify-content:space-between;gap:14px}
    .sc-panel-title{font-size:15px;font-weight:900;color:#0f172a;margin:0;text-transform:uppercase;letter-spacing:.04em}
    .sc-panel-subtitle{font-size:12px;color:#64748b;margin-top:4px}
    .sc-table{width:100%;border-collapse:separate;border-spacing:0;margin:0}
    .sc-table thead th{background:linear-gradient(180deg,var(--gov-green-dark) 0%,var(--gov-green) 100%);color:#fff;padding:12px;font-size:11px;text-transform:uppercase;letter-spacing:.06em;font-weight:900;white-space:nowrap;border-bottom:2px solid var(--gold)}
    .sc-table tbody td{padding:12px;border-bottom:1px solid rgba(148,163,184,.18);font-size:13px;font-weight:700;color:#0f172a;vertical-align:middle}
    .sc-table tbody tr{transition:background .12s}
    .sc-table tbody tr:hover{background:#f0fdf9}
    .sc-table tbody tr.map-highlighted{background:#ecfdf5!important;box-shadow:inset 4px 0 0 var(--gov-green)}
    .sc-rank{width:58px;text-align:center}
    .sc-rank-badge{width:34px;height:34px;border-radius:12px;background:#ecfdf5;color:#166534;display:inline-flex;align-items:center;justify-content:center;font-weight:900}
    .sc-district-name{font-weight:900;color:#0f172a;text-transform:uppercase;text-decoration:none}
    .sc-district-name:hover{color:var(--gov-green)}
    .sc-muted{font-size:12px;color:#64748b;font-weight:600}
    .sc-grade-badge{display:inline-flex;align-items:center;justify-content:center;min-width:44px;height:26px;border-radius:999px;padding:0 8px;color:#fff;font-size:11px;font-weight:900}
    .grade-critical{background:#dc2626}.grade-average{background:#f59e0b;color:#111827}.grade-good{background:#2563eb}.grade-excellent{background:#16a34a}
    .sc-progress{height:7px;background:#e2e8f0;border-radius:999px;overflow:hidden;min-width:90px}
    .sc-progress span{display:block;height:100%;border-radius:999px}
    .bar-critical{background:#dc2626}.bar-average{background:#f59e0b}.bar-good{background:#2563eb}.bar-excellent{background:#16a34a}
    .sc-map-panel{position:sticky;top:88px}
    .sc-pagination-wrap{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:14px 18px;background:#f8fafc;border-top:1px solid rgba(148,163,184,.22)}
    .sc-pagination-wrap .pagination{margin:0;gap:5px;flex-wrap:wrap}
    .sc-pagination-wrap .page-link{border-radius:10px!important;min-width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;color:var(--gov-green);font-weight:900;border-color:#cbd5e1;box-shadow:none}
    .sc-pagination-wrap .page-item.active .page-link{background:var(--gov-green);border-color:var(--gov-green);color:#fff}
    .sc-empty{padding:28px;text-align:center;color:#64748b;font-weight:700;background:#f8fafc}

    /* Status cards (match Tier Wise) */
    .sc-perf-card{display:flex;align-items:center;gap:12px;border:1px solid rgba(148,163,184,.25);border-radius:16px;background:#fff;padding:12px 14px;box-shadow:0 10px 24px rgba(15,23,42,.04);transition:.15s ease;min-height:72px}
    .sc-perf-card:hover{transform:translateY(-1px);border-color:rgba(15,118,110,.35);background:#f8fafc}
    .sc-perf-card.active{border-color:var(--gov-green);box-shadow:0 12px 26px rgba(0,107,63,.14)}
    .sc-perf-ico{width:36px;height:36px;border-radius:14px;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:16px;flex:0 0 auto}
    .sc-perf-ico.excellent{background:#16a34a}
    .sc-perf-ico.good{background:#2563eb}
    .sc-perf-ico.average{background:#f59e0b}
    .sc-perf-ico.critical{background:#dc2626}
    .sc-perf-ico.unreported{background:#64748b}
    .sc-perf-title{font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.06em;color:#334155;line-height:1.2}
    .sc-perf-count{font-size:20px;font-weight:900;color:#0f172a;line-height:1.1}
    .sc-perf-sub{font-size:12px;color:#64748b;font-weight:700;line-height:1.2}

    /* ═══ LEAFLET MAP ═══════════════════════════════════════ */
    /* Blank background — no world map tiles visible */
    #ppmfLeafletMap{height:490px;background:#edf2ee;position:relative;z-index:1;border-radius:0}
    .leaflet-tile-pane{display:none!important}
    .leaflet-control-attribution{display:none!important}
    .leaflet-control-zoom a{background:#fff;border-color:#e2e8f0;color:var(--gov-green);font-weight:950}
    .leaflet-control-zoom a:hover{background:#ecfdf5}

    /* District / Division name labels */
    .ppmf-label{background:transparent!important;border:none!important;box-shadow:none!important;
        font-family:inherit;font-weight:800;color:#1e293b;text-align:center;white-space:nowrap;
        pointer-events:none;line-height:1.1;
        text-shadow:0 0 3px #fff,0 0 3px #fff,0 0 3px #fff,0 0 5px #fff}
    .ppmf-label.district-lbl{font-size:9px;letter-spacing:.03em}
    .ppmf-label.division-lbl{font-size:12px;letter-spacing:.05em;color:#0f172a}
    .ppmf-label .lbl-box{display:inline-flex;flex-direction:column;align-items:center;justify-content:center;gap:1px;padding:2px 5px;border-radius:8px;border:1px solid rgba(148,163,184,.35);background:rgba(255,255,255,.72);backdrop-filter:blur(3px)}
    .ppmf-label .lbl-name{font-weight:950}
    .ppmf-label .lbl-sub{font-size:8px;font-weight:800;opacity:.85;text-transform:none;letter-spacing:0}
    .ppmf-label .lbl-box.lfb-excellent{background:rgba(220,252,231,.78);border-color:rgba(22,163,74,.30);color:#14532d}
    .ppmf-label .lbl-box.lfb-good{background:rgba(219,234,254,.80);border-color:rgba(37,99,235,.30);color:#1e40af}
    .ppmf-label .lbl-box.lfb-average{background:rgba(254,249,195,.82);border-color:rgba(245,158,11,.33);color:#854d0e}
    .ppmf-label .lbl-box.lfb-critical{background:rgba(254,226,226,.82);border-color:rgba(220,38,38,.33);color:#7f1d1d}
    .ppmf-label .lbl-box.lfb-unreported{background:rgba(241,245,249,.86);border-color:rgba(100,116,139,.28);color:#475569}

    /* Popup */
    .leaflet-popup-content-wrapper{border-radius:12px!important;padding:0!important;overflow:hidden;box-shadow:0 8px 28px rgba(0,0,0,.16)!important}
    .leaflet-popup-content{margin:0!important;min-width:190px}
    .leaflet-popup-tip-container{display:none}
    .lf-head{background:linear-gradient(180deg,var(--gov-green-dark) 0%,var(--gov-green) 100%);padding:11px 14px;border-bottom:2px solid var(--gold)}
    .lf-head h4{color:#fff;font-size:13px;font-weight:900;margin:0 0 1px}
    .lf-head span{color:rgba(255,255,255,.55);font-size:11px}
    .lf-body{padding:10px 14px}
    .lf-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;font-size:12.5px}
    .lf-lbl{color:#64748b;font-weight:600}.lf-val{font-weight:900;color:#0f172a}
    .lf-badge{display:inline-block;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:800}
    .lfb-excellent{background:#dcfce7;color:#14532d}.lfb-good{background:#dbeafe;color:#1e40af}
    .lfb-average{background:#fef9c3;color:#854d0e}.lfb-critical{background:#fee2e2;color:#7f1d1d}.lfb-unreported{background:#f1f5f9;color:#64748b}
    .lf-link{display:block;margin-top:8px;padding:7px 10px;background:var(--gov-green-light);border-radius:7px;text-align:center;font-size:12px;font-weight:900;color:var(--gov-green);text-decoration:none;border:1px solid rgba(0,107,63,.20)}
    .lf-link:hover{background:#dff2e7;border-color:rgba(0,107,63,.32);color:var(--gov-green-dark)}

    /* Map panel chrome */
    .map-view-toggle{display:flex;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden}
    .map-view-btn{height:30px;padding:0 13px;border:none;background:none;font-size:12px;font-weight:900;color:#64748b;cursor:pointer;font-family:inherit;transition:all .15s}
    .map-view-btn.active{background:var(--gov-green);color:#fff}
    .map-view-btn:hover:not(.active){background:var(--gov-green-light);color:var(--gov-green)}
    .map-spinner{width:26px;height:26px;border:3px solid #e2e8f0;border-top-color:var(--gov-green);border-radius:50%;animation:mapspin .7s linear infinite}
    .map-fit-btn{background:none;border:none;cursor:pointer;color:var(--gov-green);font-size:11.5px;font-weight:900;padding:0}
    .map-fit-btn:hover{color:var(--gov-green-dark)}
    @keyframes mapspin{to{transform:rotate(360deg)}}
    .map-loading-overlay{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;z-index:20;background:#edf2ee}
    .map-legend-bar{display:flex;flex-wrap:wrap;gap:10px;padding:10px 14px;border-top:1px solid rgba(148,163,184,.18);background:#fafcfb}
    .map-leg-item{display:inline-flex;align-items:center;gap:5px;font-size:11.5px;font-weight:700;color:#334155}
    .map-leg-dot{width:12px;height:12px;border-radius:3px;display:inline-block;flex-shrink:0}
    .map-status-bar{display:flex;align-items:center;justify-content:space-between;padding:8px 14px;background:#f8fafc;border-bottom:1px solid rgba(148,163,184,.15);font-size:11.5px;color:#64748b;font-weight:600}

    @media(max-width:1199px){.sc-layout{grid-template-columns:1fr}.sc-map-panel{position:relative;top:auto}#ppmfLeafletMap{height:420px}}
    @media(max-width:767px){.sc-page-card{padding:14px}.sc-panel-header{display:block}#ppmfLeafletMap{height:320px}.sc-table{min-width:700px}.sc-pagination-wrap{align-items:flex-start}}
</style>
@endpush

@section('content')
@php
    $filters            = $filters ?? [];
    $selectedPeriod     = $filters['period'] ?? 'weekly';
    $selectedPerformance= $filters['performance'] ?? 'all';
    $selectedWeekRange  = $filters['week_range'] ?? '';
    $selectedMonth      = $filters['month'] ?? now()->format('m');
    $selectedYear       = $filters['year'] ?? now()->format('Y');
    $selectedAreaType   = $filters['area_type'] ?? 'district';
    $isDivision         = $selectedAreaType === 'division';
    $selectedKpiCategoryId    = $filters['kpi_category_id'] ?? '';
    $selectedPerPage    = (int)($filters['per_page'] ?? 10);
    $perPageOptions     = [10, 25, 50, 100];
    $mainRoute  = Route::has('scorecard.index') ? route('scorecard.index') : url()->current();
    $tierRoute  = Route::has('scorecard.tier')  ? route('scorecard.tier')  : '#';

    $tableRanking = $isDivision ? ($divisionRanking ?? null) : ($districtRanking ?? null);
    // Keep legacy variable name used throughout the Blade below.
    $districtRanking = $tableRanking;

    $districtRankingItems = method_exists($tableRanking ?? null, 'getCollection')
        ? $tableRanking->getCollection()->values()
        : collect($tableRanking ?? [])->values();
    $pageOffset = method_exists($tableRanking ?? null, 'currentPage')
        ? (($tableRanking->currentPage() - 1) * $tableRanking->perPage())
        : 0;

    $scoreMeta = function ($score) {
        $score = (float)$score;
        if ($score >= 90) return ['grade'=>'A+','label'=>'Excellent','class'=>'excellent'];
        if ($score >= 80) return ['grade'=>'A', 'label'=>'Good',     'class'=>'good'];
        if ($score >= 70) return ['grade'=>'B', 'label'=>'Good',     'class'=>'good'];
        if ($score >= 60) return ['grade'=>'C', 'label'=>'Average',  'class'=>'average'];
        if ($score >= 50) return ['grade'=>'D', 'label'=>'Average',  'class'=>'average'];
        return                   ['grade'=>'E', 'label'=>'Critical', 'class'=>'critical'];
    };

    $monthOptions = ['01'=>'January','02'=>'February','03'=>'March','04'=>'April','05'=>'May',
                     '06'=>'June','07'=>'July','08'=>'August','09'=>'September','10'=>'October',
                     '11'=>'November','12'=>'December'];
    $yearOptions  = range((int)now()->format('Y'), (int)now()->format('Y') - 5);

    // Leaflet map data (keys MUST match GeoJSON district names)
    $districtScores = $districtScores ?? [];
    $districtMapIds = $districtMapIds ?? [];
    $divisionScores = $divisionScores ?? [];
    $divisionMapIds = $divisionMapIds ?? [];
    $districtMapRanks = $districtMapRanks ?? [];
    $divisionMapRanks = $divisionMapRanks ?? [];

    // Inject map arrays with UPPERCASE keys for JS lookups
    $mapScores = collect($districtScores)->mapWithKeys(function ($score, $name) {
        $nm = strtoupper((string) $name);
        return [$nm => (float) $score];
    })->all();
    $mapDistrictIds = collect($districtMapIds)->mapWithKeys(function ($id, $name) {
        $nm = strtoupper((string) $name);
        return [$nm => $id];
    })->all();
    $mapDistrictRanks = collect($districtMapRanks)->mapWithKeys(function ($rank, $name) {
        $nm = strtoupper((string) $name);
        return [$nm => (int) $rank];
    })->all();
    $mapDivisionScores = collect($divisionScores)->mapWithKeys(function ($score, $name) {
        $nm = strtoupper((string) $name);
        return [$nm => (float) $score];
    })->all();
    $mapDivisionIds = collect($divisionMapIds)->mapWithKeys(function ($id, $name) {
        $nm = strtoupper((string) $name);
        return $nm ? [$nm => (int) $id] : [];
    })->all();
    $mapDivisionRanks = collect($divisionMapRanks)->mapWithKeys(function ($rank, $name) {
        $nm = strtoupper((string) $name);
        return $nm ? [$nm => (int) $rank] : [];
    })->all();

    $perfHref = function (string $key) use ($mainRoute, $selectedPerformance) {
        $query = request()->query();
        if ($selectedPerformance === $key) { unset($query['performance']); } else { $query['performance'] = $key; }
        $query['page'] = 1;
        return $mainRoute . (count($query) ? ('?' . http_build_query($query)) : '');
    };

    $perfCards = [
        ['key'=>'excellent','title'=>'Excellent','range'=>'90-100','icon'=>'bi-trophy-fill','class'=>'excellent','count'=>(int)($summary['excellent_count'] ?? 0)],
        ['key'=>'good','title'=>'Good','range'=>'70-89','icon'=>'bi-check-circle-fill','class'=>'good','count'=>(int)($summary['good_count'] ?? 0)],
        ['key'=>'average','title'=>'Average','range'=>'50-69','icon'=>'bi-exclamation-circle-fill','class'=>'average','count'=>(int)($summary['average_count'] ?? 0)],
        ['key'=>'critical','title'=>'Critical','range'=>'< 50','icon'=>'bi-x-octagon-fill','class'=>'critical','count'=>(int)($summary['critical_count'] ?? 0)],
    ];
@endphp

<div class="page-title-bar mb-4">
    <div>
        <h2 class="page-title mb-1">Chief Minister Governance Scorecard District Wise</h2>
        <p class="page-subtitle mb-0">Punjab districts · Click district name or map polygon to view detail scorecard.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ $tierRoute }}" class="btn btn-gov btn-gov-outline"><i class="bi bi-layers"></i> Tier Wise</a>
        <button type="button" class="btn btn-gov btn-gov-outline" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
    </div>
</div>

<div class="sc-page-card">

    {{-- ══ SHARED FILTER FORM ══════════════════════════════════
         area_type here controls BOTH table AND map automatically
    ══════════════════════════════════════════════════════════ --}}
    <div class="sc-filter-card">
        <form method="GET" action="{{ $mainRoute }}" id="scorecardFilters">
            <input type="hidden" name="performance" id="performanceInput" value="{{ $selectedPerformance }}">
            <input type="hidden" name="per_page" id="perPageInput" value="{{ $selectedPerPage }}">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-6 col-lg-3">
                    <label class="form-label">Period</label>
                    <select name="period" class="form-select">
                        <option value="weekly"  @selected($selectedPeriod==='weekly') >Weekly</option>
                        <option value="monthly" @selected($selectedPeriod==='monthly')>Monthly</option>
                        <option value="yearly"  @selected($selectedPeriod==='yearly') >Yearly</option>
                        <option value="all"     @selected($selectedPeriod==='all')    >All Time</option>
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-3" data-period-field="week">
                    <label class="form-label">Week</label>
                    <select name="week_range" class="form-select" id="weekRangeSelect">
                        <option value="">Select Week</option>
                        @foreach(($weekOptions ?? []) as $value => $label)
                            <option value="{{ $value }}" @selected((string)$selectedWeekRange===(string)$value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label class="form-label">Month</label>
                    <select name="month" class="form-select">
                        @foreach($monthOptions as $value => $label)
                            <option value="{{ $value }}" @selected((string)$selectedMonth===(string)$value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label class="form-label">Year</label>
                    <select name="year" class="form-select">
                        @foreach($yearOptions as $year)
                            <option value="{{ $year }}" @selected((string)$selectedYear===(string)$year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row g-3 align-items-end mt-0">
                {{-- area_type controls both table AND map --}}
                <div class="col-12 col-md-6 col-lg-4">
                    <label class="form-label">Area Type</label>
                    <select name="area_type" class="form-select" id="areaTypeSelect">
                        <option value="district" @selected($selectedAreaType==='district')>District</option>
                        <option value="division" @selected($selectedAreaType==='division')>Division</option>
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <label class="form-label">Calculation</label>
                    <select name="calculation_type" class="form-select">
                        <option value="general" selected>General</option>
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-4 d-flex align-items-end">
                    <a href="{{ $mainRoute }}" class="btn btn-gov btn-gov-outline w-100" id="scorecardResetBtn">
                        <i class="bi bi-x-circle"></i> Reset
                    </a>
                </div>
            </div>
            <button type="submit" class="d-none">Apply</button>
        </form>
    </div>

    {{-- Status cards (AJAX) --}}
    <div class="mb-2" id="summaryCardsContainer">
        @include('scorecard.partials.index-status-cards', ['summary' => $summary ?? [], 'filters' => $filters ?? []])
    </div>

    {{-- ══ MAIN LAYOUT: Table LEFT · Map RIGHT ════════════════ --}}
    <div class="sc-layout mt-3">

        {{-- ── LEFT: Ranking Table ──────────────────────────── --}}
        <div class="sc-panel" id="scorecardTablePanel">
            <div class="sc-panel-header">
                <div>
                    <h5 class="sc-panel-title" id="tablePanelTitle">
                        @if($selectedAreaType === 'division') Division @else District @endif Ranking
                    </h5>
                    <div class="sc-panel-subtitle">Click district name to view detail. Map focus changes only through direct map interaction.</div>
                </div>
                <div class="d-flex flex-column align-items-end gap-2">
                    <div class="text-end sc-muted">
                        <strong>{{ method_exists($districtRanking ?? null,'total') ? $districtRanking->total() : $districtRankingItems->count() }}</strong> total
                    </div>
                    <form method="GET" action="{{ $mainRoute }}" class="d-flex align-items-center gap-2">
                        @foreach(request()->except(['per_page','page']) as $key => $value)
                            @if(is_array($value))
                                @foreach($value as $v)<input type="hidden" name="{{ $key }}[]" value="{{ $v }}">@endforeach
                            @else
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <span class="sc-muted">Per page</span>
                        <select name="per_page" class="form-select form-select-sm" style="width:90px">
                            @foreach($perPageOptions as $size)
                                <option value="{{ $size }}" @selected((int)$selectedPerPage===(int)$size)>{{ $size }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>

            @if($districtRankingItems->count())
                <div class="table-responsive">
                    <table class="sc-table" id="districtTable">
                        <thead>
                            <tr>
                                <th class="sc-rank">#</th>
                                <th>{{ $selectedAreaType === 'division' ? 'Division' : 'District' }}</th>
                                <th>Grade / Score</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($districtRankingItems as $row)
                                @php
                                    $score       = (float)($row->score_percentage ?? 0);
                                    $meta        = $scoreMeta($score);
                                    $areaName    = $isDivision
                                                    ? (optional($row->division ?? null)->name ?? 'N/A')
                                                    : (optional($row->district ?? null)->name ?? 'N/A');
                                    $rank        = $pageOffset + $loop->iteration;
                                    if ($isDivision) {
                                        $detailUrl = Route::has('scorecard.division-detail')
                                                    ? route('scorecard.division-detail', array_merge(['division' => $row->division_id], request()->query()))
                                                    : '#';
                                    } else {
                                        $detailUrl = Route::has('scorecard.district-detail')
                                                    ? route('scorecard.district-detail', array_merge(['district' => $row->district_id, 'return_url' => route('scorecard.index', request()->query())], request()->query()))
                                                    : '#';
                                    }
                                @endphp
                                <tr data-area="{{ strtoupper($areaName) }}" data-detail="{{ $detailUrl }}">
                                    <td class="sc-rank"><span class="sc-rank-badge">{{ $rank }}</span></td>
                                    <td>
                                        <a class="sc-district-name" target="_blank" rel="noopener" href="{{ $detailUrl }}" onclick="event.stopPropagation()">
                                            {{ $areaName }}<i class="bi bi-box-arrow-up-right ms-1" style="font-size:11px"></i>
                                        </a>
                                        <div class="sc-muted">Punjab {{ $selectedAreaType === 'division' ? 'Division' : 'District' }}</div>
                                    </td>
                                    <td>
                                        <span class="sc-grade-badge grade-{{ $meta['class'] }}">{{ $meta['grade'] }}</span>
                                        <strong class="ms-1">{{ number_format($score,2) }}%</strong>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="sc-progress">
                                                <span class="bar-{{ $meta['class'] }}" style="width:{{ min(100,max(0,$score)) }}%"></span>
                                            </div>
                                            <strong>{{ $meta['label'] }}</strong>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if(method_exists($districtRanking ?? null,'hasPages') && $districtRanking->hasPages())
                    <div class="sc-pagination-wrap">
                        <div class="sc-muted">Showing {{ $districtRanking->firstItem() }}–{{ $districtRanking->lastItem() }} of {{ $districtRanking->total() }}</div>
                        {{ $districtRanking->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            @else
                <div class="sc-empty"><i class="bi bi-info-circle d-block fs-4 mb-2"></i>No data found.</div>
            @endif
        </div>

        {{-- ── RIGHT: Leaflet Map ───────────────────────────── --}}
        <div class="sc-map-panel">
            <div class="sc-panel">

                {{-- Map header --}}
                <div class="sc-panel-header">
                    <div>
                        <h5 class="sc-panel-title" id="mapPanelTitle">Punjab KPI Map</h5>
                        <div class="sc-panel-subtitle" id="mapPanelSub">
                            {{ $selectedAreaType === 'division' ? '9 Divisions' : '36 Districts' }} · Click to view scorecard
                        </div>
                    </div>
                    {{-- Toggle synced with area_type filter --}}
                    <div class="map-view-toggle">
                        <button class="map-view-btn {{ $selectedAreaType!=='division'?'active':'' }}"
                                id="mapBtnDistrict" onclick="ppmfMap.switchView('district')">Districts</button>
                        <button class="map-view-btn {{ $selectedAreaType==='division'?'active':'' }}"
                                id="mapBtnDivision" onclick="ppmfMap.switchView('division')">Divisions</button>
                    </div>
                </div>

                {{-- Status bar --}}
                <div class="map-status-bar">
                    <span id="mapStatusText">Loading Punjab boundary data…</span>
                    <button onclick="ppmfMap.fitAll()" class="map-fit-btn">
                        <i class="bi bi-fullscreen"></i> Fit Punjab
                    </button>
                </div>

                {{-- MAP DIV — blank background, only Punjab GeoJSON shown --}}
                <div style="position:relative">
                    <div id="ppmfLeafletMap">
                        <div class="map-loading-overlay" id="mapLoader">
                            <div class="map-spinner"></div>
                            <span style="font-size:12px;color:#475569;font-weight:700">Loading Punjab map…</span>
                        </div>
                    </div>
                </div>

                {{-- Legend --}}
                <div class="map-legend-bar">
                    <span class="map-leg-item"><span class="map-leg-dot" style="background:#16a34a"></span>Excellent ≥90%</span>
                    <span class="map-leg-item"><span class="map-leg-dot" style="background:#2563eb"></span>Good 70–89%</span>
                    <span class="map-leg-item"><span class="map-leg-dot" style="background:#f59e0b"></span>Average 50–69%</span>
                    <span class="map-leg-item"><span class="map-leg-dot" style="background:#dc2626"></span>Critical &lt;50%</span>
                    <span class="map-leg-item"><span class="map-leg-dot" style="background:#94a3b8"></span>Unreported</span>
                </div>
            </div>
        </div>

    </div>{{-- end sc-layout --}}
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/plugins/leaflet/leaflet.js') }}"></script>
<script>
/* ════════════════════════════════════════════════════════
   PPMF LEAFLET MAP — PUNJAB ONLY
   • No tile layer → blank white/green background, only Punjab
   • District labels rendered with L.divIcon on centroid
   • area_type dropdown (District/Division) controls view
   • Table row hover/click → highlight polygon
   • Polygon hover/click → highlight table row + popup
════════════════════════════════════════════════════════ */
const ppmfMap = (function(){

    /* Scores injected from PHP — uppercase keys */
    let SCORES   = @json($mapScores);
    let DIST_IDS = @json($mapDistrictIds);
    let RANKS    = @json($mapDistrictRanks);
    let DIV_IDS  = @json($mapDivisionIds);
    let DIV_SCORES = @json($mapDivisionScores);
    let DIV_RANKS  = @json($mapDivisionRanks);
    const AREA_TYPE = '{{ $selectedAreaType }}';

    /* Detail URL template */
    function detailUrl(did){
        if(!did) return '#';
        @if(Route::has('scorecard.district-detail'))
        var base = '{{ route("scorecard.district-detail", ["district"=>"__ID__"]) }}'.replace('__ID__', did);
        @else
        var base = '#';
        @endif
        var q = @json(request()->query());
        var qs = Object.keys(q).length ? '?'+new URLSearchParams(q).toString() : '';
        return base + qs;
    }

    function divisionDetailUrl(divId){
        if(!divId) return '#';
        @if(Route::has('scorecard.division-detail'))
        var base = '{{ route("scorecard.division-detail", ["division"=>"__ID__"]) }}'.replace('__ID__', divId);
        @else
        var base = '#';
        @endif
        var q = @json(request()->query());
        var qs = Object.keys(q).length ? '?'+new URLSearchParams(q).toString() : '';
        return base + qs;
    }

    function openInNewTab(url){
        if(!url || url === '#') return;
        window.open(url, '_blank', 'noopener');
    }

    function normalizeUpperKeyMap(obj){
        const out = {};
        if(!obj) return out;
        Object.keys(obj).forEach(function(k){
            out[String(k).toUpperCase()] = obj[k];
        });
        return out;
    }

    function rebuildDistrictPopup(layer){
        if(!layer || !layer.feature || !layer.feature.properties) return;
        var name = layer.feature.properties.name || '';
        var div  = layer.feature.properties.division || '';
        var nm   = String(name).toUpperCase();
        var sc   = SCORES[nm];
        var gr   = gradeStr(sc);
        var did  = DIST_IDS[nm];
        var rk   = RANKS[nm];

        layer.bindPopup(
            '<div class="lf-head"><h4>'+name+'</h4><span>'+div+' Division</span></div>'
            +'<div class="lf-body">'
            +'<div class="lf-row"><span class="lf-lbl">Rank</span><span class="lf-val">'+(rk?('#'+rk):'—')+'</span></div>'
            +'<div class="lf-row"><span class="lf-lbl">Score</span><span class="lf-val">'+(sc!==undefined&&sc!==null?Number(sc).toFixed(2)+'%':'Unreported')+'</span></div>'
            +'<div class="lf-row"><span class="lf-lbl">Grade</span><span class="lf-badge '+gr.cls+'">'+gr.txt+'</span></div>'
            +(did?'<a class="lf-link" href="'+detailUrl(did)+'">View District Scorecard &rarr;</a>':'')
            +'<div style="margin-top:8px;font-size:11px;color:#64748b;font-weight:700;text-align:center">Tip: click district to open detail in new tab</div>'
            +'</div>',
            {maxWidth:220}
        );
    }

    function rebuildDivisionPopup(layer){
        if(!layer || !layer.feature || !layer.feature.properties) return;
        var div = layer.feature.properties.name || '';
        var nm  = String(div).toUpperCase();
        var sc  = DIV_SCORES[nm];
        var gr  = gradeStr(sc);
        var divId = DIV_IDS[nm];
        var rk  = DIV_RANKS[nm];

        layer.bindPopup(
            '<div class="lf-head"><h4>'+div+' Division</h4></div>'
            +'<div class="lf-body">'
            +'<div class="lf-row"><span class="lf-lbl">Rank</span><span class="lf-val">'+(rk?('#'+rk):'—')+'</span></div>'
            +'<div class="lf-row"><span class="lf-lbl">Score</span><span class="lf-val">'+(sc!==undefined&&sc!==null?Number(sc).toFixed(2)+'%':'Unreported')+'</span></div>'
            +'<div class="lf-row"><span class="lf-lbl">Grade</span><span class="lf-badge '+gr.cls+'">'+gr.txt+'</span></div>'
            +(divId ? '<a class="lf-link" href="'+divisionDetailUrl(divId)+'" target="_blank" rel="noopener">View Division Scorecard &rarr;</a>' : '')
            +'<div style="margin-top:8px;font-size:11px;color:#64748b;font-weight:700;text-align:center">Tip: click division to open detail in new tab</div>'
            +'</div>',
            {maxWidth:220}
        );
    }

    function setData(payload){
        if(!payload) return;

        SCORES = normalizeUpperKeyMap(payload.scores || payload.score || payload.SCORES || payload);
        DIST_IDS = normalizeUpperKeyMap(payload.ids || payload.DIST_IDS || {});
        RANKS = normalizeUpperKeyMap(payload.ranks || payload.RANKS || {});
        DIV_SCORES = normalizeUpperKeyMap(payload.div_scores || payload.divScores || payload.DIV_SCORES || {});
        DIV_IDS = normalizeUpperKeyMap(payload.div_ids || payload.divIds || payload.DIV_IDS || DIV_IDS);
        DIV_RANKS = normalizeUpperKeyMap(payload.div_ranks || payload.divRanks || payload.DIV_RANKS || {});

        if(distLayer){
            distLayer.eachLayer(function(layer){
                var name = layer.feature?.properties?.name || '';
                var nm = String(name).toUpperCase();
                layer.setStyle({fillColor:scoreColor(SCORES[nm]),weight:1.5,color:'#fff',fillOpacity:.85});
                rebuildDistrictPopup(layer);
            });
        }

        if(divLayer){
            divLayer.eachLayer(function(layer){
                var div = layer.feature?.properties?.name || '';
                var nm = String(div).toUpperCase();
                layer.setStyle({fillColor:scoreColor(DIV_SCORES[nm]),weight:2,color:'#fff',fillOpacity:.78});
                rebuildDivisionPopup(layer);
            });
        }


        // Update label colour classes based on refreshed scores
        if(districtLabelMarkers && districtLabelMarkers.length){
            districtLabelMarkers.forEach(function(m){
                if(!m || !m._ppmfLbl) return;
                var nm = String(m._ppmfLbl.name || '').toUpperCase();
                var gr = gradeStr(SCORES[nm]);
                m._ppmfLbl.cls = gr.cls;
            });
        }
        updateDistrictLabels();
    }

    /* Colour by score */
    function scoreColor(s){
        if(s===undefined||s===null) return '#94a3b8';
        if(s>=90) return '#16a34a';
        if(s>=70) return '#2563eb';
        if(s>=50) return '#f59e0b';
        return '#dc2626';
    }
    function gradeStr(s){
        if(s===undefined||s===null) return {txt:'Unreported',cls:'lfb-unreported'};
        if(s>=90) return {txt:'A+ Excellent',cls:'lfb-excellent'};
        if(s>=80) return {txt:'A Good',      cls:'lfb-good'};
        if(s>=70) return {txt:'B Good',      cls:'lfb-good'};
        if(s>=60) return {txt:'C Average',   cls:'lfb-average'};
        if(s>=50) return {txt:'D Average',   cls:'lfb-average'};
        return          {txt:'E Critical',   cls:'lfb-critical'};
    }

    /* Division colours */
    const DIV_COL = {
        'Bahawalpur':'#d97706','Dera Ghazi Khan':'#0891b2','Faisalabad':'#7c3aed',
        'Gujranwala':'#0f766e','Lahore':'#1e40af','Multan':'#c9952a',
        'Rawalpindi':'#0e8c6a','Sahiwal':'#65a30d','Sargodha':'#dc2626'
    };

    var leafletMap, distLayer, divLayer, labelGroup;
    var districtLabelMarkers = [];
    var layersByName = {};   /* UPPERCASE name → leaflet layer */
    var currentView  = AREA_TYPE === 'division' ? 'division' : 'district';

    /* ── INIT ──────────────────────────────────────────── */
    function init(){
        if(!document.getElementById('ppmfLeafletMap')) return;

        leafletMap = L.map('ppmfLeafletMap',{
            zoomControl:true,
            scrollWheelZoom:true,
            zoomSnap: 0.25,
            minZoom: 6.5,
            maxZoom: 13,
            /* Stable Punjab overview; only explicit map actions change focus. */
            center:[31.0, 72.5], zoom:6.75
        });

        /* NO tile layer at all → only our GeoJSON is visible */
        /* White/light background comes from CSS on #ppmfLeafletMap */

        labelGroup = L.layerGroup().addTo(leafletMap);
        leafletMap.on('zoomend', updateDistrictLabels);

        /* Load both files */
        Promise.all([
            fetch('{{ asset("assets/data/punjab_districts.geojson") }}').then(r=>r.json()),
            fetch('{{ asset("assets/data/punjab_divisions.geojson") }}').then(r=>r.json())
        ]).then(function([dists, divs]){
            buildDistrictLayer(dists);
            buildDivisionLayer(divs);
            document.getElementById('mapLoader').style.display='none';
            switchView(currentView, true);
        }).catch(function(){
            document.getElementById('mapLoader').innerHTML =
                '<div style="text-align:center;padding:20px">'
                +'<i class="bi bi-exclamation-triangle fs-2 text-warning"></i>'
                +'<p style="font-size:12px;color:#64748b;margin-top:8px;font-weight:600">'
                +'GeoJSON files not found!<br>'
                +'Place these in <code>public/assets/data/</code>:<br>'
                +'<code>punjab_districts.geojson</code><br>'
                +'<code>punjab_divisions.geojson</code>'
                +'</p></div>';
        });

        /* Sync when area_type dropdown changes */
        var sel = document.getElementById('areaTypeSelect');
        if(sel) sel.addEventListener('change', function(){ switchView(this.value==='division'?'division':'district'); });

        /* Table row hover → map highlight */
    }

    /* ── DISTRICT LAYER ────────────────────────────────── */
    function buildDistrictLayer(data){
        distLayer = L.geoJSON(data,{
            style:function(f){
                var nm = (f.properties.name||'').toUpperCase();
                return {fillColor:scoreColor(SCORES[nm]),weight:1.5,color:'#fff',fillOpacity:.85};
            },
            onEachFeature:function(f,layer){
                var name = f.properties.name || '';
                var nm   = name.toUpperCase();
                var div  = f.properties.division || '';
                var sc   = SCORES[nm];
                var gr   = gradeStr(sc);
                var did  = DIST_IDS[nm];
                layersByName[nm] = layer;

                layer.bindPopup(
                    '<div class="lf-head"><h4>'+name+'</h4><span>'+div+' Division</span></div>'
                    +'<div class="lf-body">'
                    +'<div class="lf-row"><span class="lf-lbl">Score</span><span class="lf-val">'+(sc!==undefined?sc.toFixed(2)+'%':'Unreported')+'</span></div>'
                    +'<div class="lf-row"><span class="lf-lbl">Grade</span><span class="lf-badge '+gr.cls+'">'+gr.txt+'</span></div>'
                    +(did?'<a class="lf-link" href="'+detailUrl(did)+'">View District Scorecard &rarr;</a>':'')
                    +'<div style="margin-top:8px;font-size:11px;color:#64748b;font-weight:700;text-align:center">Tip: click district to open detail in new tab</div>'
                    +'</div>',{maxWidth:220}
                );

                layer.on({
                    mouseover:function(e){
                        e.target.setStyle({weight:3,color:'#134e4a',fillOpacity:.95});
                        e.target.bringToFront();
                        e.target.openPopup();
                        highlightRow(nm);
                    },
                    mouseout:function(e){
                        distLayer.resetStyle(e.target);
                        clearRowHighlight();
                        e.target.closePopup();
                    },
                    click:function(){
                        highlightRow(nm,true);
                        if(did){ openInNewTab(detailUrl(did)); }
                    }
                });
            }
        });

        /* Build district labels (rendered based on zoom level to avoid crowding) */
        districtLabelMarkers = [];
        data.features.forEach(function(f){
            var name = f.properties.name || '';
            var div  = f.properties.division || '';
            var nm   = name.toUpperCase();
            var sc   = SCORES[nm];
            var gr   = gradeStr(sc);
            var c    = layerCentroid(f);
            if(!c) return;
            var m = L.marker(c,{ interactive:false });
            m._ppmfLbl = { name:name, div:div, cls:gr.cls };
            districtLabelMarkers.push(m);
        });

        updateDistrictLabels();
    }

    function districtLabelIcon(meta, mode){
        var cls = meta && meta.cls ? meta.cls : 'lfb-unreported';
        var name = meta && meta.name ? meta.name : '';
        var div  = meta && meta.div  ? meta.div  : '';

        if(mode === 'name'){
            return L.divIcon({
                className:'ppmf-label district-lbl',
                html: '<div class="lbl-box '+cls+'"><div class="lbl-name">'+name+'</div></div>',
                iconSize:[104,19],
                iconAnchor:[52,10]
            });
        }

        return L.divIcon({
            className:'ppmf-label district-lbl',
            html: '<div class="lbl-box '+cls+'"><div class="lbl-name">'+name+'</div><div class="lbl-sub">'+div+'</div></div>',
            iconSize:[122,26],
            iconAnchor:[61,13]
        });
    }

    function updateDistrictLabels(){
        if(!leafletMap || !labelGroup) return;
        if(currentView !== 'district') return;

        var z = leafletMap.getZoom();
        // Always show district names; add division only when zoomed in enough.
        var mode = (z >= 9.5) ? 'full' : 'name';

        labelGroup.clearLayers();

        districtLabelMarkers.forEach(function(m){
            m.setIcon(districtLabelIcon(m._ppmfLbl, mode));
            m.addTo(labelGroup);
        });
    }

    /* ── DIVISION LAYER ────────────────────────────────── */
    function buildDivisionLayer(data){
        var divLabels = L.layerGroup();

        divLayer = L.geoJSON(data,{
            style:function(f){
                var div = f.properties.name||'';
                var nm = String(div).toUpperCase();
                return {fillColor:scoreColor(DIV_SCORES[nm]),weight:2,color:'#fff',fillOpacity:.78};
            },
            onEachFeature:function(f,layer){
                var div = f.properties.name||'';
                var nm = String(div).toUpperCase();
                var divId = DIV_IDS[nm];
                rebuildDivisionPopup(layer);
                layer.on({
                    mouseover:function(e){e.target.setStyle({weight:3.5,fillOpacity:.95});e.target.bringToFront();e.target.openPopup();},
                    mouseout: function(e){divLayer.resetStyle(e.target);e.target.closePopup();},
                    click:function(){
                        if(divId){ openInNewTab(divisionDetailUrl(divId)); }
                    }
                });

                /* Division name label */
                var c = layerCentroid(f);
                if(c){
                    L.marker(c,{
                        icon:L.divIcon({
                            className:'ppmf-label division-lbl',
                            html:'<div>'+div+'</div>',
                            iconSize:[120,20],
                            iconAnchor:[60,10]
                        }),
                        interactive:false
                    }).addTo(divLabels);
                }
            }
        });

        /* Store division labels group on divLayer for easy toggle */
        divLayer._divLabels = divLabels;
    }

    /* ── CENTROID helper ───────────────────────────────── */
    function layerCentroid(feature){
        try {
            var coords = feature.geometry.coordinates;
            var type   = feature.geometry.type;
            var pts    = [];
            if(type==='Polygon')      { pts = coords[0]; }
            else if(type==='MultiPolygon'){
                var biggest = coords.reduce(function(a,b){ return b[0].length>a[0].length?b:a; });
                pts = biggest[0];
            }
            if(!pts.length) return null;
            var lat=0,lng=0;
            pts.forEach(function(p){ lng+=p[0]; lat+=p[1]; });
            return [lat/pts.length, lng/pts.length];
        } catch(e){ return null; }
    }

    /* ── TABLE ↔ MAP cross-link ────────────────────────── */
    function highlightRow(nm, scroll){
        clearRowHighlight();
        var tr = document.querySelector('#districtTable tbody tr[data-area="'+nm+'"]');
        if(tr){ tr.classList.add('map-highlighted'); if(scroll) tr.scrollIntoView({behavior:'smooth',block:'nearest'}); }
    }
    function clearRowHighlight(){
        document.querySelectorAll('#districtTable tbody tr.map-highlighted')
            .forEach(function(t){ t.classList.remove('map-highlighted'); });
    }

    /* ── PUBLIC: table row onclick ─────────────────────── */
    /* ── PUBLIC: switchView ────────────────────────────── */
    function switchView(view, skipFormSync){
        currentView = view;
        document.getElementById('mapBtnDistrict').className='map-view-btn'+(view==='district'?' active':'');
        document.getElementById('mapBtnDivision').className='map-view-btn'+(view==='division'?' active':'');

        if(!leafletMap) return;

        if(view==='district'){
            if(divLayer && leafletMap.hasLayer(divLayer)){
                leafletMap.removeLayer(divLayer);
                if(divLayer._divLabels) leafletMap.removeLayer(divLayer._divLabels);
            }
            if(distLayer){ leafletMap.addLayer(distLayer); }
            if(labelGroup){ leafletMap.addLayer(labelGroup); }
            if(distLayer && !skipFormSync) leafletMap.fitBounds(distLayer.getBounds(),{padding:[24,24],maxZoom:11.5});
            updateDistrictLabels();
            document.getElementById('mapPanelTitle').textContent = 'Punjab Districts — KPI Performance';
            document.getElementById('mapPanelSub').textContent   = '36 Districts · Click district to view scorecard';
            document.getElementById('mapStatusText').textContent  = '36 Punjab districts · coloured by KPI score';
        } else {
            if(distLayer && leafletMap.hasLayer(distLayer)) leafletMap.removeLayer(distLayer);
            if(labelGroup) leafletMap.removeLayer(labelGroup);
            if(divLayer){ leafletMap.addLayer(divLayer); }
            if(divLayer && divLayer._divLabels) leafletMap.addLayer(divLayer._divLabels);
            if(divLayer && !skipFormSync) leafletMap.fitBounds(divLayer.getBounds(),{padding:[24,24],maxZoom:10.5});
            document.getElementById('mapPanelTitle').textContent = 'Punjab Divisions — Overview';
            document.getElementById('mapPanelSub').textContent   = '9 Divisions · Click division to view report';
            document.getElementById('mapStatusText').textContent  = '9 Punjab divisions · click to view division report';
        }

        /* Sync area_type form select */
        if(!skipFormSync){
            var s = document.getElementById('areaTypeSelect');
            if(s && s.value!==view){ s.value=view; }
        }
    }

    /* ── PUBLIC: fitAll ────────────────────────────────── */
    function fitAll(){
        var l = (currentView==='district'&&distLayer) ? distLayer : (divLayer||null);
        if(l) leafletMap.fitBounds(l.getBounds(),{padding:[20,20]});
    }

    document.addEventListener('DOMContentLoaded', init);
    return { switchView, fitAll, setData };
})();
window.ppmfMap = ppmfMap;
</script>

{{-- ── Existing AJAX scorecard loader (unchanged) ──────── --}}
<script>
    (function () {
        const dataUrl = @json(route('scorecard.data'));

        function serializeForm(form) {
            const fd = new FormData(form);
            const params = new URLSearchParams();
            for (const [k, v] of fd.entries()) {
                if (v === null || v === undefined) continue;
                params.append(k, String(v));
            }
            return params.toString();
        }

        function pushQuery(qs) {
            window.history.pushState({}, '', String(window.location.pathname) + (qs ? ('?' + qs) : ''));
        }

        function updatePeriodVis(form) {
            const period = String(form.querySelector('[name="period"]')?.value || 'weekly');
            const weekField = form.querySelector('[data-period-field="week"]');
            if (weekField) weekField.style.display = (period === 'weekly') ? '' : 'none';
        }

        async function loadData(form, qsOverride) {
            const qs = qsOverride || serializeForm(form);
            const url = dataUrl + (qs ? ('?' + qs) : '');

            const cards = document.getElementById('summaryCardsContainer');
            const tablePanel = document.getElementById('scorecardTablePanel');
            const wkSel = document.getElementById('weekRangeSelect');

            if (cards) cards.style.opacity = '0.6';
            if (tablePanel) tablePanel.style.opacity = '0.6';

            try {
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const json = await res.json();
                if (!json || json.status !== 'success') return;

                if (json.html?.status_cards && cards) {
                    cards.innerHTML = json.html.status_cards;
                }

                if (json.html?.table_panel && tablePanel) {
                    tablePanel.outerHTML = json.html.table_panel;
                }

                if (json.html?.week_options && wkSel) {
                    wkSel.innerHTML = '<option value=\"\">Select Week</option>' + json.html.week_options;
                    if (json.filters?.week_range) wkSel.value = String(json.filters.week_range);
                }

                if (json.filters) {
                    if (json.filters.month) { const e = form.querySelector('[name=\"month\"]'); if (e) e.value = String(json.filters.month); }
                    if (json.filters.year) { const e = form.querySelector('[name=\"year\"]'); if (e) e.value = String(json.filters.year); }
                    if (json.filters.performance !== undefined) {
                        const perf = document.getElementById('performanceInput');
                        if (perf) perf.value = String(json.filters.performance || 'all');
                    }
                    if (json.filters.per_page !== undefined) {
                        const per = document.getElementById('perPageInput');
                        if (per) per.value = String(json.filters.per_page || '10');
                    }
                }

                if (json.map && window.ppmfMap && typeof window.ppmfMap.setData === 'function') {
                    window.ppmfMap.setData(json.map);
                }

                pushQuery(qs);
            } catch (e) {
                // keep silent; UI remains with old data
            } finally {
                const cards2 = document.getElementById('summaryCardsContainer');
                const table2 = document.getElementById('scorecardTablePanel');
                if (cards2) cards2.style.opacity = '1';
                if (table2) table2.style.opacity = '1';
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('scorecardFilters');
            if (!form) return;

            updatePeriodVis(form);

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                loadData(form);
            });

            form.addEventListener('change', function (e) {
                if (!e.target || !e.target.matches('select, input')) return;
                updatePeriodVis(form);
                loadData(form);
            });

            document.addEventListener('click', function (e) {
                const perf = e.target.closest('#summaryCardsContainer a.sc-perf-card');
                if (perf) {
                    e.preventDefault();
                    const u = new URL(perf.getAttribute('href'), window.location.origin);
                    const perfVal = u.searchParams.get('performance') || 'all';
                    const perfInput = document.getElementById('performanceInput');
                    if (perfInput) perfInput.value = perfVal;
                    const params = new URLSearchParams(serializeForm(form));
                    params.set('page', '1');
                    loadData(form, params.toString());
                    return;
                }

                const pag = e.target.closest('#scorecardTablePanel .pagination a');
                if (pag) {
                    const href = pag.getAttribute('href');
                    if (!href) return;
                    e.preventDefault();
                    const u = new URL(href, window.location.origin);
                    const page = u.searchParams.get('page') || '1';
                    const params = new URLSearchParams(serializeForm(form));
                    params.set('page', String(page));
                    loadData(form, params.toString());
                }
            });

            // Per-page dropdown inside table panel (avoid full reload)
            document.addEventListener('change', function (e) {
                const sel = e.target.closest('#scorecardTablePanel select[name=\"per_page\"]');
                if (!sel) return;
                e.preventDefault();
                const perPageInput = document.getElementById('perPageInput');
                if (perPageInput) perPageInput.value = String(sel.value || '10');
                const params = new URLSearchParams(serializeForm(form));
                params.set('page', '1');
                loadData(form, params.toString());
            });
        });
    })();
</script>
@endpush

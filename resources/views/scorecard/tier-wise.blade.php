@extends('layouts.app')

@section('title', 'Chief Minister Governance Scorecard Tier Wise')
@section('page_title', 'Chief Minister Governance Scorecard Tier Wise')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/plugins/leaflet/leaflet.css') }}"/>
<style>
    .sc-page-card{background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:20px;box-shadow:0 16px 38px rgba(15,23,42,.07);padding:22px}.sc-filter-card{background:linear-gradient(135deg,#f8fafc,#ffffff);border:1px solid rgba(148,163,184,.28);border-radius:18px;padding:18px;margin-bottom:22px}.sc-filter-card .form-label{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#475569;margin-bottom:7px}.sc-filter-card .form-select,.sc-filter-card .form-control{height:42px;border-radius:11px;border-color:#cbd5e1;font-size:13px;font-weight:600;color:#334155;box-shadow:none}.sc-filter-card .form-select:focus,.sc-filter-card .form-control:focus{border-color:var(--gov-green);box-shadow:0 0 0 3px rgba(0,107,63,.13)}.tier-tabs{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:18px}.tier-tab{border:1px solid rgba(0,107,63,.24);border-radius:16px;background:#fff;padding:14px 16px;text-decoration:none;color:var(--gov-green);display:flex;align-items:center;justify-content:space-between;gap:12px;box-shadow:0 10px 24px rgba(15,23,42,.05);transition:.18s ease}.tier-tab:hover{transform:translateY(-2px);background:var(--gov-green-light);color:var(--gov-green-dark)}.tier-tab.active{background:linear-gradient(135deg,var(--gov-green-dark),var(--gov-green));color:#fff;border-color:var(--gov-green-dark);box-shadow:0 14px 30px rgba(0,107,63,.16)}.tier-tab strong{font-size:15px;font-weight:900}.tier-tab span{font-size:12px;font-weight:700;opacity:.82}.tier-tab i{font-size:18px;opacity:.95}.sc-layout{display:grid;grid-template-columns:minmax(0,1.12fr) minmax(390px,.88fr);gap:22px;align-items:start}.sc-panel{background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:18px;box-shadow:0 12px 28px rgba(15,23,42,.05);overflow:hidden}.sc-panel-header{padding:16px 18px;border-bottom:1px solid rgba(148,163,184,.22);background:linear-gradient(135deg,#f8fafc,#ffffff);display:flex;align-items:flex-start;justify-content:space-between;gap:14px}.sc-panel-title{font-size:15px;font-weight:900;color:#0f172a;margin:0;text-transform:uppercase;letter-spacing:.04em}.sc-panel-subtitle{font-size:12px;color:#64748b;margin-top:4px}.sc-table{width:100%;border-collapse:separate;border-spacing:0;margin:0}.sc-table thead th{background:linear-gradient(180deg,var(--gov-green-dark) 0%,var(--gov-green) 100%);color:#fff;padding:12px 12px;font-size:11px;text-transform:uppercase;letter-spacing:.06em;font-weight:900;white-space:nowrap;border-bottom:2px solid var(--gold)}.sc-table tbody td{padding:12px;border-bottom:1px solid rgba(148,163,184,.18);font-size:13px;font-weight:700;color:#0f172a;vertical-align:middle}.sc-table tbody tr:hover{background:#f8fafc}.sc-rank{width:62px;text-align:center}.sc-rank-badge{width:34px;height:34px;border-radius:12px;background:var(--gov-green-light);color:var(--gov-green-dark);display:inline-flex;align-items:center;justify-content:center;font-weight:900}.sc-district-name{font-weight:900;color:#0f172a;text-transform:uppercase}.sc-muted{font-size:12px;color:#64748b;font-weight:600}.sc-grade-badge{display:inline-flex;align-items:center;justify-content:center;min-width:52px;height:30px;border-radius:999px;padding:0 10px;color:#fff;font-size:12px;font-weight:900}.grade-critical{background:#dc2626}.grade-average{background:#f59e0b;color:#111827}.grade-good{background:#2563eb}.grade-excellent{background:#16a34a}.sc-progress{height:8px;background:#e2e8f0;border-radius:999px;overflow:hidden;min-width:105px}.sc-progress span{display:block;height:100%;border-radius:999px}.bar-critical{background:#dc2626}.bar-average{background:#f59e0b}.bar-good{background:#2563eb}.bar-excellent{background:#16a34a}.sc-map-panel{position:sticky;top:88px}.sc-google-map-wrap{height:430px;border-radius:18px;overflow:hidden;border:1px solid rgba(15,23,42,.12);background:#e2e8f0;position:relative}.sc-google-map-wrap iframe{width:100%;height:100%;border:0;display:block}.sc-map-label{position:absolute;left:14px;top:14px;background:rgba(6,77,49,.88);color:#fff;border-radius:999px;padding:8px 12px;font-size:12px;font-weight:900;backdrop-filter:blur(6px);border:1px solid rgba(255,255,255,.16)}.sc-map-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}.sc-map-actions a{border:1px solid rgba(0,107,63,.25);background:#fff;color:var(--gov-green);border-radius:999px;padding:8px 12px;font-size:12px;font-weight:900;text-decoration:none}.sc-map-actions a:hover{background:var(--gov-green-light);border-color:rgba(0,107,63,.35);color:var(--gov-green-dark)}.sc-district-map-list{max-height:315px;overflow:auto;padding:14px}.sc-district-map-item{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 0;border-bottom:1px solid rgba(148,163,184,.18)}.sc-district-map-item:last-child{border-bottom:0}.sc-map-pin{width:12px;height:12px;border-radius:50%;box-shadow:0 0 0 4px rgba(15,23,42,.06);flex:0 0 auto}.pin-critical{background:#dc2626}.pin-average{background:#f59e0b}.pin-good{background:#2563eb}.pin-excellent{background:#16a34a}.sc-pagination-wrap{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:14px 18px;background:#f8fafc;border-top:1px solid rgba(148,163,184,.22)}.sc-pagination-wrap .pagination{margin:0;gap:5px;flex-wrap:wrap}.sc-pagination-wrap .page-link{border-radius:10px!important;min-width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;color:var(--gov-green);font-weight:900;border-color:#cbd5e1;box-shadow:none}.sc-pagination-wrap .page-item.active .page-link{background:var(--gov-green);border-color:var(--gov-green);color:#fff}.sc-empty{padding:28px;text-align:center;color:#64748b;font-weight:700;background:#f8fafc}.sc-legend{display:flex;flex-wrap:wrap;gap:10px;margin-top:18px;color:#334155;font-size:12px;font-weight:800}.sc-legend span{display:inline-flex;align-items:center;gap:7px}.sc-legend i{width:11px;height:11px;border-radius:50%;display:inline-block}
    .sc-perf-card{display:flex;align-items:center;gap:12px;border:1px solid rgba(148,163,184,.25);border-radius:16px;background:#fff;padding:12px 14px;box-shadow:0 10px 24px rgba(15,23,42,.04);transition:.15s ease;min-height:72px}
    .sc-perf-card:hover{transform:translateY(-1px);border-color:rgba(15,118,110,.35);background:#f8fafc}
    .sc-perf-card.active{border-color:#0f766e;box-shadow:0 12px 26px rgba(15,118,110,.14)}
    .sc-perf-ico{width:36px;height:36px;border-radius:14px;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:16px;flex:0 0 auto}
    .sc-perf-ico.excellent{background:#16a34a}
    .sc-perf-ico.good{background:#2563eb}
    .sc-perf-ico.average{background:#f59e0b}
    .sc-perf-ico.critical{background:#dc2626}
    .sc-perf-ico.unreported{background:#64748b}
    .sc-perf-title{font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.06em;color:#334155;line-height:1.2}
    .sc-perf-count{font-size:20px;font-weight:900;color:#0f172a;line-height:1.1}
    .sc-perf-sub{font-size:12px;color:#64748b;font-weight:700;line-height:1.2}
    .formula-help-card{background:#f8fffb;border:1px solid rgba(0,104,56,.16);border-radius:14px;padding:14px 16px;margin-bottom:16px}
    .formula-help-card .title{color:#14532d;font-size:13px;font-weight:900;margin-bottom:4px}
    .formula-help-card .formula{display:flex;flex-wrap:wrap;gap:7px 18px;margin-top:7px;color:#334155;font-size:11.5px;font-weight:800}
    @media(max-width:1199px){.sc-layout{grid-template-columns:1fr}.sc-map-panel{position:relative;top:auto}.sc-google-map-wrap{height:390px}}
    @media(max-width:767px){.sc-page-card{padding:14px}.tier-tabs{grid-template-columns:1fr}.sc-panel-header{display:block}.sc-google-map-wrap{height:320px}.sc-table{min-width:760px}.sc-pagination-wrap{align-items:flex-start}.sc-pagination-wrap .page-link{min-width:34px;height:34px;font-size:12px}}

    /* Leaflet map (Punjab GeoJSON only) */
    #ppmfTierLeafletMap{height:430px;background:#edf2ee;position:relative;z-index:1;border-radius:0}
    .leaflet-tile-pane{display:none!important}
    .leaflet-control-attribution{display:none!important}
    .leaflet-control-zoom a{background:#fff;border-color:#e2e8f0;color:#0f766e;font-weight:900}
    .leaflet-control-zoom a:hover{background:#ecfdf5}

    /* District labels (zoom-adaptive) */
    .ppmf-label{background:transparent!important;border:none!important;box-shadow:none!important;
        font-family:inherit;font-weight:800;color:#1e293b;text-align:center;white-space:nowrap;
        pointer-events:none;line-height:1.1;
        text-shadow:0 0 3px #fff,0 0 3px #fff,0 0 3px #fff,0 0 5px #fff}
    .ppmf-label.district-lbl{font-size:9px;letter-spacing:.03em}
    .ppmf-label .lbl-box{display:inline-flex;flex-direction:column;align-items:center;justify-content:center;gap:1px;padding:2px 5px;border-radius:8px;border:1px solid rgba(148,163,184,.35);background:rgba(255,255,255,.72);backdrop-filter:blur(3px)}
    .ppmf-label .lbl-name{font-weight:950}
    .ppmf-label .lbl-sub{font-size:8px;font-weight:800;opacity:.85;text-transform:none;letter-spacing:0}
    .ppmf-label .lbl-box.lfb-excellent{background:rgba(220,252,231,.78);border-color:rgba(22,163,74,.30);color:#14532d}
    .ppmf-label .lbl-box.lfb-good{background:rgba(219,234,254,.80);border-color:rgba(37,99,235,.30);color:#1e40af}
    .ppmf-label .lbl-box.lfb-average{background:rgba(254,249,195,.82);border-color:rgba(245,158,11,.33);color:#854d0e}
    .ppmf-label .lbl-box.lfb-critical{background:rgba(254,226,226,.82);border-color:rgba(220,38,38,.33);color:#7f1d1d}
    .ppmf-label .lbl-box.lfb-unreported{background:rgba(241,245,249,.86);border-color:rgba(100,116,139,.28);color:#475569}
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
    .map-status-bar{display:flex;align-items:center;justify-content:space-between;padding:8px 14px;background:#f8fafc;border-bottom:1px solid rgba(148,163,184,.15);font-size:11.5px;color:#64748b;font-weight:600}
    .map-view-toggle{display:flex;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden}
    .map-view-btn{height:32px;padding:0 14px;border:none;background:none;font-size:12px;font-weight:900;color:#64748b;cursor:pointer;font-family:inherit;transition:all .15s}
    .map-view-btn.active{background:var(--gov-green);color:#fff}
    .map-view-btn:hover:not(.active){background:var(--gov-green-light);color:var(--gov-green)}

    .map-fit-btn{background:none;border:none;cursor:pointer;color:var(--gov-green);font-size:11.5px;font-weight:900;padding:0}
    .map-fit-btn:hover{color:var(--gov-green-dark)}
    .map-loading-overlay{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;z-index:20;background:#edf2ee}
    .map-spinner{width:26px;height:26px;border:3px solid #e2e8f0;border-top-color:#0f766e;border-radius:50%;animation:mapspin .7s linear infinite}
    @keyframes mapspin{to{transform:rotate(360deg)}}
    .map-legend-bar{display:flex;flex-wrap:wrap;gap:10px;padding:10px 14px;border-top:1px solid rgba(148,163,184,.18);background:#fafcfb}
    .map-leg-item{display:inline-flex;align-items:center;gap:5px;font-size:11.5px;font-weight:700;color:#334155}
    .map-leg-dot{width:12px;height:12px;border-radius:3px;display:inline-block;flex-shrink:0}
    @media(max-width:1199px){#ppmfTierLeafletMap{height:390px}}
    @media(max-width:767px){#ppmfTierLeafletMap{height:320px}}
</style>
@endpush

@section('content')
@php
    $filters = $filters ?? [];
    $selectedPeriod = $filters['period'] ?? 'weekly';
    $selectedPerformance = $filters['performance'] ?? 'all';
    $selectedWeekRange = $filters['week_range'] ?? '';
    $selectedMonth = $filters['month'] ?? now()->format('m');
    $selectedYear = $filters['year'] ?? now()->format('Y');
    $selectedAreaType = 'district';
    $selectedKpiCategoryId = $filters['kpi_category_id'] ?? '';
    $selectedPerPage = (int) ($filters['per_page'] ?? 10);
    $perPageOptions = [10, 25, 50, 100];
    $selectedTier = (string)($filters['tier'] ?? '1');
    $mainRoute = Route::has('scorecard.index') ? route('scorecard.index') : '#';
    $tierRoute = Route::has('scorecard.tier') ? route('scorecard.tier') : url()->current();
    $tierRankingItems = method_exists($tierRanking ?? null, 'getCollection') ? $tierRanking->getCollection()->values() : collect($tierRanking ?? [])->values();
    $pageOffset = method_exists($tierRanking ?? null, 'currentPage') ? (($tierRanking->currentPage() - 1) * $tierRanking->perPage()) : 0;
    $scoreMeta = function ($score) { $score=(float)$score; if($score>=90)return ['grade'=>'A+','label'=>'Excellent','class'=>'excellent']; if($score>=70)return ['grade'=>$score>=80?'A':'B','label'=>'Good','class'=>'good']; if($score>=50)return ['grade'=>$score>=60?'C':'D','label'=>'Average','class'=>'average']; return ['grade'=>'E','label'=>'Critical','class'=>'critical']; };
    $monthOptions = ['01'=>'January','02'=>'February','03'=>'March','04'=>'April','05'=>'May','06'=>'June','07'=>'July','08'=>'August','09'=>'September','10'=>'October','11'=>'November','12'=>'December'];
    $yearOptions = range((int) now()->format('Y'), (int) now()->format('Y') - 5);
    $queryBase = request()->except(['tier','page']);

    // Leaflet map data (keys MUST match GeoJSON district names)
    $districtScores = $districtScores ?? [];
    $districtMapIds = $districtMapIds ?? [];
    $districtMapRanks = $districtMapRanks ?? [];

    $tierMapScores = collect($districtScores)->mapWithKeys(function ($score, $name) {
        $nm = strtoupper((string) $name);
        return [$nm => (float) $score];
    })->all();
    $tierMapDistrictIds = collect($districtMapIds)->mapWithKeys(function ($id, $name) {
        $nm = strtoupper((string) $name);
        return [$nm => $id];
    })->all();
    $tierMapDistrictRanks = collect($districtMapRanks)->mapWithKeys(function ($rank, $name) {
        $nm = strtoupper((string) $name);
        return [$nm => (int) $rank];
    })->all();

    $perfHref = function (string $key) use ($tierRoute, $selectedPerformance) {
        $query = request()->query();
        if ($selectedPerformance === $key) {
            unset($query['performance']);
        } else {
            $query['performance'] = $key;
        }
        $query['page'] = 1;
        return $tierRoute . (count($query) ? ('?' . http_build_query($query)) : '');
    };

    $perfCards = [
        ['key'=>'excellent','title'=>'Excellent','range'=>'90-100','icon'=>'bi-trophy-fill','class'=>'excellent','count'=>(int)($tierSummary['excellent_count'] ?? 0)],
        ['key'=>'good','title'=>'Good','range'=>'70-89','icon'=>'bi-check-circle-fill','class'=>'good','count'=>(int)($tierSummary['good_count'] ?? 0)],
        ['key'=>'average','title'=>'Average','range'=>'50-69','icon'=>'bi-exclamation-circle-fill','class'=>'average','count'=>(int)($tierSummary['average_count'] ?? 0)],
        ['key'=>'critical','title'=>'Critical','range'=>'< 50','icon'=>'bi-x-octagon-fill','class'=>'critical','count'=>(int)($tierSummary['critical_count'] ?? 0)],
    ];
@endphp

<div class="page-title-bar mb-4">
    <div>
        <h2 class="page-title mb-1">Chief Minister Governance Scorecard Tier Wise</h2>
        <p class="page-subtitle mb-0">Tier-wise district ranking with Leaflet + GeoJSON map and performance colour coding.</p>
    </div>
    <div class="d-flex flex-wrap gap-2"><a href="{{ $mainRoute }}" class="btn btn-gov btn-gov-outline"><i class="bi bi-table"></i> District Wise</a><button type="button" class="btn btn-gov btn-gov-outline" onclick="window.print()"><i class="bi bi-printer"></i> Print</button></div>
</div>

<div class="sc-page-card">
    <div class="tier-tabs">
        @foreach(['1','2','3'] as $tier)
            @php $tierUrl = $tierRoute . '?' . http_build_query(array_merge($queryBase, ['tier'=>$tier])); @endphp
            <a href="{{ $tierUrl }}" class="tier-tab {{ $selectedTier === $tier ? 'active' : '' }}"><span><strong>Tier {{ $tier }} Districts</strong><br><span>View ranked districts</span></span><i class="bi {{ $selectedTier === $tier ? 'bi-check-circle-fill' : 'bi-arrow-right-circle' }}"></i></a>
        @endforeach
    </div>

    <div class="sc-filter-card">
        <form method="GET" action="{{ $tierRoute }}" id="tierScorecardFilters">
            <input type="hidden" name="tier" value="{{ $selectedTier }}">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-6 col-lg-3"><label class="form-label">Period</label><select name="period" class="form-select"><option value="weekly" @selected($selectedPeriod==='weekly')>Weekly</option><option value="monthly" @selected($selectedPeriod==='monthly')>Monthly</option><option value="quarterly" @selected($selectedPeriod==='quarterly')>Quarterly</option><option value="yearly" @selected($selectedPeriod==='yearly')>Yearly</option><option value="all" @selected($selectedPeriod==='all')>All Time</option></select></div>
                <div class="col-12 col-md-6 col-lg-3" data-period-field="week"><label class="form-label">Week</label><select name="week_range" class="form-select" id="tierWeekRangeSelect"><option value="">Select Week</option>@foreach(($weekOptions ?? []) as $value=>$label)<option value="{{ $value }}" @selected((string)$selectedWeekRange === (string)$value)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-12 col-md-6 col-lg-3"><label class="form-label">Month</label><select name="month" class="form-select">@foreach($monthOptions as $value=>$label)<option value="{{ $value }}" @selected((string)$selectedMonth===(string)$value)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-12 col-md-6 col-lg-3"><label class="form-label">Year</label><select name="year" class="form-select">@foreach($yearOptions as $year)<option value="{{ $year }}" @selected((string)$selectedYear===(string)$year)>{{ $year }}</option>@endforeach</select></div>

                <div class="col-12 col-md-6 col-lg-3"><label class="form-label">Calculation Type</label><select name="calculation_type" class="form-select"><option value="general" selected>General</option></select></div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label class="form-label">KPI Category</label>
                    <select name="kpi_category_id" class="form-select">
                        <option value="">General</option>
                        @foreach(($kpiCategories ?? []) as $category)
                            <option value="{{ $category->id }}" @selected((string)$selectedKpiCategoryId===(string)$category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Auto-applies on change; keep a hidden submit for accessibility. --}}
                <button type="submit" class="d-none">Apply</button>

                <div class="col-12 col-md-6 col-lg-3 d-flex align-items-end justify-content-end">
                    <div class="w-100 d-flex justify-content-end">
                        <a href="{{ $tierRoute }}?tier=1" class="btn btn-gov btn-gov-outline w-100" id="tierResetBtn">
                            <i class="bi bi-x-circle"></i> Reset
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="sc-layout" id="tierResultsLayout">
        <div id="tierScorecardDynamic">
            @include('scorecard.partials.tier-results', [
                'tierSummary' => $tierSummary ?? [],
                'tierRanking' => $tierRanking ?? null,
                'filters' => $filters ?? [],
            ])
        </div>

        <div class="sc-map-panel" id="tierMapContainer">
            <div class="sc-panel">
                <div class="sc-panel-header">
                    <div>
                        <h5 class="sc-panel-title">Punjab Districts — Tier {{ $selectedTier }}</h5>
                        <div class="sc-panel-subtitle">Leaflet + GeoJSON · coloured by KPI score</div>
                    </div>
                </div>

                <div class="map-status-bar">
                    <span id="tierMapStatusText">Loading Punjab boundary data…</span>
                    <button onclick="ppmfTierMap.fitAll()" class="map-fit-btn">
                        <i class="bi bi-fullscreen"></i> Fit Punjab
                    </button>
                </div>

                <div style="position:relative">
                    <div id="ppmfTierLeafletMap">
                        <div class="map-loading-overlay" id="tierMapLoader">
                            <div class="map-spinner"></div>
                            <span style="font-size:12px;color:#475569;font-weight:700">Loading Punjab map…</span>
                        </div>
                    </div>
                </div>

                <div class="map-legend-bar">
                    <span class="map-leg-item"><span class="map-leg-dot" style="background:#16a34a"></span>Excellent ≥90%</span>
                    <span class="map-leg-item"><span class="map-leg-dot" style="background:#2563eb"></span>Good 70–89%</span>
                    <span class="map-leg-item"><span class="map-leg-dot" style="background:#f59e0b"></span>Average 50–69%</span>
                    <span class="map-leg-item"><span class="map-leg-dot" style="background:#dc2626"></span>Critical &lt;50%</span>
                    <span class="map-leg-item"><span class="map-leg-dot" style="background:#94a3b8"></span>Unreported</span>
                </div>
            </div>
        </div>

</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/plugins/leaflet/leaflet.js') }}"></script>
<script>
    const ppmfTierMap = (function () {
        let SCORES = @json($tierMapScores);
        let DIST_IDS = @json($tierMapDistrictIds);
        let RANKS = @json($tierMapDistrictRanks);

        let leafletMap, distLayer, labelGroup;
        let districtLabelMarkers = [];
        const layersByName = {}; // UPPERCASE district name -> layer

        function normalizeUpperKeyMap(obj) {
            const out = {};
            if (!obj) return out;
            Object.keys(obj).forEach(function (k) {
                out[String(k).toUpperCase()] = obj[k];
            });
            return out;
        }

        function scoreColor(s) {
            if (s === undefined || s === null) return '#94a3b8';
            if (s >= 90) return '#16a34a';
            if (s >= 70) return '#2563eb';
            if (s >= 50) return '#f59e0b';
            return '#dc2626';
        }

        function gradeStr(s) {
            if (s === undefined || s === null) return { txt: 'Unreported', cls: 'lfb-unreported' };
            if (s >= 90) return { txt: 'A+ Excellent', cls: 'lfb-excellent' };
            if (s >= 80) return { txt: 'A Good', cls: 'lfb-good' };
            if (s >= 70) return { txt: 'B Good', cls: 'lfb-good' };
            if (s >= 60) return { txt: 'C Average', cls: 'lfb-average' };
            if (s >= 50) return { txt: 'D Average', cls: 'lfb-average' };
            return { txt: 'E Critical', cls: 'lfb-critical' };
        }

        function detailUrl(did) {
            if (!did) return '#';
            @if(Route::has('scorecard.district-detail'))
            var base = '{{ route("scorecard.district-detail", ["district"=>"__ID__"]) }}'.replace('__ID__', did);
            @else
            var base = '#';
            @endif
            var q = @json(request()->query());
            var qs = Object.keys(q).length ? ('?' + new URLSearchParams(q).toString()) : '';
            return base + qs;
        }

        function openInNewTab(url) {
            if (!url || url === '#') return;
            window.open(url, '_blank', 'noopener');
        }

        function rebuildPopup(layer) {
            if (!layer || !layer.feature || !layer.feature.properties) return;
            var name = layer.feature.properties.name || '';
            var div = layer.feature.properties.division || '';
            var nm = String(name).toUpperCase();
            var sc = SCORES[nm];
            var gr = gradeStr(sc);
            var did = DIST_IDS[nm];
            var rk = RANKS[nm];

            layer.bindPopup(
                '<div class="lf-head"><h4>' + name + '</h4><span>' + div + ' Division</span></div>' +
                '<div class="lf-body">' +
                '<div class="lf-row"><span class="lf-lbl">Rank</span><span class="lf-val">' + (rk ? ('#' + rk) : '—') + '</span></div>' +
                '<div class="lf-row"><span class="lf-lbl">Score</span><span class="lf-val">' + (sc !== undefined && sc !== null ? Number(sc).toFixed(2) + '%' : 'Unreported') + '</span></div>' +
                '<div class="lf-row"><span class="lf-lbl">Grade</span><span class="lf-badge ' + gr.cls + '">' + gr.txt + '</span></div>' +
                (did ? '<a class="lf-link" href="' + detailUrl(did) + '">View District Scorecard →</a>' : '') +
                '<div style="margin-top:8px;font-size:11px;color:#64748b;font-weight:700;text-align:center">Tip: click district to open detail in new tab</div>' +
                '</div>',
                { maxWidth: 220 }
            );
        }

        function districtLabelIcon(meta, mode) {
            var cls = meta && meta.cls ? meta.cls : 'lfb-unreported';
            var name = meta && meta.name ? meta.name : '';
            var div = meta && meta.div ? meta.div : '';

            if (mode === 'name') {
                return L.divIcon({
                    className: 'ppmf-label district-lbl',
                    html: '<div class="lbl-box ' + cls + '"><div class="lbl-name">' + name + '</div></div>',
                    iconSize: [104, 19],
                    iconAnchor: [52, 10]
                });
            }

            return L.divIcon({
                className: 'ppmf-label district-lbl',
                html: '<div class="lbl-box ' + cls + '"><div class="lbl-name">' + name + '</div><div class="lbl-sub">' + div + '</div></div>',
                iconSize: [122, 26],
                iconAnchor: [61, 13]
            });
        }

        function updateDistrictLabels() {
            if (!leafletMap || !labelGroup) return;
            if (!districtLabelMarkers || !districtLabelMarkers.length) return;

            var z = leafletMap.getZoom();
            var mode = (z >= 9.5) ? 'full' : 'name';

            labelGroup.clearLayers();
            districtLabelMarkers.forEach(function (m) {
                if (!m || !m._ppmfLbl) return;
                m.setIcon(districtLabelIcon(m._ppmfLbl, mode));
                m.addTo(labelGroup);
            });
        }

        function init() {
            if (!document.getElementById('ppmfTierLeafletMap')) return;

            leafletMap = L.map('ppmfTierLeafletMap', {
                zoomControl: true,
                scrollWheelZoom: true,
                zoomSnap: 0.25,
                minZoom: 6.5,
                maxZoom: 13,
                center: [30.5, 71.5],
                zoom: 8
            });

            labelGroup = L.layerGroup().addTo(leafletMap);
            leafletMap.on('zoomend', updateDistrictLabels);

            fetch('{{ asset("assets/data/punjab_districts.geojson") }}')
                .then(r => r.json())
                .then(function (geo) {
                    distLayer = L.geoJSON(geo, {
                        style: function (f) {
                            var nm = (f.properties.name || '').toUpperCase();
                            return { fillColor: scoreColor(SCORES[nm]), weight: 1.5, color: '#fff', fillOpacity: .85 };
                        },
                        onEachFeature: function (f, layer) {
                            var name = f.properties.name || '';
                            var nm = String(name).toUpperCase();
                            layersByName[nm] = layer;
                            rebuildPopup(layer);

                            layer.on({
                                mouseover: function (e) { e.target.setStyle({ weight: 3, color: '#134e4a', fillOpacity: .95 }); e.target.bringToFront(); e.target.openPopup(); },
                                mouseout: function (e) { distLayer.resetStyle(e.target); e.target.closePopup(); },
                                click: function () {
                                    var did = DIST_IDS[nm];
                                    if (did) openInNewTab(detailUrl(did));
                                }
                            });
                        }
                    });

                    distLayer.addTo(leafletMap);
                    document.getElementById('tierMapLoader').style.display = 'none';
                    document.getElementById('tierMapStatusText').textContent = 'Punjab districts · coloured by KPI score';
                    fitAll();

                    districtLabelMarkers = [];
                    distLayer.eachLayer(function (layer) {
                        var name = layer.feature?.properties?.name || '';
                        var div = layer.feature?.properties?.division || '';
                        var nm = String(name).toUpperCase();
                        var sc = SCORES[nm];
                        var gr = gradeStr(sc);
                        var c = layer.getBounds ? layer.getBounds().getCenter() : null;
                        if (!c) return;
                        var m = L.marker(c, { interactive: false });
                        m._ppmfLbl = { name: name, div: div, cls: gr.cls };
                        districtLabelMarkers.push(m);
                    });
                    updateDistrictLabels();
                })
                .catch(function () {
                    const loader = document.getElementById('tierMapLoader');
                    if (loader) loader.innerHTML = '<div style="text-align:center;padding:20px"><i class="bi bi-exclamation-triangle fs-2 text-warning"></i><p style="font-size:12px;color:#64748b;margin-top:8px;font-weight:600">GeoJSON file not found: <code>public/assets/data/punjab_districts.geojson</code></p></div>';
                });
        }

        function fitAll() {
            if (!leafletMap) return;
            if (distLayer) leafletMap.fitBounds(distLayer.getBounds(), { padding: [20, 20], maxZoom: 11.5 });
        }

        function setData(payload) {
            if (!payload) return;
            SCORES = normalizeUpperKeyMap(payload.scores || payload.SCORES || {});
            DIST_IDS = normalizeUpperKeyMap(payload.ids || payload.DIST_IDS || {});
            RANKS = normalizeUpperKeyMap(payload.ranks || payload.RANKS || {});

            if (distLayer) {
                distLayer.eachLayer(function (layer) {
                    var name = layer.feature?.properties?.name || '';
                    var nm = String(name).toUpperCase();
                    layer.setStyle({ fillColor: scoreColor(SCORES[nm]), weight: 1.5, color: '#fff', fillOpacity: .85 });
                    rebuildPopup(layer);
                });
            }

            if (districtLabelMarkers && districtLabelMarkers.length) {
                districtLabelMarkers.forEach(function (m) {
                    if (!m || !m._ppmfLbl) return;
                    var nm = String(m._ppmfLbl.name || '').toUpperCase();
                    var gr = gradeStr(SCORES[nm]);
                    m._ppmfLbl.cls = gr.cls;
                });
                updateDistrictLabels();
            }
        }

        document.addEventListener('DOMContentLoaded', init);
        return { setData, fitAll };
    })();
    window.ppmfTierMap = ppmfTierMap;
</script>
<script>
    (function () {
        const dataUrl = @json(route('scorecard.tier-wise.data'));

        function serializeForm(form) {
            const fd = new FormData(form);
            const params = new URLSearchParams();
            for (const [key, value] of fd.entries()) {
                if (value === null || value === undefined) continue;
                params.append(key, String(value));
            }
            return params.toString();
        }

        function pushQuery(queryString) {
            const base = String(window.location.pathname);
            const qs = queryString ? ('?' + queryString) : '';
            window.history.pushState({}, '', base + qs);
        }

        function updatePeriodFieldVisibility(form) {
            const period = String(form.querySelector('[name="period"]')?.value || 'weekly');
            const weekWrap = form.querySelector('[data-period-field="week"]');
            if (weekWrap) weekWrap.style.display = (period === 'weekly') ? '' : 'none';
        }

        async function loadTierData(form, queryStringOverride) {
            const dynamic = document.getElementById('tierScorecardDynamic');
            const weekSelect = document.getElementById('tierWeekRangeSelect');
            if (!dynamic) return;

            const queryString = queryStringOverride || serializeForm(form);
            const url = dataUrl + (queryString ? ('?' + queryString) : '');

            dynamic.style.opacity = '0.6';
            dynamic.style.pointerEvents = 'none';

            try {
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const json = await res.json();

                if (!json || json.status !== 'success') {
                    dynamic.innerHTML = '<div class="sc-empty">Unable to load tier-wise scorecard data.</div>';
                    return;
                }

                if (json.html && json.html.results) {
                    dynamic.innerHTML = json.html.results;
                }

                if (json.html && json.html.week_options && weekSelect) {
                    weekSelect.innerHTML = '<option value="">Select Week</option>' + json.html.week_options;
                }

                if (json.filters) {
                    if (json.filters.month) {
                        const el = form.querySelector('[name="month"]');
                        if (el) el.value = String(json.filters.month);
                    }
                    if (json.filters.year) {
                        const el = form.querySelector('[name="year"]');
                        if (el) el.value = String(json.filters.year);
                    }
                    if (json.filters.week_range && weekSelect) {
                        weekSelect.value = String(json.filters.week_range);
                    }
                }

                if (json.map && window.ppmfTierMap && typeof window.ppmfTierMap.setData === 'function') {
                    window.ppmfTierMap.setData(json.map);
                }

                pushQuery(queryString);
            } catch (e) {
                dynamic.innerHTML = '<div class="sc-empty">Error loading tier-wise data. Please try again.</div>';
            } finally {
                dynamic.style.opacity = '1';
                dynamic.style.pointerEvents = 'auto';
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('tierScorecardFilters');
            if (!form) return;

            updatePeriodFieldVisibility(form);

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                loadTierData(form);
            });

            form.addEventListener('change', function (e) {
                const target = e.target;
                if (!target) return;
                if (target.matches('select, input')) {
                    updatePeriodFieldVisibility(form);
                    loadTierData(form);
                }
            });

            document.addEventListener('click', function (e) {
                const a = e.target.closest('#tierScorecardDynamic .pagination a');
                if (a) {
                    e.preventDefault();
                    const href = a.getAttribute('href');
                    if (!href) return;
                    const url = new URL(href, window.location.origin);
                    loadTierData(form, url.search.replace(/^\\?/, ''));
                    return;
                }

                const perf = e.target.closest('#tierScorecardDynamic a.sc-perf-card');
                if (perf) {
                    e.preventDefault();
                    const href = perf.getAttribute('href');
                    if (!href) return;
                    const url = new URL(href, window.location.origin);
                    loadTierData(form, url.search.replace(/^\\?/, ''));
                    return;
                }

                const resetBtn = e.target.closest('#tierResetBtn');
                if (resetBtn) {
                    e.preventDefault();
                    form.reset();
                    updatePeriodFieldVisibility(form);
                    loadTierData(form);
                }
            });
        });
    })();
</script>
@endpush

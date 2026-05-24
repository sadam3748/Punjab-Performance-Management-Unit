@extends('layouts.app')

@section('title', 'Chief Minister Governance Scorecard District Wise | PPMF Portal')
@section('page_title', 'Chief Minister Governance Scorecard District Wise')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/plugins/leaflet/leaflet.css') }}"/>
<style>
    .sc-page-card{background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:20px;box-shadow:0 16px 38px rgba(15,23,42,.07);padding:22px}
    .sc-filter-card{background:linear-gradient(135deg,#f8fafc,#ffffff);border:1px solid rgba(148,163,184,.28);border-radius:18px;padding:18px;margin-bottom:22px}
    .sc-filter-card .form-label{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#475569;margin-bottom:7px}
    .sc-filter-card .form-select,.sc-filter-card .form-control{height:42px;border-radius:11px;border-color:#cbd5e1;font-size:13px;font-weight:600;color:#334155;box-shadow:none}
    .sc-filter-card .form-select:focus,.sc-filter-card .form-control:focus{border-color:#1b6b57;box-shadow:0 0 0 3px rgba(27,107,87,.13)}

    .sc-perf-card{display:flex;align-items:center;gap:13px;border:1px solid rgba(148,163,184,.24);border-radius:18px;background:#fff;padding:14px 15px;box-shadow:0 10px 24px rgba(15,23,42,.045);transition:.15s ease;min-height:84px;text-decoration:none}
    .sc-perf-card:hover{transform:translateY(-1px);border-color:rgba(15,118,110,.35);background:#f8fafc}
    .sc-perf-card.active{border-color:#0f766e;box-shadow:0 12px 26px rgba(15,118,110,.14)}
    .sc-perf-ico{width:42px;height:42px;border-radius:16px;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:18px;flex:0 0 auto}
    .sc-perf-ico.excellent{background:#16a34a}.sc-perf-ico.good{background:#2563eb}.sc-perf-ico.average{background:#f59e0b}.sc-perf-ico.critical{background:#dc2626}.sc-perf-ico.unreported{background:#64748b}
    .sc-perf-title{font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.06em;color:#334155;line-height:1.2}
    .sc-perf-count{font-size:23px;font-weight:900;color:#0f172a;line-height:1.1;margin-top:2px}
    .sc-perf-sub{font-size:12px;color:#64748b;font-weight:700;line-height:1.2;margin-top:1px}

    .sc-panel{background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:18px;box-shadow:0 12px 28px rgba(15,23,42,.05);overflow:hidden}
    .sc-panel-header{padding:16px 18px;border-bottom:1px solid rgba(148,163,184,.22);background:linear-gradient(135deg,#f8fafc,#ffffff);display:flex;align-items:flex-start;justify-content:space-between;gap:14px;flex-wrap:wrap}
    .sc-panel-title{font-size:15px;font-weight:900;color:#0f172a;margin:0;text-transform:uppercase;letter-spacing:.04em}
    .sc-panel-subtitle{font-size:12px;color:#64748b;margin-top:4px;font-weight:600}

    .sc-table{width:100%;border-collapse:separate;border-spacing:0;margin:0}
    .sc-table thead th{background:#174d43;color:#fff;padding:12px;font-size:11px;text-transform:uppercase;letter-spacing:.06em;font-weight:900;white-space:nowrap}
    .sc-table tbody td{padding:12px;border-bottom:1px solid rgba(148,163,184,.18);font-size:13px;font-weight:700;color:#0f172a;vertical-align:middle}
    .sc-table tbody tr:hover{background:#f0fdf9}
    .sc-rank{width:62px;text-align:center}.sc-rank-badge{width:34px;height:34px;border-radius:12px;background:#ecfdf5;color:#166534;display:inline-flex;align-items:center;justify-content:center;font-weight:900}
    .sc-district-name{font-weight:900;color:#0f172a;text-transform:uppercase;text-decoration:none}.sc-district-name:hover{color:#0f766e}
    .sc-muted{font-size:12px;color:#64748b;font-weight:600}.sc-grade-badge{display:inline-flex;align-items:center;justify-content:center;min-width:52px;height:30px;border-radius:999px;padding:0 10px;color:#fff;font-size:12px;font-weight:900}
    .grade-critical{background:#dc2626}.grade-average{background:#f59e0b;color:#111827}.grade-good{background:#2563eb}.grade-excellent{background:#16a34a}
    .sc-progress{height:8px;background:#e2e8f0;border-radius:999px;overflow:hidden;min-width:105px}.sc-progress span{display:block;height:100%;border-radius:999px}.bar-critical{background:#dc2626}.bar-average{background:#f59e0b}.bar-good{background:#2563eb}.bar-excellent{background:#16a34a}
    .sc-pagination-wrap{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:14px 18px;background:#f8fafc;border-top:1px solid rgba(148,163,184,.22)}
    .sc-pagination-wrap .pagination{margin:0;gap:5px;flex-wrap:wrap}.sc-pagination-wrap .page-link{border-radius:10px!important;min-width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;color:#0f766e;font-weight:800;border-color:#cbd5e1;box-shadow:none}.sc-pagination-wrap .page-item.active .page-link{background:#0f766e;border-color:#0f766e;color:#fff}
    .sc-empty{padding:28px;text-align:center;color:#64748b;font-weight:700;background:#f8fafc}

    /* Leaflet GeoJSON map */
    #ppmfPunjabMap{height:650px;width:100%;background:#eef5f2;position:relative;z-index:1;border-radius:0}
    .leaflet-control-attribution{display:none!important}.leaflet-control-zoom a{background:#fff;border-color:#e2e8f0;color:#0f766e;font-weight:900}.leaflet-control-zoom a:hover{background:#ecfdf5}
    .ppmf-map-loader{position:absolute;inset:0;z-index:500;display:flex;align-items:center;justify-content:center;background:#eef5f2;color:#334155;font-size:13px;font-weight:800;text-align:center;padding:20px}
    .ppmf-map-note{padding:9px 14px;background:#f8fafc;border-top:1px solid rgba(148,163,184,.18);font-size:12px;color:#64748b;font-weight:700;display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}
    .ppmf-map-legend{display:flex;flex-wrap:wrap;gap:12px}.ppmf-map-legend span{display:inline-flex;align-items:center;gap:6px}.ppmf-map-dot{width:12px;height:12px;border-radius:4px;display:inline-block}.dot-excellent{background:#16a34a}.dot-good{background:#2563eb}.dot-average{background:#f59e0b}.dot-critical{background:#dc2626}.dot-nodata{background:#94a3b8}
    .ppmf-label{background:transparent!important;border:none!important;box-shadow:none!important;font-family:inherit;font-weight:900;text-align:center;white-space:nowrap;pointer-events:none;line-height:1.05;text-shadow:0 0 3px #fff,0 0 4px #fff,0 0 6px #fff;color:#0f172a}
    .ppmf-label.district-label{font-size:9.5px;letter-spacing:.02em;color:#1e293b}.ppmf-label.division-label{font-size:13px;letter-spacing:.05em;color:#064e3b;text-transform:uppercase}
    .leaflet-popup-content-wrapper{border-radius:14px!important;padding:0!important;overflow:hidden;box-shadow:0 12px 30px rgba(15,23,42,.18)!important}.leaflet-popup-content{margin:0!important;min-width:220px}.leaflet-popup-tip{display:none}
    .map-pop-head{background:#134e4a;padding:11px 14px}.map-pop-head h4{color:#fff;font-size:13px;font-weight:900;margin:0}.map-pop-head span{color:rgba(255,255,255,.75);font-size:11px;font-weight:700}.map-pop-body{padding:10px 14px}.map-pop-row{display:flex;justify-content:space-between;gap:12px;font-size:12.5px;margin-bottom:6px}.map-pop-row b{color:#0f172a}.map-pop-row span{color:#64748b;font-weight:700}.map-pop-link{display:block;margin-top:8px;padding:8px 10px;border-radius:9px;background:#ecfdf5;color:#0f766e;text-align:center;font-size:12px;font-weight:900;text-decoration:none}

    @media(max-width:1199px){#ppmfPunjabMap{height:560px}.ppmf-label.district-label{font-size:8.5px}.ppmf-label.division-label{font-size:12px}}
    @media(max-width:767px){.sc-page-card{padding:14px}.sc-panel-header{display:block}#ppmfPunjabMap{height:420px}.sc-table{min-width:760px}.sc-pagination-wrap{align-items:flex-start}.sc-pagination-wrap .page-link{min-width:34px;height:34px;font-size:12px}.sc-perf-card{min-height:76px}.sc-perf-count{font-size:20px}}
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
    $selectedAreaType = $filters['area_type'] ?? 'district';
    $selectedKpiCategoryId = $filters['kpi_category_id'] ?? '';
    $selectedPerPage = (int) ($filters['per_page'] ?? 10);
    $perPageOptions = [10, 25, 50, 100];
    $mainRoute = Route::has('scorecard.index') ? route('scorecard.index') : url()->current();
    $tierRoute = Route::has('scorecard.tier') ? route('scorecard.tier') : '#';

    $districtRankingItems = method_exists($districtRanking ?? null, 'getCollection')
        ? $districtRanking->getCollection()->values()
        : collect($districtRanking ?? [])->values();

    $pageOffset = method_exists($districtRanking ?? null, 'currentPage')
        ? (($districtRanking->currentPage() - 1) * $districtRanking->perPage())
        : 0;

    $scoreMeta = function ($score) {
        $score = (float) $score;
        if ($score >= 90) return ['grade'=>'A+','label'=>'Excellent','class'=>'excellent'];
        if ($score >= 80) return ['grade'=>'A','label'=>'Good','class'=>'good'];
        if ($score >= 70) return ['grade'=>'B','label'=>'Good','class'=>'good'];
        if ($score >= 60) return ['grade'=>'C','label'=>'Average','class'=>'average'];
        if ($score >= 50) return ['grade'=>'D','label'=>'Average','class'=>'average'];
        return ['grade'=>'E','label'=>'Critical','class'=>'critical'];
    };

    $monthOptions = ['01'=>'January','02'=>'February','03'=>'March','04'=>'April','05'=>'May','06'=>'June','07'=>'July','08'=>'August','09'=>'September','10'=>'October','11'=>'November','12'=>'December'];
    $yearOptions = range((int) now()->format('Y'), (int) now()->format('Y') - 5);

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
        ['key'=>'critical','title'=>'Critical','range'=>'Below 50','icon'=>'bi-x-octagon-fill','class'=>'critical','count'=>(int)($summary['critical_count'] ?? 0)],
    ];

    $mapRows = [];
    foreach ($districtRankingItems as $row) {
        $district = $row->district ?? null;
        $division = $district->division ?? ($row->division ?? null);
        $score = (float)($row->score_percentage ?? 0);
        $meta = $scoreMeta($score);
        if ($district && $district->name) {
            $mapRows[] = [
                'district_id' => $row->district_id ?? $district->id ?? null,
                'district' => strtoupper($district->name),
                'district_display' => $district->name,
                'division' => $division->name ?? 'Punjab',
                'score' => round($score, 2),
                'grade' => $meta['grade'],
                'label' => $meta['label'],
                'class' => $meta['class'],
            ];
        }
    }
    $districtDetailTemplate = Route::has('scorecard.district-detail')
        ? route('scorecard.district-detail', ['district' => '__DISTRICT_ID__'])
        : url('/portal/scorecard/district/__DISTRICT_ID__');
@endphp

<div class="page-title-bar mb-4">
    <div>
        <h2 class="page-title mb-1">Chief Minister Governance Scorecard District Wise</h2>
        <p class="page-subtitle mb-0">Punjab districts · score grade, performance status, and district map view.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ $tierRoute }}" class="btn btn-gov btn-gov-outline"><i class="bi bi-layers"></i> Tier Wise</a>
        <button type="button" class="btn btn-gov btn-gov-outline" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
    </div>
</div>

<div class="sc-page-card">
    <div class="sc-filter-card">
        <form method="GET" action="{{ $mainRoute }}" id="scorecardFilters">
            <div class="row g-3 align-items-end">
                <div class="col-md-6 col-xl-2">
                    <label class="form-label">Period</label>
                    <select name="period" class="form-select scorecard-auto-submit">
                        <option value="weekly" @selected($selectedPeriod==='weekly')>Weekly</option>
                        <option value="monthly" @selected($selectedPeriod==='monthly')>Monthly</option>
                        <option value="quarterly" @selected($selectedPeriod==='quarterly')>Quarterly</option>
                        <option value="yearly" @selected($selectedPeriod==='yearly')>Yearly</option>
                        <option value="all" @selected($selectedPeriod==='all')>All Time</option>
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label">Week</label>
                    <select name="week_range" class="form-select scorecard-auto-submit">
                        <option value="">Select Week</option>
                        @foreach(($weekOptions ?? []) as $value => $label)
                            <option value="{{ $value }}" @selected((string)$selectedWeekRange === (string)$value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label">Month</label>
                    <select name="month" class="form-select scorecard-auto-submit">
                        @foreach($monthOptions as $value => $label)
                            <option value="{{ $value }}" @selected((string)$selectedMonth === (string)$value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-1">
                    <label class="form-label">Year</label>
                    <select name="year" class="form-select scorecard-auto-submit">
                        @foreach($yearOptions as $year)
                            <option value="{{ $year }}" @selected((string)$selectedYear === (string)$year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label">Area Type</label>
                    <select name="area_type" class="form-select scorecard-auto-submit">
                        <option value="district" @selected($selectedAreaType==='district')>District</option>
                        <option value="division" @selected($selectedAreaType==='division')>Division</option>
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label">KPI Category</label>
                    <select name="kpi_category_id" class="form-select scorecard-auto-submit">
                        <option value="">General</option>
                        @foreach(($kpiCategories ?? []) as $category)
                            <option value="{{ $category->id }}" @selected((string)$selectedKpiCategoryId === (string)$category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-1 d-flex">
                    <a href="{{ $mainRoute }}" class="btn btn-gov btn-gov-outline w-100"><i class="bi bi-x-circle"></i> Reset</a>
                </div>
            </div>
        </form>
    </div>

    <div class="mb-3">
        <div class="row g-3">
            @foreach($perfCards as $card)
                <div class="col-6 col-lg-3">
                    <a href="{{ $perfHref($card['key']) }}" class="sc-perf-card {{ $selectedPerformance===$card['key'] ? 'active' : '' }}">
                        <span class="sc-perf-ico {{ $card['class'] }}"><i class="bi {{ $card['icon'] }}"></i></span>
                        <span class="d-block">
                            <span class="sc-perf-title d-block">{{ $card['title'] }}</span>
                            <span class="sc-perf-count d-block">{{ number_format($card['count']) }}</span>
                            <span class="sc-perf-sub d-block">{{ $card['range'] }}</span>
                        </span>
                    </a>
                </div>
            @endforeach
        </div>
    </div>

    <div class="sc-panel mb-4">
        <div class="sc-panel-header">
            <div>
                <h5 class="sc-panel-title">Punjab District Status Map</h5>
                <div class="sc-panel-subtitle">GeoJSON Leaflet map with district status, score, division, and readable district labels.</div>
            </div>
            <div class="sc-muted text-end">Click a district polygon to view details.</div>
        </div>
        <div class="position-relative">
            <div id="ppmfPunjabMap"></div>
            <div id="ppmfMapLoader" class="ppmf-map-loader">
                <div>
                    <i class="bi bi-map fs-4 d-block mb-2"></i>
                    Loading Punjab district GeoJSON map...
                </div>
            </div>
        </div>
        <div class="ppmf-map-note">
            <span>District labels and division names are shown from GeoJSON properties where available.</span>
            <div class="ppmf-map-legend">
                <span><i class="ppmf-map-dot dot-excellent"></i> Excellent</span>
                <span><i class="ppmf-map-dot dot-good"></i> Good</span>
                <span><i class="ppmf-map-dot dot-average"></i> Average</span>
                <span><i class="ppmf-map-dot dot-critical"></i> Critical</span>
                <span><i class="ppmf-map-dot dot-nodata"></i> No Data</span>
            </div>
        </div>
    </div>

    <div class="sc-panel">
        <div class="sc-panel-header">
            <div>
                <h5 class="sc-panel-title">District Ranking Table</h5>
                <div class="sc-panel-subtitle">One ranking table only · score grade and current performance category.</div>
            </div>
            <div class="d-flex flex-column align-items-end gap-2">
                <div class="text-end sc-muted"><strong>{{ method_exists($districtRanking ?? null, 'total') ? $districtRanking->total() : $districtRankingItems->count() }}</strong><br>Total districts</div>
                <form method="GET" action="{{ $mainRoute }}" class="d-flex align-items-center gap-2">
                    @foreach(request()->except(['per_page','page']) as $key => $value)
                        @if(is_array($value))
                            @foreach($value as $v)<input type="hidden" name="{{ $key }}[]" value="{{ $v }}">@endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <span class="sc-muted mb-0">Per page</span>
                    <select name="per_page" class="form-select form-select-sm" style="width:96px" onchange="this.form.submit()">
                        @foreach($perPageOptions as $size)
                            <option value="{{ $size }}" @selected((int)$selectedPerPage === (int)$size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

        @if($districtRankingItems->count())
            <div class="table-responsive">
                <table class="sc-table">
                    <thead>
                        <tr>
                            <th class="sc-rank">Rank</th>
                            <th>District</th>
                            <th>Score Grade</th>
                            <th>Performance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($districtRankingItems as $row)
                            @php
                                $score = (float)($row->score_percentage ?? 0);
                                $meta = $scoreMeta($score);
                                $districtName = optional($row->district ?? null)->name ?? 'N/A';
                                $divisionName = optional(optional($row->district ?? null)->division ?? null)->name ?? 'Punjab District';
                                $rank = $pageOffset + $loop->iteration;
                            @endphp
                            <tr>
                                <td class="sc-rank"><span class="sc-rank-badge">{{ $rank }}</span></td>
                                <td>
                                    <a class="sc-district-name" target="_blank" href="{{ route('scorecard.district-detail', array_merge(['district' => $row->district_id], request()->query())) }}">
                                        {{ $districtName }} <i class="bi bi-box-arrow-up-right ms-1" style="font-size:12px"></i>
                                    </a>
                                    <div class="sc-muted">{{ $divisionName }}</div>
                                </td>
                                <td><span class="sc-grade-badge grade-{{ $meta['class'] }}">{{ $meta['grade'] }}</span> <strong>{{ number_format($score, 2) }}%</strong></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="sc-progress"><span class="bar-{{ $meta['class'] }}" style="width: {{ min(100, max(0, $score)) }}%"></span></div>
                                        <strong>{{ $meta['label'] }}</strong>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if(method_exists($districtRanking ?? null, 'hasPages') && $districtRanking->hasPages())
                <div class="sc-pagination-wrap">
                    <div class="sc-muted">Showing {{ $districtRanking->firstItem() }} to {{ $districtRanking->lastItem() }} of {{ $districtRanking->total() }}</div>
                    {{ $districtRanking->appends(request()->query())->links('pagination::bootstrap-5') }}
                </div>
            @endif
        @else
            <div class="sc-empty"><i class="bi bi-info-circle d-block fs-4 mb-2"></i>No scorecard data found for selected filters.</div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/plugins/leaflet/leaflet.js') }}"></script>
<script>
(function () {
    const filterForm = document.getElementById('scorecardFilters');
    if (filterForm) {
        filterForm.querySelectorAll('.scorecard-auto-submit').forEach(function (field) {
            field.addEventListener('change', function () { filterForm.submit(); });
        });
    }

    const mapEl = document.getElementById('ppmfPunjabMap');
    if (!mapEl || typeof L === 'undefined') return;

    const loader = document.getElementById('ppmfMapLoader');
    const mapRows = @json($mapRows);
    const detailTemplate = @json($districtDetailTemplate);
    const queryString = @json(http_build_query(request()->query()));
    const scoreByDistrict = {};
    mapRows.forEach(function (row) {
        scoreByDistrict[normalizeName(row.district)] = row;
    });

    const map = L.map('ppmfPunjabMap', {
        zoomControl: true,
        scrollWheelZoom: false,
        preferCanvas: true,
        attributionControl: false,
        minZoom: 6,
        maxZoom: 10
    }).setView([31.2, 72.7], 7);

    L.tileLayer('', { attribution: '' }).addTo(map);

    const geojsonCandidates = [
        @json(asset('assets/geojson/punjab_districts.geojson')),
        @json(asset('geojson/punjab_districts.geojson')),
        @json(asset('data/punjab_districts.geojson')),
        @json(asset('assets/maps/punjab_districts.geojson'))
    ];

    loadFirstAvailableGeoJson(geojsonCandidates)
        .then(function (geojson) {
            renderPunjabMap(geojson);
            if (loader) loader.style.display = 'none';
        })
        .catch(function () {
            if (loader) {
                loader.innerHTML = '<div><i class="bi bi-exclamation-triangle fs-4 d-block mb-2"></i>Punjab district GeoJSON file not found.<br><small>Place file at public/assets/geojson/punjab_districts.geojson</small></div>';
            }
        });

    function loadFirstAvailableGeoJson(urls) {
        return new Promise(function (resolve, reject) {
            let index = 0;
            const next = function () {
                if (index >= urls.length) { reject(); return; }
                fetch(urls[index], { cache: 'no-store' })
                    .then(function (response) {
                        if (!response.ok) throw new Error('not-found');
                        return response.json();
                    })
                    .then(resolve)
                    .catch(function () { index++; next(); });
            };
            next();
        });
    }

    function renderPunjabMap(geojson) {
        const divisionCenters = {};

        const layer = L.geoJSON(geojson, {
            style: function (feature) {
                const row = findDistrictRow(feature);
                return {
                    color: '#ffffff',
                    weight: 1.2,
                    fillColor: colorFor(row ? row.class : 'nodata'),
                    fillOpacity: 0.82,
                    opacity: 1
                };
            },
            onEachFeature: function (feature, polygon) {
                const props = feature.properties || {};
                const districtName = getProp(props, ['district', 'DISTRICT', 'District', 'DIST_NAME', 'DISTRICT_N', 'NAME_3', 'name', 'Name']) || 'District';
                const divisionName = getProp(props, ['division', 'DIVISION', 'Division', 'DIV_NAME', 'NAME_2']) || 'Punjab';
                const row = findDistrictRow(feature);
                const center = polygon.getBounds().getCenter();

                polygon.bindTooltip(districtName, {
                    permanent: true,
                    direction: 'center',
                    className: 'ppmf-label district-label',
                    opacity: 1
                });

                if (!divisionCenters[divisionName]) divisionCenters[divisionName] = [];
                divisionCenters[divisionName].push(center);

                polygon.bindPopup(buildPopup(districtName, divisionName, row));
                polygon.on('mouseover', function () { polygon.setStyle({ weight: 2.4, fillOpacity: 0.96 }); });
                polygon.on('mouseout', function () { layer.resetStyle(polygon); });
            }
        }).addTo(map);

        map.fitBounds(layer.getBounds(), { padding: [24, 24], maxZoom: 8 });
        setTimeout(function () { map.invalidateSize(); }, 250);

        Object.keys(divisionCenters).forEach(function (divisionName) {
            const centers = divisionCenters[divisionName];
            if (!centers || !centers.length) return;
            const avgLat = centers.reduce((sum, c) => sum + c.lat, 0) / centers.length;
            const avgLng = centers.reduce((sum, c) => sum + c.lng, 0) / centers.length;
            L.marker([avgLat, avgLng], {
                icon: L.divIcon({
                    className: 'ppmf-label division-label',
                    html: divisionName,
                    iconSize: [150, 18],
                    iconAnchor: [75, 9]
                }),
                interactive: false
            }).addTo(map);
        });
    }

    function findDistrictRow(feature) {
        const props = feature.properties || {};
        const districtName = getProp(props, ['district', 'DISTRICT', 'District', 'DIST_NAME', 'DISTRICT_N', 'NAME_3', 'name', 'Name']) || '';
        return scoreByDistrict[normalizeName(districtName)] || null;
    }

    function buildPopup(districtName, divisionName, row) {
        const score = row ? Number(row.score).toFixed(2) + '%' : 'No Data';
        const grade = row ? row.grade : '-';
        const status = row ? row.label : 'No Data';
        let detailUrl = '#';
        if (row && row.district_id) {
            detailUrl = detailTemplate.replace('__DISTRICT_ID__', row.district_id);
            if (queryString) detailUrl += (detailUrl.includes('?') ? '&' : '?') + queryString;
        }
        return `
            <div class="map-pop-head">
                <h4>${escapeHtml(districtName)}</h4>
                <span>${escapeHtml(divisionName)}</span>
            </div>
            <div class="map-pop-body">
                <div class="map-pop-row"><span>Status</span><b>${escapeHtml(status)}</b></div>
                <div class="map-pop-row"><span>Score</span><b>${escapeHtml(score)}</b></div>
                <div class="map-pop-row"><span>Grade</span><b>${escapeHtml(grade)}</b></div>
                ${row && row.district_id ? `<a class="map-pop-link" href="${detailUrl}" target="_blank">View District Detail</a>` : ''}
            </div>`;
    }

    function getProp(props, keys) {
        for (const key of keys) if (props[key] !== undefined && props[key] !== null && props[key] !== '') return props[key];
        return null;
    }

    function normalizeName(name) {
        return String(name || '').trim().toUpperCase().replace(/[^A-Z0-9]+/g, ' ');
    }

    function colorFor(statusClass) {
        switch (statusClass) {
            case 'excellent': return '#16a34a';
            case 'good': return '#2563eb';
            case 'average': return '#f59e0b';
            case 'critical': return '#dc2626';
            default: return '#94a3b8';
        }
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>'"]/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' })[char];
        });
    }
})();
</script>
@endpush

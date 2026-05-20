@extends('layouts.app')

@section('title', 'Chief Minister Governance Scorecard District Wise | PPMF Portal')
@section('page_title', 'Chief Minister Governance Scorecard District Wise')

@push('styles')
<style>
    .sc-page-card{background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:20px;box-shadow:0 16px 38px rgba(15,23,42,.07);padding:22px}.sc-header-note{font-size:13px;color:#64748b}.sc-filter-card{background:linear-gradient(135deg,#f8fafc,#ffffff);border:1px solid rgba(148,163,184,.28);border-radius:18px;padding:18px;margin-bottom:22px}.sc-filter-card .form-label{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#475569;margin-bottom:7px}.sc-filter-card .form-select,.sc-filter-card .form-control{height:42px;border-radius:11px;border-color:#cbd5e1;font-size:13px;font-weight:600;color:#334155;box-shadow:none}.sc-filter-card .form-select:focus,.sc-filter-card .form-control:focus{border-color:#1b6b57;box-shadow:0 0 0 3px rgba(27,107,87,.13)}.sc-layout{display:grid;grid-template-columns:minmax(0,1.12fr) minmax(390px,.88fr);gap:22px;align-items:start}.sc-panel{background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:18px;box-shadow:0 12px 28px rgba(15,23,42,.05);overflow:hidden}.sc-panel-header{padding:16px 18px;border-bottom:1px solid rgba(148,163,184,.22);background:linear-gradient(135deg,#f8fafc,#ffffff);display:flex;align-items:flex-start;justify-content:space-between;gap:14px}.sc-panel-title{font-size:15px;font-weight:900;color:#0f172a;margin:0;text-transform:uppercase;letter-spacing:.04em}.sc-panel-subtitle{font-size:12px;color:#64748b;margin-top:4px}.sc-table{width:100%;border-collapse:separate;border-spacing:0;margin:0}.sc-table thead th{background:#174d43;color:#fff;padding:12px 12px;font-size:11px;text-transform:uppercase;letter-spacing:.06em;font-weight:900;white-space:nowrap}.sc-table tbody td{padding:12px;border-bottom:1px solid rgba(148,163,184,.18);font-size:13px;font-weight:700;color:#0f172a;vertical-align:middle}.sc-table tbody tr:hover{background:#f8fafc}.sc-rank{width:62px;text-align:center}.sc-rank-badge{width:34px;height:34px;border-radius:12px;background:#ecfdf5;color:#166534;display:inline-flex;align-items:center;justify-content:center;font-weight:900}.sc-district-name{font-weight:900;color:#0f172a;text-transform:uppercase}.sc-muted{font-size:12px;color:#64748b;font-weight:600}.sc-grade-badge{display:inline-flex;align-items:center;justify-content:center;min-width:52px;height:30px;border-radius:999px;padding:0 10px;color:#fff;font-size:12px;font-weight:900}.grade-critical{background:#dc2626}.grade-average{background:#f59e0b;color:#111827}.grade-good{background:#2563eb}.grade-excellent{background:#16a34a}.sc-progress{height:8px;background:#e2e8f0;border-radius:999px;overflow:hidden;min-width:105px}.sc-progress span{display:block;height:100%;border-radius:999px}.bar-critical{background:#dc2626}.bar-average{background:#f59e0b}.bar-good{background:#2563eb}.bar-excellent{background:#16a34a}.sc-map-panel{position:sticky;top:88px}.sc-google-map-wrap{height:430px;border-radius:18px;overflow:hidden;border:1px solid rgba(15,23,42,.12);background:#e2e8f0;position:relative}.sc-google-map-wrap iframe{width:100%;height:100%;border:0;display:block}.sc-map-label{position:absolute;left:14px;top:14px;background:rgba(15,23,42,.82);color:#fff;border-radius:999px;padding:8px 12px;font-size:12px;font-weight:800;backdrop-filter:blur(6px)}.sc-map-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}.sc-map-actions a{border:1px solid #cbd5e1;background:#fff;color:#0f766e;border-radius:999px;padding:8px 12px;font-size:12px;font-weight:800;text-decoration:none}.sc-map-actions a:hover{background:#ecfdf5;border-color:#99f6e4;color:#14532d}.sc-district-map-list{max-height:315px;overflow:auto;padding:14px}.sc-district-map-item{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 0;border-bottom:1px solid rgba(148,163,184,.18)}.sc-district-map-item:last-child{border-bottom:0}.sc-map-pin{width:12px;height:12px;border-radius:50%;box-shadow:0 0 0 4px rgba(15,23,42,.06);flex:0 0 auto}.pin-critical{background:#dc2626}.pin-average{background:#f59e0b}.pin-good{background:#2563eb}.pin-excellent{background:#16a34a}.sc-pagination-wrap{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:14px 18px;background:#f8fafc;border-top:1px solid rgba(148,163,184,.22)}.sc-pagination-wrap .pagination{margin:0;gap:5px;flex-wrap:wrap}.sc-pagination-wrap .page-link{border-radius:10px!important;min-width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;color:#0f766e;font-weight:800;border-color:#cbd5e1;box-shadow:none}.sc-pagination-wrap .page-item.active .page-link{background:#0f766e;border-color:#0f766e;color:#fff}.sc-empty{padding:28px;text-align:center;color:#64748b;font-weight:700;background:#f8fafc}.sc-legend{display:flex;flex-wrap:wrap;gap:10px;margin-top:18px;color:#334155;font-size:12px;font-weight:800}.sc-legend span{display:inline-flex;align-items:center;gap:7px}.sc-legend i{width:11px;height:11px;border-radius:50%;display:inline-block}
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
    @media(max-width:1199px){.sc-layout{grid-template-columns:1fr}.sc-map-panel{position:relative;top:auto}.sc-google-map-wrap{height:390px}}
    @media(max-width:767px){.sc-page-card{padding:14px}.sc-panel-header{display:block}.sc-google-map-wrap{height:320px}.sc-table{min-width:760px}.sc-pagination-wrap{align-items:flex-start}.sc-pagination-wrap .page-link{min-width:34px;height:34px;font-size:12px}}
</style>
@endpush

@section('content')
@php
    $filters = $filters ?? [];
    $selectedScope = $filters['scope'] ?? 'all';
    $selectedPeriod = $filters['period'] ?? 'weekly';
    $selectedPerformance = $filters['performance'] ?? 'all';
    $selectedWeekRange = $filters['week_range'] ?? '';
    $selectedMonth = $filters['month'] ?? now()->format('m');
    $selectedYear = $filters['year'] ?? now()->format('Y');
    $selectedAreaType = $filters['area_type'] ?? 'district';
    $selectedKpiCategoryId = $filters['kpi_category_id'] ?? '';
    $selectedCalculationType = $filters['calculation_type'] ?? 'general';
    if ($selectedCalculationType === 'negative_marking') { $selectedCalculationType = 'special_branch_negative'; }
    $selectedPerPage = (int) ($filters['per_page'] ?? 10);
    $perPageOptions = [10, 25, 50, 100];
    $mainRoute = Route::has('scorecard.index') ? route('scorecard.index') : url()->current();
    $tierRoute = Route::has('scorecard.tier') ? route('scorecard.tier') : '#';
    $districtRankingItems = method_exists($districtRanking ?? null, 'getCollection') ? $districtRanking->getCollection()->values() : collect($districtRanking ?? [])->values();
    $pageOffset = method_exists($districtRanking ?? null, 'currentPage') ? (($districtRanking->currentPage() - 1) * $districtRanking->perPage()) : 0;
    $scoreMeta = function ($score) { $score=(float)$score; if($score>=90)return ['grade'=>'A+','label'=>'Excellent','class'=>'excellent']; if($score>=70)return ['grade'=>$score>=80?'A':'B','label'=>'Good','class'=>'good']; if($score>=50)return ['grade'=>$score>=60?'C':'D','label'=>'Average','class'=>'average']; return ['grade'=>'E','label'=>'Critical','class'=>'critical']; };
    $monthOptions = ['01'=>'January','02'=>'February','03'=>'March','04'=>'April','05'=>'May','06'=>'June','07'=>'July','08'=>'August','09'=>'September','10'=>'October','11'=>'November','12'=>'December'];
    $yearOptions = range((int) now()->format('Y'), (int) now()->format('Y') - 5);
    $mapQuery = 'Punjab Pakistan districts';
    $googleMapEmbedUrl = 'https://maps.google.com/maps?q=' . rawurlencode($mapQuery) . '&t=m&z=7&output=embed';
    $googleMapOpenUrl = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($mapQuery);

    $perfHref = function (string $key) use ($mainRoute, $selectedPerformance) {
        $query = request()->query();
        if ($selectedPerformance === $key) {
            unset($query['performance']);
        } else {
            $query['performance'] = $key;
        }
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
        <p class="page-subtitle mb-0">District ranking with Google map view and score-based performance colour coding.</p>
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
                <div class="col-12 col-md-6 col-lg-3"><label class="form-label">Period</label><select name="period" class="form-select"><option value="weekly" @selected($selectedPeriod==='weekly')>Weekly</option><option value="monthly" @selected($selectedPeriod==='monthly')>Monthly</option><option value="quarterly" @selected($selectedPeriod==='quarterly')>Quarterly</option><option value="yearly" @selected($selectedPeriod==='yearly')>Yearly</option><option value="all" @selected($selectedPeriod==='all')>All Time</option></select></div>
                <div class="col-12 col-md-6 col-lg-3" data-period-field="week"><label class="form-label">Week</label><select name="week_range" class="form-select" id="weekRangeSelect"><option value="">Select Week</option>@foreach(($weekOptions ?? []) as $value=>$label)<option value="{{ $value }}" @selected((string)$selectedWeekRange === (string)$value)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-12 col-md-6 col-lg-3"><label class="form-label">Month</label><select name="month" class="form-select">@foreach($monthOptions as $value=>$label)<option value="{{ $value }}" @selected((string)$selectedMonth===(string)$value)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-12 col-md-6 col-lg-3"><label class="form-label">Year</label><select name="year" class="form-select">@foreach($yearOptions as $year)<option value="{{ $year }}" @selected((string)$selectedYear===(string)$year)>{{ $year }}</option>@endforeach</select></div>

                <div class="col-12 col-md-6 col-lg-3"><label class="form-label">Area Type</label><select name="area_type" class="form-select"><option value="district" @selected($selectedAreaType==='district')>District</option><option value="division" @selected($selectedAreaType==='division')>Division</option></select></div>
                <div class="col-12 col-md-6 col-lg-3"><label class="form-label">Scope</label><select name="scope" class="form-select"><option value="all" @selected($selectedScope==='all')>All</option><option value="division" @selected($selectedScope==='division')>Division</option><option value="district" @selected($selectedScope==='district')>District</option></select></div>
                <div class="col-12 col-md-6 col-lg-3"><label class="form-label">Calculation Type</label><select name="calculation_type" class="form-select"><option value="general" @selected($selectedCalculationType==='general')>General</option><option value="sixty_forty" @selected($selectedCalculationType==='sixty_forty')>Sixty Forty Ratio</option><option value="special_branch_negative" @selected($selectedCalculationType==='special_branch_negative')>Special Branch Negative Marking</option><option value="victims_negative" @selected($selectedCalculationType==='victims_negative')>Victims Negative Marking</option></select></div>
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

                <div class="col-12 d-flex justify-content-end">
                    <a href="{{ $mainRoute }}" class="btn btn-gov btn-gov-outline" id="scorecardResetBtn">
                        <i class="bi bi-x-circle"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div id="scorecardDynamic">
        @include('scorecard.partials.scorecard-results', [
            'summary' => $summary ?? [],
            'districtRanking' => $districtRanking ?? null,
            'divisionRanking' => $divisionRanking ?? null,
            'categoryRanking' => $categoryRanking ?? null,
            'filters' => $filters ?? [],
        ])

        @if(false)
    <div class="mb-3">
        <div class="row g-2">
            @foreach($perfCards as $card)
                <div class="col-6 col-md-3 col-xl-3">
                    <a href="{{ $perfHref($card['key']) }}" class="sc-perf-card {{ $selectedPerformance===$card['key'] ? 'active' : '' }} text-decoration-none">
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

    <div class="sc-layout">
        <div class="sc-panel">
            <div class="sc-panel-header">
                <div>
                    <h5 class="sc-panel-title">District Ranking Table</h5>
                    <div class="sc-panel-subtitle">Showing score grade and current performance category.</div>
                </div>
                <div class="d-flex flex-column align-items-end gap-2">
                    <div class="text-end sc-muted">
                        <strong>{{ $districtRanking->total() ?? $districtRankingItems->count() }}</strong><br>Total districts
                    </div>
                    <form method="GET" action="{{ $mainRoute }}" class="d-flex align-items-center gap-2">
                        @foreach(request()->except(['per_page','page']) as $key => $value)
                            @if(is_array($value))
                                @foreach($value as $v)
                                    <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                @endforeach
                            @else
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <span class="sc-muted mb-0">Per page</span>
                        <select name="per_page" class="form-select form-select-sm" style="width:96px" onchange="this.form.submit()">
                            @foreach($perPageOptions as $size)
                                <option value="{{ $size }}" @selected((int)$selectedPerPage===(int)$size)>{{ $size }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>
            @if($districtRankingItems->count())
                <div class="table-responsive">
                    <table class="sc-table">
                        <thead><tr><th class="sc-rank">Rank</th><th>District</th><th>Score Grade</th><th>Performance</th></tr></thead>
                        <tbody>
                            @foreach($districtRankingItems as $row)
                                @php $score=(float)($row->score_percentage ?? 0); $meta=$scoreMeta($score); $districtName=optional($row->district ?? null)->name ?? 'N/A'; $rank=$pageOffset+$loop->iteration; @endphp
                                <tr>
                                    <td class="sc-rank"><span class="sc-rank-badge">{{ $rank }}</span></td>
                                    <td>
                                        <a class="sc-district-name text-decoration-none" target="_blank" href="{{ route('scorecard.district-detail', array_merge(['district' => $row->district_id], request()->query())) }}">
                                            {{ $districtName }}
                                            <i class="bi bi-box-arrow-up-right ms-1" style="font-size:12px"></i>
                                        </a>
                                        <div class="sc-muted">Punjab District</div>
                                    </td>
                                    <td><span class="sc-grade-badge grade-{{ $meta['class'] }}">{{ $meta['grade'] }}</span> <strong>{{ number_format($score,2) }}%</strong></td>
                                    <td><div class="d-flex align-items-center gap-2"><div class="sc-progress"><span class="bar-{{ $meta['class'] }}" style="width: {{ min(100, max(0, $score)) }}%"></span></div><strong>{{ $meta['label'] }}</strong></div></td>
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

        <div class="sc-map-panel">
            <div class="sc-panel mb-3">
                <div class="sc-panel-header"><div><h5 class="sc-panel-title">Punjab Google Map View</h5><div class="sc-panel-subtitle">Google Maps embed focused on Punjab. District cards below open exact district map search.</div></div></div>
                <div class="p-3">
                    <div class="sc-google-map-wrap"><iframe loading="lazy" allowfullscreen src="{{ $googleMapEmbedUrl }}"></iframe><div class="sc-map-label"><i class="bi bi-geo-alt-fill"></i> Punjab District Map</div></div>
                    <div class="sc-map-actions"><a href="{{ $googleMapOpenUrl }}" target="_blank"><i class="bi bi-box-arrow-up-right"></i> Open Punjab in Google Maps</a></div>
                </div>
            </div>
            {{-- Current District Map Links section removed (requested) --}}
            {{--
            <div class="sc-panel">
                <div class="sc-panel-header"><div><h5 class="sc-panel-title">Current District Map Links</h5><div class="sc-panel-subtitle">Districts visible on this page.</div></div></div>
                <div class="sc-district-map-list">
                    @forelse($districtRankingItems as $row)
                        @php $score=(float)($row->score_percentage ?? 0); $meta=$scoreMeta($score); $districtName=optional($row->district ?? null)->name ?? 'N/A'; $districtMapUrl='https://www.google.com/maps/search/?api=1&query=' . rawurlencode($districtName . ' Punjab Pakistan'); @endphp
                        <a href="{{ $districtMapUrl }}" target="_blank" class="sc-district-map-item text-decoration-none">
                            <span class="d-flex align-items-center gap-2"><span class="sc-map-pin pin-{{ $meta['class'] }}"></span><span><strong class="text-dark">{{ $districtName }}</strong><div class="sc-muted">{{ $meta['label'] }} · {{ number_format($score,2) }}%</div></span></span><i class="bi bi-arrow-up-right text-secondary"></i>
                        </a>
                    @empty
                        <div class="sc-empty">No districts available.</div>
                    @endforelse
                </div>
            </div>
            --}}
        </div>
    </div>


        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const dataUrl = @json(route('scorecard.data'));

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

        async function loadScorecardData(form, queryStringOverride) {
            const dynamic = document.getElementById('scorecardDynamic');
            const weekSelect = document.getElementById('weekRangeSelect');
            if (!dynamic) return;

            const queryString = queryStringOverride || serializeForm(form);
            const url = dataUrl + (queryString ? ('?' + queryString) : '');

            dynamic.style.opacity = '0.6';
            dynamic.style.pointerEvents = 'none';

            try {
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const json = await res.json();

                if (!json || json.status !== 'success') {
                    dynamic.innerHTML = '<div class="sc-empty">Unable to load scorecard data.</div>';
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

                pushQuery(queryString);
            } catch (e) {
                dynamic.innerHTML = '<div class="sc-empty">Error loading scorecard data. Please try again.</div>';
            } finally {
                dynamic.style.opacity = '1';
                dynamic.style.pointerEvents = 'auto';
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('scorecardFilters');
            if (!form) return;

            updatePeriodFieldVisibility(form);

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                loadScorecardData(form);
            });

            form.addEventListener('change', function (e) {
                const target = e.target;
                if (!target) return;
                if (target.matches('select, input')) {
                    updatePeriodFieldVisibility(form);
                    loadScorecardData(form);
                }
            });

            document.addEventListener('click', function (e) {
                const a = e.target.closest('#scorecardDynamic .pagination a');
                if (a) {
                    e.preventDefault();
                    const href = a.getAttribute('href');
                    if (!href) return;
                    const url = new URL(href, window.location.origin);
                    loadScorecardData(form, url.search.replace(/^\\?/, ''));
                    return;
                }

                const perf = e.target.closest('#scorecardDynamic a.sc-perf-card');
                if (perf) {
                    e.preventDefault();
                    const href = perf.getAttribute('href');
                    if (!href) return;
                    const url = new URL(href, window.location.origin);
                    loadScorecardData(form, url.search.replace(/^\\?/, ''));
                    return;
                }

                const resetBtn = e.target.closest('#scorecardResetBtn');
                if (resetBtn) {
                    e.preventDefault();
                    form.reset();
                    updatePeriodFieldVisibility(form);
                    loadScorecardData(form);
                }
            });
        });
    })();
</script>
@endpush

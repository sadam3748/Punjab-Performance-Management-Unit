@extends('layouts.app')

@section('title', 'Chief Minister Governance Scorecard District Wise | PPMF Portal')
@section('page_title', 'Chief Minister Governance Scorecard District Wise')

@push('styles')
<style>
    .sc-page-card{background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:20px;box-shadow:0 16px 38px rgba(15,23,42,.07);padding:22px}.sc-header-note{font-size:13px;color:#64748b}.sc-filter-card{background:linear-gradient(135deg,#f8fafc,#ffffff);border:1px solid rgba(148,163,184,.28);border-radius:18px;padding:18px;margin-bottom:22px}.sc-filter-card .form-label{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#475569;margin-bottom:7px}.sc-filter-card .form-select,.sc-filter-card .form-control{height:42px;border-radius:11px;border-color:#cbd5e1;font-size:13px;font-weight:600;color:#334155;box-shadow:none}.sc-filter-card .form-select:focus,.sc-filter-card .form-control:focus{border-color:#1b6b57;box-shadow:0 0 0 3px rgba(27,107,87,.13)}.sc-layout{display:grid;grid-template-columns:minmax(0,1.12fr) minmax(390px,.88fr);gap:22px;align-items:start}.sc-panel{background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:18px;box-shadow:0 12px 28px rgba(15,23,42,.05);overflow:hidden}.sc-panel-header{padding:16px 18px;border-bottom:1px solid rgba(148,163,184,.22);background:linear-gradient(135deg,#f8fafc,#ffffff);display:flex;align-items:flex-start;justify-content:space-between;gap:14px}.sc-panel-title{font-size:15px;font-weight:900;color:#0f172a;margin:0;text-transform:uppercase;letter-spacing:.04em}.sc-panel-subtitle{font-size:12px;color:#64748b;margin-top:4px}.sc-table{width:100%;border-collapse:separate;border-spacing:0;margin:0}.sc-table thead th{background:#174d43;color:#fff;padding:12px 12px;font-size:11px;text-transform:uppercase;letter-spacing:.06em;font-weight:900;white-space:nowrap}.sc-table tbody td{padding:12px;border-bottom:1px solid rgba(148,163,184,.18);font-size:13px;font-weight:700;color:#0f172a;vertical-align:middle}.sc-table tbody tr:hover{background:#f8fafc}.sc-rank{width:62px;text-align:center}.sc-rank-badge{width:34px;height:34px;border-radius:12px;background:#ecfdf5;color:#166534;display:inline-flex;align-items:center;justify-content:center;font-weight:900}.sc-district-name{font-weight:900;color:#0f172a;text-transform:uppercase}.sc-muted{font-size:12px;color:#64748b;font-weight:600}.sc-grade-badge{display:inline-flex;align-items:center;justify-content:center;min-width:52px;height:30px;border-radius:999px;padding:0 10px;color:#fff;font-size:12px;font-weight:900}.grade-critical{background:#dc2626}.grade-average{background:#f59e0b;color:#111827}.grade-good{background:#2563eb}.grade-excellent{background:#16a34a}.sc-progress{height:8px;background:#e2e8f0;border-radius:999px;overflow:hidden;min-width:105px}.sc-progress span{display:block;height:100%;border-radius:999px}.bar-critical{background:#dc2626}.bar-average{background:#f59e0b}.bar-good{background:#2563eb}.bar-excellent{background:#16a34a}.sc-map-panel{position:sticky;top:88px}.sc-google-map-wrap{height:430px;border-radius:18px;overflow:hidden;border:1px solid rgba(15,23,42,.12);background:#e2e8f0;position:relative}.sc-google-map-wrap iframe{width:100%;height:100%;border:0;display:block}.sc-map-label{position:absolute;left:14px;top:14px;background:rgba(15,23,42,.82);color:#fff;border-radius:999px;padding:8px 12px;font-size:12px;font-weight:800;backdrop-filter:blur(6px)}.sc-map-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}.sc-map-actions a{border:1px solid #cbd5e1;background:#fff;color:#0f766e;border-radius:999px;padding:8px 12px;font-size:12px;font-weight:800;text-decoration:none}.sc-map-actions a:hover{background:#ecfdf5;border-color:#99f6e4;color:#14532d}.sc-district-map-list{max-height:315px;overflow:auto;padding:14px}.sc-district-map-item{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 0;border-bottom:1px solid rgba(148,163,184,.18)}.sc-district-map-item:last-child{border-bottom:0}.sc-map-pin{width:12px;height:12px;border-radius:50%;box-shadow:0 0 0 4px rgba(15,23,42,.06);flex:0 0 auto}.pin-critical{background:#dc2626}.pin-average{background:#f59e0b}.pin-good{background:#2563eb}.pin-excellent{background:#16a34a}.sc-pagination-wrap{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:14px 18px;background:#f8fafc;border-top:1px solid rgba(148,163,184,.22)}.sc-pagination-wrap .pagination{margin:0;gap:5px;flex-wrap:wrap}.sc-pagination-wrap .page-link{border-radius:10px!important;min-width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;color:#0f766e;font-weight:800;border-color:#cbd5e1;box-shadow:none}.sc-pagination-wrap .page-item.active .page-link{background:#0f766e;border-color:#0f766e;color:#fff}.sc-empty{padding:28px;text-align:center;color:#64748b;font-weight:700;background:#f8fafc}.sc-legend{display:flex;flex-wrap:wrap;gap:10px;margin-top:18px;color:#334155;font-size:12px;font-weight:800}.sc-legend span{display:inline-flex;align-items:center;gap:7px}.sc-legend i{width:11px;height:11px;border-radius:50%;display:inline-block}@media(max-width:1199px){.sc-layout{grid-template-columns:1fr}.sc-map-panel{position:relative;top:auto}.sc-google-map-wrap{height:390px}}@media(max-width:767px){.sc-page-card{padding:14px}.sc-panel-header{display:block}.sc-google-map-wrap{height:320px}.sc-table{min-width:760px}.sc-pagination-wrap{align-items:flex-start}.sc-pagination-wrap .page-link{min-width:34px;height:34px;font-size:12px}}
</style>
@endpush

@section('content')
@php
    $filters = $filters ?? [];
    $selectedScope = $filters['scope'] ?? 'all';
    $selectedPeriod = $filters['period'] ?? 'weekly';
    $selectedWeekRange = $filters['week_range'] ?? '';
    $selectedMonth = $filters['month'] ?? now()->format('m');
    $selectedYear = $filters['year'] ?? now()->format('Y');
    $selectedAreaType = $filters['area_type'] ?? 'district';
    $selectedKpiCategoryId = $filters['kpi_category_id'] ?? '';
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
        <form method="GET" action="{{ $mainRoute }}">
            <div class="row g-3 align-items-end">
                <div class="col-md-6 col-xl-2"><label class="form-label">Scope</label><select name="scope" class="form-select"><option value="all" @selected($selectedScope==='all')>All</option><option value="division" @selected($selectedScope==='division')>Division</option><option value="district" @selected($selectedScope==='district')>District</option><option value="tehsil" @selected($selectedScope==='tehsil')>Tehsil</option></select></div>
                <div class="col-md-6 col-xl-2"><label class="form-label">Period</label><select name="period" class="form-select"><option value="weekly" @selected($selectedPeriod==='weekly')>Weekly</option><option value="monthly" @selected($selectedPeriod==='monthly')>Monthly</option><option value="quarterly" @selected($selectedPeriod==='quarterly')>Quarterly</option><option value="yearly" @selected($selectedPeriod==='yearly')>Yearly</option><option value="all" @selected($selectedPeriod==='all')>All Time</option></select></div>
                <div class="col-md-6 col-xl-2"><label class="form-label">Week</label><select name="week_range" class="form-select"><option value="">Select Week</option>@foreach(($weekOptions ?? []) as $value=>$label)<option value="{{ $value }}" @selected($selectedWeekRange===$value)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-6 col-xl-2"><label class="form-label">Month</label><select name="month" class="form-select">@foreach($monthOptions as $value=>$label)<option value="{{ $value }}" @selected((string)$selectedMonth===(string)$value)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-6 col-xl-2"><label class="form-label">Year</label><select name="year" class="form-select">@foreach($yearOptions as $year)<option value="{{ $year }}" @selected((string)$selectedYear===(string)$year)>{{ $year }}</option>@endforeach</select></div>
                <div class="col-md-6 col-xl-2"><label class="form-label">Area Type</label><select name="area_type" class="form-select"><option value="district" @selected($selectedAreaType==='district')>District</option><option value="division" @selected($selectedAreaType==='division')>Division</option><option value="tehsil" @selected($selectedAreaType==='tehsil')>Tehsil</option></select></div>
                <div class="col-md-6 col-xl-4"><label class="form-label">KPI Category</label><select name="kpi_category_id" class="form-select"><option value="">General</option>@foreach(($kpiCategories ?? []) as $category)<option value="{{ $category->id }}" @selected((string)$selectedKpiCategoryId===(string)$category->id)>{{ $category->name }}</option>@endforeach</select></div>
                <div class="col-md-6 col-xl-3 d-flex gap-2"><button type="submit" class="btn btn-gov btn-gov-primary flex-fill"><i class="bi bi-search"></i> Apply</button><a href="{{ $mainRoute }}" class="btn btn-gov btn-gov-outline"><i class="bi bi-x-circle"></i> Reset</a></div>
            </div>
        </form>
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
                                    <td><div class="sc-district-name">{{ $districtName }}</div><div class="sc-muted">Punjab District</div></td>
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

    <div class="sc-legend"><span><i class="pin-excellent"></i> Excellent 90-100</span><span><i class="pin-good"></i> Good 70-89.99</span><span><i class="pin-average"></i> Average 50-69.99</span><span><i class="pin-critical"></i> Critical below 50</span></div>
</div>
@endsection

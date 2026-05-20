@php
    $filters = $filters ?? [];
    $selectedPerformance = $filters['performance'] ?? 'all';
    $districtRankingItems = method_exists($districtRanking ?? null, 'getCollection') ? $districtRanking->getCollection()->values() : collect($districtRanking ?? [])->values();
    $pageOffset = method_exists($districtRanking ?? null, 'currentPage') ? (($districtRanking->currentPage() - 1) * $districtRanking->perPage()) : 0;
    $mainRoute = Route::has('scorecard.index') ? route('scorecard.index') : url()->current();
    $scoreMeta = function ($score) { $score=(float)$score; if($score>=90)return ['grade'=>'A+','label'=>'Excellent','class'=>'excellent']; if($score>=70)return ['grade'=>$score>=80?'A':'B','label'=>'Good','class'=>'good']; if($score>=50)return ['grade'=>$score>=60?'C':'D','label'=>'Average','class'=>'average']; return ['grade'=>'E','label'=>'Critical','class'=>'critical']; };

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

    $mapQuery = 'Punjab Pakistan districts';
    $googleMapEmbedUrl = 'https://maps.google.com/maps?q=' . rawurlencode($mapQuery) . '&t=m&z=7&output=embed';
    $googleMapOpenUrl = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($mapQuery);
@endphp

<div class="mb-3" id="summaryCardsContainer">
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

<div class="sc-layout" id="scorecardResults">
    <div class="sc-panel" id="districtRankingContainer">
        <div class="sc-panel-header">
            <div>
                <h5 class="sc-panel-title">District Ranking Table</h5>
                <div class="sc-panel-subtitle">Showing score grade and current performance category.</div>
                <div class="sc-legend" style="margin-top:10px">
                    <span><i class="pin-excellent"></i> Excellent 90-100</span>
                    <span><i class="pin-good"></i> Good 70-89.99</span>
                    <span><i class="pin-average"></i> Average 50-69.99</span>
                    <span><i class="pin-critical"></i> Critical below 50</span>
                </div>
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
                        @foreach([10,25,50,100] as $size)
                            <option value="{{ $size }}" @selected((int)request('per_page',10)===(int)$size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
        @if($districtRankingItems->count())
            <div class="table-responsive">
                <table class="sc-table">
                    <thead><tr><th class="sc-rank">Rank</th><th>District</th><th>Score</th><th>Performance</th><th class="text-end">Action</th></tr></thead>
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
                                    <div class="sc-muted">{{ optional(optional($row->district ?? null)->division ?? null)->name ?? '—' }}</div>
                                </td>
                                <td class="fw-bold">{{ number_format($score,2) }}%</td>
                                <td><span class="sc-grade-badge grade-{{ $meta['class'] }}">{{ $meta['grade'] }}</span> <strong>{{ $meta['label'] }}</strong></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-gov btn-gov-outline" target="_blank" href="{{ route('scorecard.district-detail', array_merge(['district' => $row->district_id], request()->query())) }}">
                                        View
                                    </a>
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

    <div class="sc-map-panel" id="scorecardMapContainer">
        <div class="sc-panel mb-3">
            <div class="sc-panel-header"><div><h5 class="sc-panel-title">Punjab Google Map View</h5><div class="sc-panel-subtitle">Google Maps embed focused on Punjab. District cards below open exact district map search.</div></div></div>
            <div class="p-3">
                <div class="sc-google-map-wrap"><iframe loading="lazy" allowfullscreen src="{{ $googleMapEmbedUrl }}"></iframe><div class="sc-map-label"><i class="bi bi-geo-alt-fill"></i> Punjab District Map</div></div>
                <div class="sc-map-actions"><a href="{{ $googleMapOpenUrl }}" target="_blank"><i class="bi bi-box-arrow-up-right"></i> Open Punjab in Google Maps</a></div>
            </div>
        </div>
    </div>
</div>

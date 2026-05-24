@php
    $filters = $filters ?? [];
    $selectedAreaType = $filters['area_type'] ?? 'district';
    $selectedPerPage = (int) ($filters['per_page'] ?? 10);
    $perPageOptions = [10, 25, 50, 100];

    $districtRankingItems = method_exists($districtRanking ?? null, 'getCollection')
        ? $districtRanking->getCollection()->values()
        : collect($districtRanking ?? [])->values();
    $pageOffset = method_exists($districtRanking ?? null, 'currentPage')
        ? (($districtRanking->currentPage() - 1) * $districtRanking->perPage())
        : 0;

    $scoreMeta = function ($score) {
        $score = (float) $score;
        if ($score >= 90) return ['grade'=>'A+','label'=>'Excellent','class'=>'excellent'];
        if ($score >= 80) return ['grade'=>'A', 'label'=>'Good',     'class'=>'good'];
        if ($score >= 70) return ['grade'=>'B', 'label'=>'Good',     'class'=>'good'];
        if ($score >= 60) return ['grade'=>'C', 'label'=>'Average',  'class'=>'average'];
        if ($score >= 50) return ['grade'=>'D', 'label'=>'Average',  'class'=>'average'];
        return                   ['grade'=>'E', 'label'=>'Critical', 'class'=>'critical'];
    };
@endphp

<div class="sc-panel" id="scorecardTablePanel">
    <div class="sc-panel-header">
        <div>
            <h5 class="sc-panel-title" id="tablePanelTitle">
                @if($selectedAreaType === 'division') Division @else District @endif Ranking
            </h5>
            <div class="sc-panel-subtitle">Click row → highlight on map · Click name → view detail</div>
        </div>
        <div class="d-flex flex-column align-items-end gap-2">
            <div class="text-end sc-muted">
                <strong>{{ method_exists($districtRanking ?? null,'total') ? $districtRanking->total() : $districtRankingItems->count() }}</strong> total
            </div>
            <form method="GET" action="{{ $mainRoute ?? (Route::has('scorecard.index') ? route('scorecard.index') : url()->current()) }}" class="d-flex align-items-center gap-2">
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
                            $areaName    = optional($row->district ?? null)->name ?? 'N/A';
                            $rank        = $pageOffset + $loop->iteration;
                            $detailUrl   = Route::has('scorecard.district-detail')
                                            ? route('scorecard.district-detail', array_merge(['district' => $row->district_id], request()->query()))
                                            : '#';
                        @endphp
                        <tr data-area="{{ strtoupper($areaName) }}"
                            data-detail="{{ $detailUrl }}"
                            onclick="ppmfMap.onTableClick('{{ strtoupper($areaName) }}')">
                            <td class="sc-rank"><span class="sc-rank-badge">{{ $rank }}</span></td>
                            <td>
                                <a class="sc-district-name" href="{{ $detailUrl }}" onclick="event.stopPropagation()">
                                    {{ $areaName }}<i class="bi bi-box-arrow-up-right ms-1" style="font-size:11px"></i>
                                </a>
                                <div class="sc-muted">Punjab {{ $selectedAreaType === 'division' ? 'Division' : 'District' }}</div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="sc-grade-badge grade-{{ $meta['class'] }}">{{ $meta['grade'] }}</span>
                                    <strong>{{ number_format($score, 2) }}%</strong>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="sc-progress" style="min-width:120px">
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

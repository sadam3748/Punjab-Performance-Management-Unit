<div class="ppmu-section-head mt-4" id="kpiRecordsHead">
    <div>
        <h2><i class="bi bi-table"></i> Detailed Records</h2>
        <p id="kpiRecordsCount">
            <strong>{{ number_format($tableSubmissions->total()) }}</strong> record{{ $tableSubmissions->total() === 1 ? '' : 's' }}
            @if(!empty($periodDescription))
                <span class="ppmu-records-period">· {{ $periodDescription }}</span>
            @endif
        </p>
    </div>
    <div class="ppmu-table-toolbar">
        <label class="ppmu-per-page-label">
            <span>Show</span>
            <select class="form-select form-select-sm" data-kpi-per-page>
                @foreach([10, 15, 25, 50] as $n)
                    <option value="{{ $n }}" @selected((int) request('per_page', 15) === $n)>{{ $n }}</option>
                @endforeach
            </select>
        </label>
        <div class="ppmu-search-box">
            <i class="bi bi-search"></i>
            <input type="text" id="kpiTableSearch" placeholder="Search area, status, week…" autocomplete="off">
        </div>
    </div>
</div>

<div class="card-ppmf ppmu-table-card" data-kpi-records>
    <div class="ppmu-table-wrap">
        <table class="table-ppmf ppmu-table ppmu-detail-table" id="kpiDetailTable">
            <thead>
                <tr>
                    <th class="ppmu-th-num">#</th>
                    <th class="ppmu-col-week">Week</th>
                    <th class="ppmu-col-area">Area</th>
                    <th class="ppmu-col-num">Target</th>
                    <th class="ppmu-col-num ppmu-hide-md">Reported</th>
                    <th class="ppmu-col-num">Achieved</th>
                    <th class="ppmu-col-num ppmu-hide-md">Pending</th>
                    <th class="ppmu-col-pct">%</th>
                    <th class="ppmu-col-status">Status</th>
                    <th class="ppmu-col-user ppmu-hide-lg">Submitted By</th>
                    <th class="ppmu-col-date ppmu-hide-xl">Updated</th>
                    <th class="ppmu-th-action"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($tableSubmissions as $rowIndex => $item)
                    @php
                        $itemTarget = (float) ($item->target_value ?? $summary['target']);
                        $itemReported = (float) ($item->reported_value ?? 0);
                        $itemAchieved = (float) ($item->achieved_value ?? $item->score);
                        $itemPending = (float) ($item->pending_value ?? max(0, $itemTarget - $itemAchieved));
                        $itemPct = (float) ($item->achievement_percentage ?? ($itemTarget > 0 ? round(min(100, ($itemAchieved / $itemTarget) * 100), 1) : 0));
                        $pctClass = $itemPct >= 85 ? 'ppmu-pct-excellent'
                                  : ($itemPct >= 70 ? 'ppmu-pct-good'
                                  : ($itemPct >= 50 ? 'ppmu-pct-warn'
                                  : 'ppmu-pct-critical'));
                        $itemArea = $item->tehsil?->name
                            ?? $item->district?->name
                            ?? $item->division?->name
                            ?? 'Punjab';
                        $weekLabel = $item->week_start_date && $item->week_end_date
                            ? 'W'.$item->week_no.' · '.$item->week_start_date->format('d M').'–'.$item->week_end_date->format('d M')
                            : ($item->week_no ? 'W'.$item->week_no : $item->submission_date->format('d M Y'));
                        $searchStr = strtolower($item->period_label.' '.$item->status.' '.($item->user?->name ?? '').' '.$itemArea.' '.$weekLabel);
                    @endphp
                    <tr data-search="{{ $searchStr }}">
                        <td class="ppmu-td-num text-muted">{{ $tableSubmissions->firstItem() + $rowIndex }}</td>
                        <td class="ppmu-period-cell">
                            <strong title="{{ $weekLabel }}">{{ \Illuminate\Support\Str::limit($weekLabel, 22) }}</strong>
                            <small>{{ $item->submission_date->format('d M Y') }}</small>
                        </td>
                        <td class="ppmu-col-area" title="{{ $itemArea }}">{{ \Illuminate\Support\Str::limit($itemArea, 14) }}</td>
                        <td class="ppmu-col-num text-end">{{ number_format($itemTarget, 1) }}</td>
                        <td class="ppmu-col-num text-end ppmu-hide-md">{{ number_format($itemReported, 1) }}</td>
                        <td class="ppmu-col-num text-end fw-semibold">{{ number_format($itemAchieved, 1) }}</td>
                        <td class="ppmu-col-num text-end ppmu-hide-md">{{ number_format($itemPending, 1) }}</td>
                        <td class="ppmu-col-pct text-center"><span class="ppmu-pct-badge {{ $pctClass }}">{{ $itemPct }}%</span></td>
                        <td class="ppmu-col-status"><x-status-badge :status="$item->status"/></td>
                        <td class="ppmu-col-user ppmu-hide-lg" title="{{ $item->user?->name }}">{{ \Illuminate\Support\Str::limit($item->user?->name ?? '—', 12) }}</td>
                        <td class="ppmu-col-date ppmu-hide-xl"><small class="text-muted">{{ $item->updated_at->diffForHumans() }}</small></td>
                        <td class="text-center">
                            <button type="button" class="ppmu-view-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#recModal{{ $item->id }}"
                                    title="View details"
                                    aria-label="View record details">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr class="ppmu-empty-row">
                        <td colspan="12">
                            <div class="ppmu-empty-state">
                                <i class="bi bi-inbox"></i>
                                <h5>No records found</h5>
                                <p>Adjust filters or submit data to populate this table.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($tableSubmissions->hasPages())
        <div class="ppmu-pagination-wrap">
            {{ $tableSubmissions->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

<div id="kpiRecordModals">
@foreach($tableSubmissions as $item)
    @php
        $mTarget = (float) ($item->target_value ?? $summary['target']);
        $mReported = (float) ($item->reported_value ?? 0);
        $mAchieved = (float) ($item->achieved_value ?? $item->score);
        $mPending = (float) ($item->pending_value ?? max(0, $mTarget - $mAchieved));
        $mPct = (float) ($item->achievement_percentage ?? ($mTarget > 0 ? round(min(100, ($mAchieved / $mTarget) * 100), 1) : 0));
        $mWeek = $item->week_start_date && $item->week_end_date
            ? $item->week_start_date->format('d M Y').' – '.$item->week_end_date->format('d M Y')
            : '—';
        $mWeekNum = $item->week_no && preg_match('/\d{4}(\d{2})$/', (string) $item->week_no, $wm) ? (int) $wm[1] : null;
        $mWeekLabel = $mWeekNum ? sprintf('W%02d · %s', $mWeekNum, $mWeek) : $mWeek;
        $mArea = $item->tehsil?->name ?? $item->district?->name ?? $item->division?->name ?? 'Punjab';
        $snapshot = is_array($item->metric_snapshot) ? $item->metric_snapshot : json_decode($item->metric_snapshot ?? '[]', true);
    @endphp
    <div class="modal fade ppmu-record-modal" id="recModal{{ $item->id }}" tabindex="-1" aria-labelledby="recLbl{{ $item->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content ppmu-modal-v2">
                <div class="ppmu-modal-v2-hero">
                    <div class="ppmu-modal-v2-hero-inner">
                        <div class="ppmu-modal-v2-visual">
                            <img src="{{ $imageUrl }}" alt="{{ $kpiCard->title }}">
                        </div>
                        <div class="ppmu-modal-v2-title">
                            <span class="ppmu-modal-v2-eyebrow">{{ $kpiCard->category }}</span>
                            <h5 class="modal-title mb-1" id="recLbl{{ $item->id }}">{{ $kpiCard->title }}</h5>
                            <p class="ppmu-modal-v2-meta mb-0">
                                <i class="bi bi-geo-alt-fill"></i> {{ $mArea }}
                                <span class="ppmu-modal-sep">·</span>
                                <i class="bi bi-calendar3"></i> {{ $item->submission_date->format('d M Y') }}
                                <span class="ppmu-modal-sep">·</span>
                                <i class="bi bi-calendar-week"></i> {{ $mWeekLabel }}
                            </p>
                        </div>
                        <div class="ppmu-modal-v2-status">
                            <span>Status</span>
                            <x-status-badge :status="$item->status"/>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white ppmu-modal-v2-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body ppmu-modal-v2-body">
                    <div class="ppmu-modal-v2-stats">
                        <div class="ppmu-modal-v2-stat tone-blue"><span>Target</span><strong>{{ number_format($mTarget, 1) }}</strong></div>
                        <div class="ppmu-modal-v2-stat tone-blue"><span>Reported</span><strong>{{ number_format($mReported, 1) }}</strong></div>
                        <div class="ppmu-modal-v2-stat tone-green"><span>Achieved</span><strong>{{ number_format($mAchieved, 1) }}</strong></div>
                        <div class="ppmu-modal-v2-stat tone-orange"><span>Pending</span><strong>{{ number_format($mPending, 1) }}</strong></div>
                        <div class="ppmu-modal-v2-stat tone-purple"><span>Achievement</span><strong>{{ $mPct }}%</strong></div>
                        <div class="ppmu-modal-v2-stat tone-blue"><span>Submitted By</span><strong>{{ $item->user?->name ?? '—' }}</strong></div>
                    </div>

                    @if(!empty($snapshot))
                        <div class="ppmu-modal-v2-section">
                            <h6><i class="bi bi-bar-chart-line"></i> Metric Breakdown</h6>
                            <div class="ppmu-modal-v2-metrics">
                                @foreach($snapshot as $key => $val)
                                    <div class="ppmu-modal-v2-metric">
                                        <span>{{ str_replace('_', ' ', ucfirst($key)) }}</span>
                                        <strong>{{ $val }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @elseif($item->values->isNotEmpty())
                        <div class="ppmu-modal-v2-section">
                            <h6><i class="bi bi-bar-chart-line"></i> Metric Breakdown</h6>
                            <div class="ppmu-modal-v2-metrics">
                                @foreach($item->values as $val)
                                    <div class="ppmu-modal-v2-metric">
                                        <span>{{ $val->field?->field_label ?? $val->field?->field_name ?? 'Field' }}</span>
                                        <strong>{{ $val->value ?: '—' }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($item->remarks)
                        <div class="ppmu-modal-v2-remarks">
                            <h6><i class="bi bi-chat-quote"></i> Remarks</h6>
                            <p>{{ $item->remarks }}</p>
                        </div>
                    @endif
                </div>
                <div class="modal-footer ppmu-modal-v2-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endforeach
</div>

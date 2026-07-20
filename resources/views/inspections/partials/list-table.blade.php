@php $observationService = app(\App\Services\KpiObservationService::class); @endphp
<div class="ppmu-inspection-table-wrap">
    <table class="table-ppmf ppmu-table inspection-table inspection-table-compact ppmu-inspection-table">
        <thead>
            <tr>
                <th data-col="sr">Sr. No.</th>
                <th data-col="reference_no">Inspection ID</th>
                <th data-col="inspection_type">Inspection Type</th>
                <th data-col="inspection_name">Inspection Name</th>
                <th data-col="key_finding">Key Finding</th>
                <th data-col="tehsil">Tehsil</th>
                <th data-col="district">District</th>
                <th data-col="inspection_date">Date &amp; Time</th>
                <th data-col="inspection_status">Inspection Status</th>
                <th data-col="status">Review Status</th>
                <th data-col="inspector">Inspector</th>
                <th data-col="reviewer">Reviewer</th>
                <th class="ppmu-th-action" data-col="action">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($inspectionRecords as $inspection)
                @php
                    $card = $inspection->kpiCard;
                    $inspectionType = $inspection->entity_type ?: $inspection->inspection_title ?: '—';
                    $inspectionName = $inspection->entity_name ?? '—';
                    $serialNo = ($inspectionRecords->firstItem() ?? 0) + $loop->index;
                @endphp
                <tr>
                    <td data-col="sr" class="ppmu-inspection-sr">{{ $serialNo }}</td>
                    <td data-col="reference_no"><strong class="ppmu-inspection-ref">{{ $inspection->reference_no }}</strong></td>
                    <td data-col="inspection_type" title="{{ $inspectionType }}">{{ \Illuminate\Support\Str::limit($inspectionType, 22) }}</td>
                    <td data-col="inspection_name" class="ppmu-inspection-name-cell" title="{{ $inspectionName }}">{{ \Illuminate\Support\Str::limit($inspectionName, 28) }}</td>
                    <td data-col="key_finding" class="ppmu-cell-ellipsis" title="{{ $observationService->keyFinding($inspection) }}">{{ \Illuminate\Support\Str::limit($observationService->keyFinding($inspection), 34) }}</td>
                    <td data-col="tehsil">{{ $inspection->tehsil?->name ?? '—' }}</td>
                    <td data-col="district">{{ $inspection->district?->name ?? '—' }}</td>
                    <td data-col="inspection_date">{{ $inspection->inspection_datetime->copy()->timezone(config('app.inspection_timezone', 'Asia/Karachi'))->format('d M Y, h:i A') }}</td>
                    <td data-col="inspection_status"><span class="ppmu-inspection-status ppmu-inspection-status-inspected_only">Inspected</span></td>
                    <td data-col="status">
                        @php $displayStatusKey = $inspection->displayStatusKeyFor(auth()->user()); @endphp
                        <span class="ppmu-inspection-status ppmu-inspection-status-{{ $displayStatusKey }}">{{ $inspection->displayStatusFor(auth()->user()) }}</span>
                    </td>
                    <td data-col="inspector">{{ $inspection->inspectedBy?->name ?? 'â€”' }}</td>
                    <td data-col="reviewer">{{ $inspection->isSelectedFor(auth()->user()) ? ($inspection->reviewedBy?->name ?? 'â€”') : 'â€”' }}</td>
                    <td class="text-center ppmu-td-action" data-col="action">
                        @if($card)
                            <a href="{{ route('kpi.inspections.show', [$card, $inspection, 'return_url' => route('inspections.index', request()->query())]) }}"
                               class="ppmu-inspection-view-icon"
                               title="View inspection detail"
                               aria-label="View inspection {{ $inspection->reference_no }}">
                                <i class="bi bi-eye"></i>
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="13">
                        <div class="ppmu-empty-state py-4">
                            <i class="bi bi-clipboard2-check"></i>
                            <h5>No inspection records</h5>
                            <p>No field inspections match the selected filters in your scope.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($inspectionRecords->total() > 0)
    <div class="ppmu-pagination-bar ppmu-inspection-pagination">
        <div class="ppmu-pagination-meta" id="inspectionPaginationMeta">
            Showing {{ number_format($inspectionRecords->firstItem() ?? 0) }}–{{ number_format($inspectionRecords->lastItem() ?? 0) }}
            of {{ number_format($inspectionRecords->total()) }} inspections
        </div>
        <div class="ppmu-pagination-links ppmu-pagination-wrap">
            {{ $inspectionRecords->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endif

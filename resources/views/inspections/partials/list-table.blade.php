<div class="ppmu-inspection-table-wrap">
    <table class="table-ppmf ppmu-table inspection-table inspection-table-compact ppmu-inspection-table">
        <thead>
            <tr>
                <th>Ref.</th>
                <th>KPI</th>
                <th>Entity</th>
                <th>Tehsil</th>
                <th>Date</th>
                <th>Status</th>
                <th class="ppmu-th-action"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($inspectionRecords as $inspection)
                @php $card = $inspection->kpiCard; @endphp
                <tr>
                    <td><strong class="ppmu-inspection-ref">{{ $inspection->reference_no }}</strong></td>
                    <td title="{{ $card?->title }}">{{ \Illuminate\Support\Str::limit($card?->title ?? '—', 28) }}</td>
                    <td title="{{ $inspection->entity_name }}">{{ \Illuminate\Support\Str::limit($inspection->entity_name ?? '—', 24) }}</td>
                    <td>{{ $inspection->tehsil?->name ?? '—' }}</td>
                    <td>{{ $inspection->inspection_datetime->format('d M Y') }}</td>
                    <td>
                        <span class="ppmu-inspection-status ppmu-inspection-status-{{ $inspection->status }}">{{ $inspection->statusLabel() }}</span>
                    </td>
                    <td class="text-center ppmu-td-action">
                        @if($card)
                            <a href="{{ route('kpi.inspections.show', [$card, $inspection]) }}"
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
                    <td colspan="7">
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

@props([
    'kpiCard',
    'inspectionRecords',
    'tableColumns' => [],
    'fallbackImage' => null,
    'inspectionService' => null,
])

@php
    $sourceColumns = ! empty($tableColumns)
        ? $tableColumns
        : app(\App\Services\KpiInspectionService::class)->getTableColumnsForKpi($kpiCard->slug);
    $service = $inspectionService ?? app(\App\Services\KpiInspectionService::class);
    $columnKey = fn (array $column): string => (string) ($column['key'] ?? $column['field'] ?? '');
    $baseKeys = ['reference_no', 'district', 'tehsil', 'address', 'inspected_by', 'inspection_date', 'inspection_link', 'status', 'action'];
    $detailColumns = collect($sourceColumns)
        ->filter(fn (array $column): bool => ! in_array($columnKey($column), $baseKeys, true))
        ->reject(fn (array $column): bool => in_array($column['from'] ?? '', ['address', 'inspector'], true))
        ->values();
    $primaryDetailColumn = $detailColumns->first(
        fn (array $column): bool => in_array($columnKey($column), ['entity_name', 'facility_name', 'plant_name', 'identifier', 'market_name', 'office_name'], true)
    ) ?? $detailColumns->first();
    $secondaryDetailColumn = $detailColumns
        ->reject(fn (array $column): bool => $primaryDetailColumn && $columnKey($column) === $columnKey($primaryDetailColumn))
        ->first();
    $columns = collect([
        ['key' => 'reference_no', 'label' => 'Ref.', 'type' => 'base'],
        $primaryDetailColumn,
        $secondaryDetailColumn,
        ['key' => 'inspection_date', 'label' => 'Date', 'type' => 'base'],
        ['key' => 'status', 'label' => 'Status', 'type' => 'base'],
        ['key' => 'action', 'label' => '', 'type' => 'base'],
    ])->filter()->unique(fn (array $column): string => $columnKey($column))->values()->all();
@endphp

<div class="ppmu-inspection-table-wrap">
    <table class="table-ppmf ppmu-table inspection-table inspection-table-compact ppmu-inspection-table">
        <thead>
            <tr>
                @foreach($columns as $column)
                    @php $key = $columnKey($column); @endphp
                    <th @class([
                        'ppmu-th-action' => $key === 'action',
                        'ppmu-th-status' => $key === 'status',
                    ]) data-col="{{ $key }}">{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($inspectionRecords as $inspection)
                <tr>
                    @foreach($columns as $column)
                        @php $key = $columnKey($column); @endphp
                        @if($key === 'action')
                            <td class="text-center ppmu-td-action" data-col="{{ $key }}">
                                <a href="{{ route('kpi.inspections.show', [$kpiCard, $inspection]) }}"
                                   class="ppmu-inspection-view-icon"
                                   title="View inspection detail"
                                   aria-label="View inspection detail for {{ $inspection->reference_no }}">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        @elseif($key === 'status')
                            <td class="ppmu-td-status" data-col="{{ $key }}">
                                <span class="ppmu-inspection-status ppmu-inspection-status-{{ $inspection->status }}">{{ $inspection->statusLabel() }}</span>
                            </td>
                        @elseif($key === 'inspection_link')
                            <td class="ppmu-cell-ellipsis" data-col="{{ $key }}">
                                <a href="{{ route('kpi.inspections.show', [$kpiCard, $inspection]) }}" class="ppmu-inspection-link" title="{{ $inspection->reference_no }}">
                                    View
                                </a>
                            </td>
                        @elseif($key === 'reference_no')
                            <td class="ppmu-cell-ellipsis" data-col="{{ $key }}" title="{{ $inspection->reference_no }}">
                                <strong class="ppmu-inspection-ref">{{ $inspection->reference_no }}</strong>
                            </td>
                        @elseif($key === 'inspection_date')
                            <td class="ppmu-cell-ellipsis" data-col="{{ $key }}">{{ $inspection->inspection_datetime->format('d M y') }}</td>
                        @elseif($key === 'tehsil')
                            <td class="ppmu-cell-ellipsis" title="{{ $inspection->tehsil?->name }}">{{ $inspection->tehsil?->name ?? '—' }}</td>
                        @elseif($key === 'inspected_by')
                            <td class="ppmu-cell-ellipsis" title="{{ $inspection->inspector?->name }}">{{ $inspection->inspector?->name ?? '—' }}</td>
                        @else
                            <td class="ppmu-cell-ellipsis" data-col="{{ $key }}" title="{{ $service->cellValue($inspection, $column) }}">{{ $service->cellValue($inspection, $column) }}</td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}">
                        <div class="ppmu-empty-state py-4">
                            <i class="bi bi-clipboard2-check"></i>
                            <h5>No inspection records</h5>
                            <p>No field inspections found for this KPI in your scope.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($inspectionRecords->hasPages())
    <div class="ppmu-pagination-wrap">
        {{ $inspectionRecords->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
@endif
